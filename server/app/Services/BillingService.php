<?php

namespace App\Services;

use App\Enums\BillingPackType;
use App\Enums\BillingSubscriptionType;
use App\Enums\GroupPlan;
use App\Enums\HttpStatusCode;
use App\Models\Group;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Payjp\Error\Base as PayjpError;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BillingService
{
    public function __construct(
        private readonly PayjpBillingClient $payjp,
        private readonly GroupPlanService $groupPlanService,
        private readonly AiUsageService $aiUsageService,
    ) {}

    /**
     * カードトークンで PAY.JP Customer を作成またはカードを更新する。
     *
     * @throws PayjpError
     */
    public function createOrUpdatePayjpCustomer(Group $group, User $user, string $cardToken): void
    {
        if ($group->hasPayjpCustomer()) {
            $customer = $this->payjp->addCustomerCardFromToken(
                $group->payjp_customer_id,
                $cardToken,
            );
        } else {
            $customer = $this->payjp->createCustomer([
                'email' => $user->email,
                'description' => $user->name,
                'card' => $cardToken,
                'metadata' => [
                    'group_id' => $group->id,
                ],
            ]);

            $group->forceFill([
                'payjp_customer_id' => $customer->id,
            ])->save();
        }

        $this->syncPaymentMethodFromCustomer($group, $customer);
        $group->refresh();
    }

    /**
     * PAY.JP Customer のデフォルトカード情報を Group に同期する。
     */
    private function syncPaymentMethodFromCustomer(Group $group, object $customer): void
    {
        $card = $this->getDefaultCard($customer);

        if ($card === null) {
            return;
        }

        $group->forceFill([
            'pm_type' => $card->brand ?? $card->type ?? 'card',
            'pm_last_four' => isset($card->last4) ? (string) $card->last4 : null,
            'pm_exp_month' => isset($card->exp_month) ? (int) $card->exp_month : null,
            'pm_exp_year' => isset($card->exp_year) ? (int) $card->exp_year : null,
        ])->save();
    }

    /**
     * デフォルトカードを取得する
     * @param object $customer
     * @return object|null
     */
    private function getDefaultCard(object $customer): ?object
    {
        $defaultCardId = $customer->default_card ?? null;
        $cards = $customer->cards->data ?? [];

        foreach ($cards as $card) {
            if ($defaultCardId !== null && ($card->id ?? null) !== $defaultCardId) {
                continue;
            }

            return $card;
        }

        return null;
    }

    /**
     * カードトークンで登録カードを更新する。
     *
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
     *
     * @throws HttpException
     */
    public function updateCard(Group $group, User $user, string $cardToken): array
    {
        try {
            $this->createOrUpdatePayjpCustomer($group, $user, $cardToken);
        } catch (PayjpError $e) {
            throw $this->payjpHttpException($e, __('api.billing.card_update_failed'));
        }

        return $this->getBillingStatus($group->fresh());
    }

    /**
     * 登録カードを削除する。
     *
     * @throws HttpException
     */
    public function deleteCard(Group $group): void
    {
        if (! $group->hasPayjpCustomer()) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.no_billing_account'),
            );
        }

        try {
            $customer = $this->payjp->retrieveCustomer($group->payjp_customer_id);
            $card = $this->getDefaultCard($customer);
            $cardId = $card->id ?? null;

            if (is_string($cardId) && $cardId !== '') {
                $this->payjp->deleteCustomerCard($group->payjp_customer_id, $cardId);
            }

            $group->forceFill([
                'pm_type' => null,
                'pm_last_four' => null,
                'pm_exp_month' => null,
                'pm_exp_year' => null,
            ])->save();
        } catch (PayjpError $e) {
            throw $this->payjpHttpException($e, __('api.billing.card_delete_failed'));
        }
    }

    /**
     * 登録済みカード（またはトークンでカード登録後）でサブスクリプションを開始する。
     *
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
     *
     * @throws HttpException
     */
    public function createSubscription(
        Group $group,
        User $user,
        BillingSubscriptionType $subscriptionType,
        ?string $cardToken = null,
    ): array {
        if ($group->isSubscribed()) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.already_subscribed'),
            );
        }

        $groupPlan = $subscriptionType->groupPlan();
        $planId = $subscriptionType->payjpSubscriptionPlanId();

        try {
            if ($cardToken !== null && $cardToken !== '') {
                $this->createOrUpdatePayjpCustomer($group, $user, $cardToken);
            } elseif (! $group->hasPayjpCustomer()) {
                throw new HttpException(
                    HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                    __('api.billing.no_payment_method'),
                );
            }

            $payjpSubscription = $this->payjp->createSubscription([
                'customer' => $group->payjp_customer_id,
                'plan' => $planId,
                'metadata' => [
                    'group_id' => $group->id,
                ],
            ]);

            $this->syncLocalSubscription($group, $payjpSubscription);
            $this->groupPlanService->update($group, $groupPlan);

            $periodEnd = $this->timestampToCarbon($payjpSubscription->current_period_end ?? null);
            // 期間終了日がある場合は猶予期間を更新
            if ($periodEnd !== null) {
                $this->aiUsageService->renewBillingPeriod($group, $periodEnd);
            }
        } catch (PayjpError $e) {
            throw $this->payjpHttpException($e, __('api.billing.subscribe_failed'));
        }

        return $this->getBillingStatus($group->fresh());
    }

    /**
     * サブスクリプションを期間終了時に解約する。
     *
     * PAY.JP の cancel は status を canceled にし、current_period_end まで利用できる。
     *
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
     *
     * @throws HttpException
     */
    public function cancelSubscription(Group $group): array
    {
        if (! $group->isSubscribed()) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.no_active_subscription'),
            );
        }

        $subscription = $group->subscription();

        try {
            $payjpSubscription = $this->payjp->cancelSubscription($subscription->payjp_subscription_id);
            $this->syncLocalSubscription($group, $payjpSubscription);
        } catch (PayjpError $e) {
            throw $this->payjpHttpException($e, __('api.billing.cancel_failed'));
        }

        return $this->getBillingStatus($group->fresh());
    }

    /**
     * 登録済みカード（またはトークンでカード登録後）で買い切りパックを購入する。
     *
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
     *
     * @throws HttpException
     */
    public function purchasePack(
        Group $group,
        User $user,
        BillingPackType $packType,
        ?string $cardToken = null,
    ): array {
        $amount = $packType->payjpPackPrice();

        try {
            if ($cardToken !== null && $cardToken !== '') {
                $this->createOrUpdatePayjpCustomer($group, $user, $cardToken);
            } elseif (! $group->hasPayjpCustomer()) {
                throw new HttpException(
                    HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                    __('api.billing.no_payment_method'),
                );
            }

            $this->payjp->createCharge([
                'amount' => $amount,
                'currency' => 'jpy',
                'customer' => $group->payjp_customer_id,
                'metadata' => [
                    'type' => 'pack',
                    'group_id' => $group->id,
                    'pack_type' => $packType->value,
                    'credits' => (string) $packType->credits(),
                ],
            ]);

            $this->addPackCredits($group, $packType->credits());
        } catch (PayjpError $e) {
            throw $this->payjpHttpException($e, __('api.billing.purchase_pack_failed'));
        }

        return $this->getBillingStatus($group->fresh());
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
        $subscription = $group->subscription();
        $plan = $group->plan ?? GroupPlan::FREE;

        $pendingPlanChange = $this->getPendingPlanChange($subscription, $plan);
        $cardExpiration = $this->getCardExpiration($group);

        return [
            'plan' => $plan->value,
            'isSubscribed' => $group->isSubscribed(),
            'subscriptionStatus' => $subscription?->status,
            'subscriptionEndsAt' => $subscription?->ends_at?->toIso8601String(),
            'pendingPlanChange' => $pendingPlanChange,
            'pmType' => $group->pm_type,
            'pmLastFour' => $group->pm_last_four,
            'pmExpMonth' => $cardExpiration['expMonth'],
            'pmExpYear' => $cardExpiration['expYear'],
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
        $subscription = $group->subscription();

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
        if (! $this->isCancellationScheduled($subscription, $plan)) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.no_pending_plan_change'),
            );
        }

        // 猶予期間が終了している場合はエラー
        if (! $subscription->onGracePeriod()) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.no_pending_plan_change'),
            );
        }

        try {
            // キャンセル済みのサブスクリプションを再開する
            $payjpSubscription = $this->payjp->resumeSubscription($subscription->payjp_subscription_id);
            $this->syncLocalSubscription($group, $payjpSubscription);
        } catch (PayjpError $e) {
            throw $this->payjpHttpException($e, __('api.billing.resume_failed'));
        }
    }

    /**
     * 予定されているプラン変更を取得する
     * @param ?Subscription $subscription
     * @param GroupPlan $plan
     * @return array{nextPlan: string, changesAt: string}|null
     */
    private function getPendingPlanChange(
        ?Subscription $subscription,
        GroupPlan $plan,
    ): ?array {
        if (! $this->isCancellationScheduled($subscription, $plan)) {
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
     * @param ?Subscription $subscription
     * @param GroupPlan $plan
     * @return bool
     */
    private function isCancellationScheduled(
        ?Subscription $subscription,
        GroupPlan $plan,
    ): bool {
        // サブスクリプションが存在しない、またはプランがスタンダードでない場合は false
        if ($subscription === null || $plan !== GroupPlan::STANDARD) {
            return false;
        }

        // キャンセルされていて、猶予期間がある場合は true
        if ($subscription->status === 'canceled' && $subscription->ends_at?->isFuture()) {
            return true;
        }

        // アクティブで、猶予期間がある場合は true
        if ($subscription->isActive() && $subscription->ends_at?->isFuture()) {
            return true;
        }

        // それ以外は false
        return false;
    }

    /**
     * PAY.JP Subscription オブジェクトをローカル DB に反映する。
     */
    private function syncLocalSubscription(Group $group, object $payjpSubscription): void
    {
        $endsAt = $this->getSubscriptionEndsAt($payjpSubscription);

        Subscription::query()->updateOrCreate(
            ['payjp_subscription_id' => (string) $payjpSubscription->id],
            [
                'group_id' => $group->id,
                'payjp_plan_id' => $this->extractPayjpSubscriptionPlanId($payjpSubscription),
                'status' => (string) ($payjpSubscription->status ?? 'active'),
                'trial_ends_at' => $this->timestampToCarbon($payjpSubscription->trial_end ?? null),
                'ends_at' => $endsAt,
                'current_period_start' => $this->timestampToCarbon($payjpSubscription->current_period_start ?? null),
                'current_period_end' => $this->timestampToCarbon($payjpSubscription->current_period_end ?? null),
                'canceled_at' => $this->timestampToCarbon($payjpSubscription->canceled_at ?? null),
                'paused_at' => $this->timestampToCarbon($payjpSubscription->paused_at ?? null),
            ],
        );
    }

    /**
     * PAY.JP の subscription オブジェクトからプラン ID を取得する。
     * @param object $payjpSubscription
     * @return ?string
     */
    private function extractPayjpSubscriptionPlanId(object $payjpSubscription): ?string
    {
        $plan = $payjpSubscription->plan ?? null;

        if (is_object($plan)) {
            return isset($plan->id) ? (string) $plan->id : null;
        }

        if (is_string($plan) && $plan !== '') {
            return $plan;
        }

        return null;
    }

    /**
     * 解約予定・解約済み猶予期間の終了日（ローカル `ends_at`）を PAY.JP レスポンスから求める。
     *
     * PAY.JP の subscription オブジェクトに `ended_at` はなく、キャンセル後も
     * `current_period_end` まで利用可能（公式: キャンセルは current_period_end 以降に削除）。
     *
     * @param object $payjpSubscription PAY.JP Subscription API レスポンス
     */
    private function getSubscriptionEndsAt(object $payjpSubscription): ?Carbon
    {
        $status = (string) ($payjpSubscription->status ?? '');

        // キャンセルされていない場合は null
        if ($status !== 'canceled' && ($payjpSubscription->canceled_at ?? null) === null) {
            return null;
        }

        // キャンセルされている場合は `current_period_end` を返す
        return $this->timestampToCarbon($payjpSubscription->current_period_end ?? null);
    }

    /**
     * タイムスタンプを Carbon に変換する
     * @param mixed $timestamp
     * @return ?Carbon
     */
    private function timestampToCarbon(mixed $timestamp): ?Carbon
    {
        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        return Carbon::createFromTimestamp((int) $timestamp, config('app.timezone'));
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
     *     pastInvoices: array<int, array{id: string, date: string, description: string, total: int}>,
     * }
     */
    public function getInvoices(Group $group): array
    {
        $upcomingInvoice = null;
        $pastInvoices = [];

        if (! $group->hasPayjpCustomer()) {
            return [
                'upcomingInvoice' => null,
                'pastInvoices' => [],
            ];
        }

        try {
            $subscription = $group->subscription();
            // 次回請求は継続課金中のみ。解約猶予（isSubscribed だが !isActive）では表示しない。
            if ($group->isSubscribed() && $subscription?->isActive()) {
                $upcomingInvoice = $this->buildUpcomingInvoice($subscription);
            }

            $chargeHistoryLimit = (int) config('billing.charge_history_limit', 100);
            $chargeHistoryLimit = max(1, min(100, $chargeHistoryLimit));

            $historyYears = (int) config('billing.charge_history_since_years', 2);
            $historyYears = max(1, $historyYears);
            $chargeSince = Carbon::now(config('app.timezone'))
                ->subYears($historyYears)
                ->getTimestamp();

            $charges = $this->payjp->listCharges([
                'customer' => $group->payjp_customer_id,
                'since' => $chargeSince,
                'limit' => $chargeHistoryLimit,
            ]);

            foreach ($charges as $charge) {
                if (($charge->paid ?? false) !== true) {
                    continue;
                }

                $created = $this->timestampToCarbon($charge->created ?? null);
                if ($created === null) {
                    continue;
                }

                $pastInvoices[] = [
                    'id' => (string) $charge->id,
                    'date' => $created->toIso8601String(),
                    'description' => $this->buildChargeDescription($charge),
                    'total' => (int) ($charge->amount ?? 0),
                ];
            }
        } catch (PayjpError) {
            // PAY.JP 取得不可時は upcoming=null・pastInvoices=取得できた分のみ
        }

        return [
            'upcomingInvoice' => $upcomingInvoice,
            'pastInvoices' => $pastInvoices,
        ];
    }

    /**
     * ローカル Subscription から次回お支払い予定を構築する。
     *
     * @return array{
     *     date: string,
     *     lines: array<int, array{description: string|null, quantity: int|null, amount: int}>,
     *     subtotal: int,
     *     subtotalExcludingTax: int,
     *     tax: int,
     *     total: int,
     *     amountDue: int,
     * }|null
     */
    private function buildUpcomingInvoice(Subscription $subscription): ?array
    {
        $periodEnd = $subscription->current_period_end;
        if ($periodEnd === null) {
            return null;
        }

        $amount = BillingSubscriptionType::STANDARD->payjpSubscriptionAmount();

        return [
            'date' => $periodEnd->toIso8601String(),
            'lines' => [
                [
                    'description' => __('api.billing.standard_plan_line'),
                    'quantity' => 1,
                    'amount' => $amount,
                ],
            ],
            'subtotal' => $amount,
            'subtotalExcludingTax' => $amount,
            'tax' => 0,
            'total' => $amount,
            'amountDue' => $amount,
        ];
    }

    /**
     * PAY.JP Charge の metadata から請求内容を組み立てる。
     *
     * @param  object  $charge  PAY.JP Charge オブジェクト
     */
    private function buildChargeDescription(object $charge): string
    {
        $metadata = $charge->metadata ?? null;
        $type = $metadata->type ?? null;

        if ($type === 'pack') {
            $packType = BillingPackType::tryFrom($metadata->pack_type ?? '');
            if ($packType !== null) {
                return __('api.billing.pack_charge_line', ['label' => $packType->label()]);
            }
        }

        return __('api.billing.standard_plan_line');
    }

    /**
     * 登録カードの有効期限を Group から取得する。
     *
     * @return array{expMonth: int|null, expYear: int|null}
     */
    private function getCardExpiration(Group $group): array
    {
        return [
            'expMonth' => $group->pm_exp_month !== null ? (int) $group->pm_exp_month : null,
            'expYear' => $group->pm_exp_year !== null ? (int) $group->pm_exp_year : null,
        ];
    }

    private function addPackCredits(Group $group, int $credits): void
    {
        DB::transaction(function () use ($group, $credits): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);
            $lockedGroup->ai_pack_remaining += $credits;
            $lockedGroup->save();
        });
    }

    private function payjpHttpException(PayjpError $error, string $message): HttpException
    {
        $status = $error->getHttpStatus();
        $code = $status >= 400 && $status < 600
            ? $status
            : HttpStatusCode::UNPROCESSABLE_ENTITY->value;

        return new HttpException($code, $message);
    }
}
