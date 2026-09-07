<?php

namespace App\Services;

use App\Enums\BillingPackType;
use App\Enums\BillingSubscriptionType;
use App\Enums\GroupPlan;
use App\Enums\HttpStatusCode;
use App\Models\Group;
use App\Models\User;
use App\Traits\ExtractsSubscriptionEndsAt;
use Carbon\Carbon;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Subscription;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BillingService
{
    use ExtractsSubscriptionEndsAt;
    /**
     * サブスクリプションのチェックアウトを作成する
     * @param Group $group
     * @param User $user
     * @param BillingSubscriptionType $subscriptionType
     * @return string
     * @throws HttpException
     */
    public function createSubscriptionCheckout(Group $group, User $user, BillingSubscriptionType $subscriptionType): string
    {
        if ($group->subscribed(config('billing.subscription_type'))) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.already_subscribed'),
            );
        }

        $this->ensureStripeCustomer($group, $user);
        $urls = $this->frontendCallbackUrls();

        /** @var Checkout $checkout */
        $checkout = $group->newSubscription(
            config('billing.subscription_type'),
            $this->priceId($subscriptionType->configKey()),
        )
            ->withMetadata([
                'group_id' => $group->id,
            ])
            ->checkout([
                'success_url' => $urls['success'],
                'cancel_url' => $urls['cancel'],
                'automatic_tax' => ['enabled' => true],
                'customer_update' => ['address' => 'auto'],
            ]);

        return $checkout->url;
    }

    /**
     * Stripe Customer Portal セッションを作成する
     * @param Group $group
     * @return string
     * @throws HttpException
     */
    public function createPortalSession(Group $group): string
    {
        if (! $group->hasStripeId()) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.no_billing_account'),
            );
        }

        $returnUrl = rtrim((string) config('app.frontend_url'), '/') . '/settings/billing';

        return $group->billingPortalUrl($returnUrl);
    }

    /**
     * 買い切りパックのチェックアウトを作成する
     * @param Group $group
     * @param User $user
     * @param BillingPackType $packType
     * @return string
     * @throws HttpException
     */
    public function createPackCheckout(Group $group, User $user, BillingPackType $packType): string
    {
        $this->ensureStripeCustomer($group, $user);
        $urls = $this->frontendCallbackUrls();

        /** @var Checkout $checkout */
        $checkout = $group->checkout([$this->priceId($packType->configKey()) => 1], [
            'success_url' => $urls['success'],
            'cancel_url' => $urls['cancel'],
            'automatic_tax' => ['enabled' => true],
            'customer_update' => ['address' => 'auto'],
            'metadata' => [
                'type' => 'pack',
                'group_id' => $group->id,
                'credits' => (string) $packType->credits(),
            ],
            'invoice_creation' => [
                'enabled' => true,
                'invoice_data' => [
                    'metadata' => [
                        'type' => 'pack',
                        'group_id' => $group->id,
                        'credits' => (string) $packType->credits(),
                    ],
                ],
            ],
        ]);

        return $checkout->url;
    }

    /**
     * 課金・サブスクリプション状態を取得する
     * @param Group $group
     * @return array{
     *     plan: string,
     *     isSubscribed: bool,
     *     subscriptionStatus: string|null,
     *     subscriptionEndsAt: string|null,
     *     pendingPlanChange: array{nextPlan: string, changesAt: string}|null,
     *     pmType: string|null,
     *     pmLastFour: string|null,
     *     pmExpMonth: int|null,
     *     pmExpYear: int|null,
     * }
     */
    public function getBillingStatus(Group $group): array
    {
        $subscriptionType = config('billing.subscription_type');
        $subscription = $group->subscription($subscriptionType);
        $plan = $group->plan ?? GroupPlan::FREE;

        $pendingPlanChange = $this->getPendingPlanChange($group, $subscription, $plan);

        if ($subscription !== null) {
            $subscription->refresh();
        }

        $card = null;

        if ($group->hasStripeId()) {
            try {
                $card = $group->defaultPaymentMethod()?->asStripePaymentMethod()?->card;
            } catch (\Exception) {
                // Stripe PaymentMethod 取得不可時は null
            }
        }

        return [
            'plan' => $plan->value,
            'isSubscribed' => $group->subscribed($subscriptionType),
            'subscriptionStatus' => $subscription?->stripe_status,
            'subscriptionEndsAt' => $subscription?->ends_at?->toIso8601String(),
            'pendingPlanChange' => $pendingPlanChange,
            'pmType' => $group->pm_type,
            'pmLastFour' => $group->pm_last_four,
            'pmExpMonth' => $card?->exp_month,
            'pmExpYear' => $card?->exp_year,
        ];
    }

    /**
     * 予定されているプラン変更（解約予定など）を取り消してサブスクリプションを継続する
     * @param Group $group
     * @return void
     * @throws HttpException
     */
    public function resumeSubscription(Group $group): void
    {
        $subscriptionType = config('billing.subscription_type');
        $subscription = $group->subscription($subscriptionType);

        // サブスクリプションが存在しない場合はエラー
        if ($subscription === null) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.no_pending_plan_change'),
            );
        }

        $subscription->refresh();
        $plan = $group->plan ?? GroupPlan::FREE;

        // 解約予定がない場合はエラー
        if (! $this->isCancellationScheduled($group, $subscription, $plan)) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.no_pending_plan_change'),
            );
        }

        $subscription->refresh();

        // 猶予期間が終了している場合はエラー
        if (! $subscription->onGracePeriod()) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.no_pending_plan_change'),
            );
        }

        // サブスクリプションを継続する
        $subscription->resume();
    }

    /**
     * 予定されているプラン変更を取得する
     * @param Group $group
     * @param ?Subscription $subscription
     * @param GroupPlan $plan
     * @return array{nextPlan: string, changesAt: string}|null
     */
    private function getPendingPlanChange(
        Group $group,
        ?Subscription $subscription,
        GroupPlan $plan,
    ): ?array {
        if (! $this->isCancellationScheduled($group, $subscription, $plan)) {
            return null;
        }

        $changesAt = $subscription?->ends_at?->toIso8601String();

        if ($changesAt === null) {
            return null;
        }

        return [
            'nextPlan' => GroupPlan::FREE->value,
            'changesAt' => $changesAt,
        ];
    }

    /**
     * 解約予定のチェック
     * @param Group $group
     * @param ?Subscription $subscription
     * @param GroupPlan $plan
     * @return bool
     */
    private function isCancellationScheduled(
        Group $group,
        ?Subscription $subscription,
        GroupPlan $plan,
    ): bool {
        // サブスクリプションが存在しない、またはプランがスタンダードでない場合は false
        if ($subscription === null || $plan !== GroupPlan::STANDARD) {
            return false;
        }

        $subscriptionType = config('billing.subscription_type');

        if (! is_string($subscriptionType) || $subscriptionType === '' || ! $group->subscribed($subscriptionType)) {
            return false;
        }

        // 解約予定の場合は true
        if ($subscription->stripe_status === 'canceled' && $subscription->ends_at?->isFuture()) {
            return true;
        }

        // 有効なサブスクリプションの場合は false
        if (! in_array($subscription->stripe_status, ['active', 'trialing', 'past_due'], true)) {
            return false;
        }

        try {
            // Stripeのサブスクリプションを取得
            $stripeSubscription = $subscription->asStripeSubscription();

            // 解約予定の場合は true
            if ($stripeSubscription->cancel_at_period_end) {
                // ends_atを更新
                $this->syncEndsAtFromStripe($subscription, $stripeSubscription);

                return true;
            }

            // ends_atをクリア
            if ($subscription->ends_at !== null) {
                $subscription->forceFill(['ends_at' => null])->save();
            }

            return false;
        } catch (\Exception) {
            return $subscription->ends_at?->isFuture() ?? false;
        }
    }

    /**
     * ends_at が未設定の場合、Stripe の current_period_end（なければ cancel_at）をもとに更新
     * @param Subscription $subscription
     * @param \Stripe\Subscription $stripeSubscription
     * @return void
     */
    private function syncEndsAtFromStripe(Subscription $subscription, \Stripe\Subscription $stripeSubscription): void
    {
        if ($subscription->ends_at !== null) {
            return;
        }

        $timestamp = $this->extractEndsAtTimestamp($stripeSubscription);

        if ($timestamp === null) {
            return;
        }

        $endsAt = Carbon::createFromTimestamp($timestamp, config('app.timezone'));
        $subscription->forceFill(['ends_at' => $endsAt])->save();
    }

    /**
     * 請求履歴と次回お支払い予定を取得する
     * @param Group $group
     * @return array{
     *     upcomingInvoice: array{
     *         date: string,
     *         lines: array<int, array{description: string|null, quantity: int|null, amount: int}>,
     *         subtotal: int,
     *         subtotalExcludingTax: int,
     *         tax: int,
     *         total: int,
     *         amountDue: int,
     *     }|null,
     *     pastInvoices: array<int, array{id: string, date: string, total: int, invoiceUrl: string|null}>,
     * }
     */
    public function getInvoices(Group $group): array
    {
        $upcomingInvoice = null;
        $pastInvoices = [];

        if ($group->hasStripeId()) {
            try {
                $subscription = $group->subscription(config('billing.subscription_type'));
                $params = [];
                if ($subscription?->stripe_id) {
                    $params['subscription'] = $subscription->stripe_id;
                    $params['automatic_tax'] = ['enabled' => true];
                }
                $upcoming = $group->upcomingInvoice($params);
                if ($upcoming) {
                    $stripeInvoice = $upcoming->asStripeInvoice();
                    $tax = $this->extractInvoiceTaxAmount($stripeInvoice);
                    $total = $upcoming->rawTotal();
                    $subtotalExcludingTax = (int) (
                        $stripeInvoice->subtotal_excluding_tax
                        ?? $stripeInvoice->total_excluding_tax
                        ?? ($total - $tax)
                    );
                    $upcomingInvoice = [
                        'date' => $upcoming->date()->toIso8601String(),
                        'lines' => collect($upcoming->invoiceLineItems())->map(fn($line) => [
                            'description' => $line->description,
                            'quantity' => $line->quantity,
                            'amount' => (int) $line->amount,
                        ])->toArray(),
                        'subtotal' => (int) ($stripeInvoice->subtotal ?? 0),
                        'subtotalExcludingTax' => $subtotalExcludingTax,
                        'tax' => $tax,
                        'total' => $total,
                        'amountDue' => $upcoming->rawAmountDue(),
                    ];
                }
            } catch (\Exception) {
                // サブスク未加入時は upcoming が取得できない
            }

            $pastInvoices = $group->invoices()->map(fn($invoice) => [
                'id' => $invoice->id,
                'date' => $invoice->date()->toIso8601String(),
                'total' => $invoice->rawTotal(),
                'invoiceUrl' => $invoice->asStripeInvoice()->hosted_invoice_url,
            ])->toArray();
        }

        return [
            'upcomingInvoice' => $upcomingInvoice,
            'pastInvoices' => $pastInvoices,
        ];
    }

    /**
     * フロントエンドのコールバックURLを返す
     * @return array{success: string, cancel: string}
     */
    private function frontendCallbackUrls(): array
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return [
            'success' => $frontendUrl . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel' => $frontendUrl . '/settings/billing?checkout=canceled',
        ];
    }

    /**
     * Stripe Customer を作成する
     * @param Group $group
     * @param User $user
     * @return void
     * @throws HttpException
     */
    private function ensureStripeCustomer(Group $group, User $user): void
    {
        $group->createOrGetStripeCustomer([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'group_id' => $group->id,
            ],
        ]);

        $group->refresh();
    }

    /**
     * 価格IDを取得する
     * @param string $configKey
     * @return string
     * @throws HttpException
     */
    private function priceId(string $configKey): string
    {
        $priceId = config("billing.price_ids.{$configKey}");

        if (! is_string($priceId) || $priceId === '') {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                __('api.billing.price_not_configured'),
            );
        }

        return $priceId;
    }

    /**
     * Stripe Invoice から税額を取得する
     *
     * @param \Stripe\Invoice|object $stripeInvoice
     * @return int 税額（最小通貨単位）
     */
    private function extractInvoiceTaxAmount(object $stripeInvoice): int
    {
        if (! empty($stripeInvoice->total_taxes)) {
            return (int) collect($stripeInvoice->total_taxes)->sum(
                fn($tax) => (int) ($tax->amount ?? 0),
            );
        }

        return (int) ($stripeInvoice->tax ?? 0);
    }
}
