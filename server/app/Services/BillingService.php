<?php

namespace App\Services;

use App\Enums\BillingPackType;
use App\Enums\BillingSubscriptionType;
use App\Enums\GroupPlan;
use App\Enums\HttpStatusCode;
use App\Models\BillingHistory;
use App\Models\Group;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Square\Exceptions\SquareApiException;
use Square\Types\Card;
use Square\Types\Payment;
use Square\Types\Subscription as SquareSubscription;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BillingService
{
    public function __construct(
        private readonly SquareBillingClient $square,
        private readonly GroupPlanService $groupPlanService,
        private readonly AiUsageService $aiUsageService,
    ) {}

    /**
     * カードトークンで Square Customer を作成またはカードを更新する。
     *
     * @throws SquareApiException
     */
    public function createOrUpdateSquareCustomer(
        Group $group,
        User $user,
        string $cardToken,
        ?string $verificationToken = null,
    ): void {
        if ($group->hasSquareCustomer()) {
            $card = $this->square->addCustomerCardFromToken(
                $group->square_customer_id,
                $cardToken,
                $group->square_card_id,
                $verificationToken,
            );
        } else {
            $customer = $this->square->createCustomer([
                'emailAddress' => $user->email,
                'note' => $user->name,
                'referenceId' => $group->id,
            ]);

            $customerId = $customer->getId();
            if ($customerId === null) {
                throw new SquareApiException('Square customer id is missing', 502, null);
            }

            $card = $this->square->createCard($customerId, $cardToken, $verificationToken);

            $group->forceFill([
                'square_customer_id' => $customerId,
            ])->save();
        }

        $this->syncPaymentMethodFromCard($group, $card);
        $group->refresh();
    }

    /**
     * Square Card の情報を Group に同期する。
     */
    private function syncPaymentMethodFromCard(Group $group, Card $card): void
    {
        $group->forceFill([
            'square_card_id' => $card->getId(),
            'pm_type' => $this->formatPmType($card->getCardBrand()),
            'pm_last_four' => $card->getLast4(),
            'pm_exp_month' => $card->getExpMonth(),
            'pm_exp_year' => $card->getExpYear(),
        ])->save();
    }

    private function formatPmType(?string $cardBrand): ?string
    {
        if ($cardBrand === null || $cardBrand === '') {
            return 'card';
        }

        return match (strtoupper($cardBrand)) {
            'VISA' => 'Visa',
            'MASTERCARD' => 'Mastercard',
            'AMERICAN_EXPRESS' => 'American Express',
            'DISCOVER' => 'Discover',
            'JCB' => 'JCB',
            default => ucfirst(strtolower($cardBrand)),
        };
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
    public function updateCard(
        Group $group,
        User $user,
        string $cardToken,
        ?string $verificationToken = null,
    ): array {
        try {
            $this->createOrUpdateSquareCustomer($group, $user, $cardToken, $verificationToken);
        } catch (SquareApiException $e) {
            throw $this->squareHttpException($e, __('api.billing.card_update_failed'));
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
        if (! $group->hasSquareCustomer()) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.no_billing_account'),
            );
        }

        try {
            $cardId = $group->square_card_id;

            if (is_string($cardId) && $cardId !== '') {
                $this->square->disableCard($cardId);
            }

            $group->forceFill([
                'square_card_id' => null,
                'pm_type' => null,
                'pm_last_four' => null,
                'pm_exp_month' => null,
                'pm_exp_year' => null,
            ])->save();
        } catch (SquareApiException $e) {
            throw $this->squareHttpException($e, __('api.billing.card_delete_failed'));
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
        ?string $verificationToken = null,
    ): array {
        if ($group->isSubscribed()) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.billing.already_subscribed'),
            );
        }

        $groupPlan = $subscriptionType->groupPlan();
        $planVariationId = $subscriptionType->squarePlanVariationId();

        try {
            if ($cardToken !== null && $cardToken !== '') {
                $this->createOrUpdateSquareCustomer($group, $user, $cardToken, $verificationToken);
            } elseif (! $group->hasSquareCustomer()) {
                throw new HttpException(
                    HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                    __('api.billing.no_payment_method'),
                );
            }

            $group->refresh();
            $cardId = $group->square_card_id;
            if (! is_string($cardId) || $cardId === '') {
                throw new HttpException(
                    HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                    __('api.billing.no_payment_method'),
                );
            }

            $squareSubscription = $this->square->createSubscription([
                'customerId' => (string) $group->square_customer_id,
                'planVariationId' => $planVariationId,
                'cardId' => $cardId,
            ]);

            // 申込応答は charged_through_date が空、または直後に ACTIVE でなくなることがある。
            // 再取得して請求周期とステータスを補完する。再取得失敗時だけ初回応答を保存する。
            try {
                $latestSubscription = $this->square->retrieveSubscription(
                    (string) $squareSubscription->getId(),
                );
                $this->syncLocalSubscription($group, $latestSubscription);

                // ACTIVE 以外は開始失敗として FREE に戻す
                if (strtolower((string) ($latestSubscription->getStatus() ?? '')) !== 'active') {
                    $this->groupPlanService->update($group, GroupPlan::FREE);
                    throw new HttpException(
                        HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                        __('api.billing.subscribe_failed'),
                    );
                }

                $squareSubscription = $latestSubscription;
            } catch (SquareApiException) {
                $this->syncLocalSubscription($group, $squareSubscription);
            }

            $this->groupPlanService->update($group, $groupPlan);

            // 初回請求周期の終了日を ai_usage_reset_at に反映（月間残数の満タン化は update() 側）
            $periodEnd = SubscriptionHelper::periodEnd(
                $squareSubscription->getChargedThroughDate(),
                $squareSubscription->getCanceledDate(),
            );
            if ($periodEnd !== null) {
                $this->aiUsageService->renewBillingPeriod($group, $periodEnd);
            }
        } catch (SquareApiException $e) {
            throw $this->squareHttpException($e, __('api.billing.subscribe_failed'));
        }

        return $this->getBillingStatus($group->fresh());
    }

    /**
     * サブスクリプションを期間終了時に解約する。
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
            $squareSubscription = $this->square->cancelSubscription($subscription->square_subscription_id);
            $this->syncLocalSubscription($group, $squareSubscription);
        } catch (SquareApiException $e) {
            throw $this->squareHttpException($e, __('api.billing.cancel_failed'));
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
        ?string $verificationToken = null,
    ): array {
        $amount = $packType->squarePackPrice();

        try {
            if ($cardToken !== null && $cardToken !== '') {
                $this->createOrUpdateSquareCustomer($group, $user, $cardToken, $verificationToken);
            } elseif (! $group->hasSquareCustomer()) {
                throw new HttpException(
                    HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                    __('api.billing.no_payment_method'),
                );
            }

            $group->refresh();
            $cardId = $group->square_card_id;
            if (! is_string($cardId) || $cardId === '') {
                throw new HttpException(
                    HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                    __('api.billing.no_payment_method'),
                );
            }

            $payment = $this->square->createPayment([
                'sourceId' => $cardId,
                'customerId' => (string) $group->square_customer_id,
                'amount' => $amount,
                'currency' => 'JPY',
                'note' => $this->encodePackPaymentNote($group, $packType),
                'referenceId' => 'pack:' . $packType->value,
            ]);

            BillingHistory::create([
                'group_id' => $group->id,
                'square_payment_id' => (string) $payment->getId(),
                'type' => 'pack',
                'description' => __('api.billing.pack_charge_line', ['label' => $packType->label()]),
            ]);

            $this->addPackCredits($group, $packType->credits());
        } catch (SquareApiException $e) {
            throw $this->squareHttpException($e, __('api.billing.purchase_pack_failed'));
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
     * 解約予定を取り消し、サブスクリプションを継続する（猶予期間中のみ）。
     * AI 利用枠（ai_usage_reset_at / ai_monthly_remaining）は変更しない。
     *
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
            $squareSubscription = $this->square->resumeSubscription($subscription->square_subscription_id);
            $this->syncLocalSubscription($group, $squareSubscription);
        } catch (SquareApiException $e) {
            throw $this->squareHttpException($e, __('api.billing.resume_failed'));
        }
    }

    /**
     * 解約予定（STANDARD → FREE）を返す。
     *
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
     * 解約予定（周期終了後に FREE へ落ちる状態）かどうか。
     */
    private function isCancellationScheduled(
        ?Subscription $subscription,
        GroupPlan $plan,
    ): bool {
        // サブスクリプションが存在しない、またはプランがスタンダードでない場合は false
        if ($subscription === null || $plan !== GroupPlan::STANDARD) {
            return false;
        }

        // 解約済みだが契約終了日（ends_at）まで利用可能な場合
        if ($subscription->status === 'canceled' && $subscription->ends_at?->isFuture()) {
            return true;
        }

        // active のまま ends_at が未来（同期タイミング等の残存ケース）
        if ($subscription->isActive() && $subscription->ends_at?->isFuture()) {
            return true;
        }

        // それ以外は false
        return false;
    }

    /**
     * Square Subscription をローカル DB に反映する。
     */
    private function syncLocalSubscription(Group $group, SquareSubscription $squareSubscription): void
    {
        SubscriptionHelper::upsertSubscriptionRecord(
            $group,
            SubscriptionHelper::normalizeFromSquare($squareSubscription),
        );
    }

    /**
     * 請求履歴と次回お支払い予定を取得する。
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

        if (! $group->hasSquareCustomer()) {
            return [
                'upcomingInvoice' => null,
                'pastInvoices' => [],
            ];
        }

        try {
            $subscription = $group->subscription();

            // 周期終了日が未設定の場合は再取得する
            if (
                $subscription?->isActive()
                && $subscription->current_period_end === null
                && is_string($subscription->square_subscription_id)
            ) {
                try {
                    $squareSubscription = $this->square->retrieveSubscription(
                        $subscription->square_subscription_id,
                    );
                    $this->syncLocalSubscription($group, $squareSubscription);
                    $subscription->refresh();

                    // 再取得で継続中でなくなっていたらプランを FREE に戻す
                    if (! $subscription->isActive()) {
                        $this->groupPlanService->update($group, GroupPlan::FREE);
                    }
                } catch (SquareApiException) {
                    // 補完失敗は無視
                }
            }

            // 次回請求は継続課金中のみ。解約予定（isSubscribed だが !isActive）では表示しない。
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

            $payments = $this->square->listPayments([
                'customerId' => (string) $group->square_customer_id,
                'since' => $chargeSince,
                'limit' => $chargeHistoryLimit,
            ]);

            foreach ($payments as $payment) {
                if (! $this->square->isPaymentCompleted($payment)) {
                    continue;
                }

                $createdAt = $this->paymentCreatedAt($payment);
                if ($createdAt === null) {
                    continue;
                }

                $pastInvoices[] = [
                    'id' => (string) $payment->getId(),
                    'date' => $createdAt->toIso8601String(),
                    'description' => $this->buildPaymentDescription($payment),
                    'total' => $this->paymentTotalAmount($payment),
                ];
            }
        } catch (SquareApiException) {
            // Square 取得不可時は upcoming は DB 由来のまま・pastInvoices=取得できた分のみ
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

        $subscriptionType = BillingSubscriptionType::subscriptionTypeFromSquarePlanVariationId(
            $subscription->square_plan_variation_id,
        ) ?? BillingSubscriptionType::STANDARD;

        $amount = $subscriptionType->squareSubscriptionAmount();

        return [
            'date' => $periodEnd->toIso8601String(),
            'lines' => [
                [
                    'description' => $subscriptionType->label(),
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
     * パック購入 Payment の note（JSON）を組み立てる。
     */
    private function encodePackPaymentNote(Group $group, BillingPackType $packType): string
    {
        return json_encode([
            'type' => 'pack',
            'group_id' => (string) $group->id,
            'pack_type' => $packType->value,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Square Payment の請求内容ラベルを返す。billing_histories を参照し、未登録なら中立なフォールバックを返す。
     */
    private function buildPaymentDescription(Payment $payment): string
    {
        $paymentId = (string) $payment->getId();
        $history = BillingHistory::where('square_payment_id', $paymentId)->first();
        if ($history !== null) {
            return $history->description;
        }

        return __('api.billing.unknown_charge_line');
    }

    /**
     * Square Payment の作成日を Carbon に変換する。
     */
    private function paymentCreatedAt(Payment $payment): ?Carbon
    {
        $createdAt = $payment->getCreatedAt();
        if ($createdAt === null || $createdAt === '') {
            return null;
        }

        return Carbon::parse($createdAt, config('app.timezone'));
    }

    /**
     * Square Payment の合計金額を返す。
     */
    private function paymentTotalAmount(Payment $payment): int
    {
        $totalMoney = $payment->getTotalMoney() ?? $payment->getAmountMoney();
        $amount = $totalMoney?->getAmount();

        return is_int($amount) ? $amount : 0;
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

    private function squareHttpException(SquareApiException $error, string $message): HttpException
    {
        $status = $error->getStatusCode();
        $code = $status >= 400 && $status < 600
            ? $status
            : HttpStatusCode::UNPROCESSABLE_ENTITY->value;

        return new HttpException($code, $message);
    }
}
