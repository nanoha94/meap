<?php

namespace App\Services;

use App\Enums\BillingSubscriptionType;
use App\Enums\GroupPlan;
use App\Models\BillingHistory;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Square\Exceptions\SquareApiException;
use Square\Types\Payment;

class BillingWebhookService
{
    public function __construct(
        private readonly AiUsageService $aiUsageService,
        private readonly GroupPlanService $groupPlanService,
        private readonly SquareBillingClient $square,
    ) {}

    /**
     * invoice.payment_made（サブスク請求成功）で Group.plan を更新し、月間利用回数を新周期にリセットする。
     *
     * プランは Retrieve したサブスクリプションの plan variation から決める。
     * 利用回数のリセットは charged_through_date（請求周期終了日）が取れたときに行う。
     * 請求履歴表示用に決済時点の description・金額・決済日時を billing_histories に保存する。
     */
    public function handleInvoicePaymentMade(array $payload): void
    {
        $eventId = $this->eventIdFromPayload($payload);

        $this->oncePerEvent($eventId, function () use ($payload, $eventId): void {
            $invoice = $this->invoiceFromPayload($payload);
            if ($invoice === null) {
                return;
            }

            $subscriptionId = $invoice['subscription_id'] ?? null;
            if (! is_string($subscriptionId) || $subscriptionId === '') {
                return;
            }

            try {
                $squareSubscription = $this->square->retrieveSubscription($subscriptionId);
            } catch (SquareApiException $exception) {
                Log::warning('Square invoice.payment_made: failed to retrieve subscription.', [
                    'event_id' => $eventId,
                    'subscription_id' => $subscriptionId,
                    'status_code' => $exception->getCode(),
                ]);

                return;
            }

            $subscription = SubscriptionHelper::normalizeFromSquare($squareSubscription);

            $group = Group::findBySquareCustomerId($subscription['customer_id'] ?? null);
            if ($group === null) {
                return;
            }

            $this->updateGroupPlanFromSquareSubscription($group, $subscription);

            $this->recordSubscriptionBillingHistory($group, $invoice, $subscription, $eventId);

            // 請求周期終了日が取れたとき、月間利用回数を新周期にリセットする
            $periodEnd = SubscriptionHelper::periodEnd(
                $subscription['charged_through_date'] ?? null,
                $subscription['canceled_date'] ?? null,
            );
            if ($periodEnd !== null) {
                $this->aiUsageService->renewBillingPeriod($group, $periodEnd);
            }
        });
    }

    /**
     * subscription.updated で Subscriptions テーブルと Group.plan を同期する。
     * 解約・停止も status の変更としてこのイベントに含まれる。
     */
    public function handleSubscriptionUpdated(array $payload): void
    {
        $subscription = $this->subscriptionFromPayload($payload);
        if ($subscription === null) {
            return;
        }

        $group = Group::findBySquareCustomerId($subscription['customer_id'] ?? null);
        if ($group === null) {
            return;
        }

        SubscriptionHelper::upsertSubscriptionRecord($group, $subscription);
        $this->syncGroupPlanFromSubscription($group, $subscription);
    }

    /**
     * payment.updated の支払い失敗はログのみ残す。
     * パック付与は購入 API の成功時に完了しており、ここではプランも残数も変えない。
     */
    public function handlePaymentUpdated(array $payload): void
    {
        $payment = $this->paymentFromPayload($payload);
        if ($payment === null) {
            return;
        }

        $status = strtoupper((string) ($payment['status'] ?? ''));
        if ($status !== 'FAILED') {
            return;
        }

        Log::warning('Square payment.updated webhook received for failed payment.', [
            'event_id' => $this->eventIdFromPayload($payload),
            'payment_id' => $payment['id'] ?? null,
        ]);
    }

    /**
     * サブスクリプション status に応じて Group.plan（利用権限）を同期する。
     *
     * ACTIVE は plan variation から有料プランを付与する。
     * PAUSED は即時に、CANCELED / DEACTIVATED は請求周期終了後（終了日不明を含む）に FREE に戻す。
     * PENDING などそれ以外は変更しない。
     *
     * @param  array<string, mixed>  $subscription
     */
    private function syncGroupPlanFromSubscription(Group $group, array $subscription): void
    {
        // ACTIVE の場合は有料プランを付与する
        if ($this->grantsPaidPlan($subscription)) {
            $this->updateGroupPlanFromSquareSubscription($group, $subscription);

            return;
        }

        // 解約・停止時は FREE に戻す
        if ($this->shouldRevokePlan($subscription)) {
            $this->groupPlanService->update($group, GroupPlan::FREE);
        }
    }

    /**
     * plan variation ID が既知の有料プランなら Group.plan を更新する。
     * 未登録の ID のときは何もしない。
     *
     * @param  array<string, mixed>  $subscription
     */
    private function updateGroupPlanFromSquareSubscription(Group $group, array $subscription): void
    {
        $planVariationId = $subscription['plan_variation_id'] ?? null;
        $plan = BillingSubscriptionType::groupPlanFromSquarePlanVariationId(
            is_string($planVariationId) ? $planVariationId : null,
        );

        if ($plan !== null) {
            $this->groupPlanService->update($group, $plan);
        }
    }

    /**
     * サブスク請求成功時に、決済時点の description・金額・決済日時を billing_histories に保存する。
     *
     * 金額と決済日時は Square Payment を優先し、取得できないときは Invoice ペイロードにフォールバックする。
     * 料金定数は使わない。どちらからも金額が取れないときは amount / paid_at を更新せず、事前記録を残す。
     *
     * @param  array<string, mixed>  $invoice
     * @param  array<string, mixed>  $subscription
     */
    private function recordSubscriptionBillingHistory(
        Group $group,
        array $invoice,
        array $subscription,
        string $eventId,
    ): void {
        $paymentId = $this->square->paymentIdFromInvoicePayload($invoice);
        if ($paymentId === null) {
            $this->logMissingPaymentIdFromInvoice($invoice, $eventId);

            return;
        }

        $planVariationId = $subscription['plan_variation_id'] ?? null;
        $subscriptionType = BillingSubscriptionType::subscriptionTypeFromSquarePlanVariationId(
            is_string($planVariationId) ? $planVariationId : null,
        );
        if ($subscriptionType === null) {
            return;
        }

        $attributes = [
            'group_id' => $group->id,
            'type' => 'subscription',
            'description' => $subscriptionType->label(),
        ];

        $charge = $this->subscriptionChargeAmountAndPaidAt($paymentId, $invoice);
        if ($charge['amount'] !== null) {
            $attributes['amount'] = $charge['amount'];
            $attributes['paid_at'] = $charge['paid_at'] ?? Carbon::now(config('app.timezone'));
        }

        BillingHistory::updateOrCreate(
            ['square_payment_id' => $paymentId],
            $attributes,
        );
    }

    /**
     * billing_histories に書く金額と決済日時を Square の決済情報から決める。
     *
     * Payment の合計を優先する。API 失敗時や 0 円のときは Invoice の入金額・更新日時を使う。
     *
     * @param  array<string, mixed>  $invoice
     * @return array{amount: int|null, paid_at: Carbon|null}
     */
    private function subscriptionChargeAmountAndPaidAt(string $paymentId, array $invoice): array
    {
        $amount = null;
        $paidAt = null;

        try {
            $payment = $this->square->retrievePayment($paymentId);
            $amount = $this->paymentTotalAmount($payment);
            $paidAt = $this->paymentCreatedAt($payment);
        } catch (SquareApiException) {
            $amount = null;
            $paidAt = null;
        }

        if ($amount === null) {
            $amount = $this->amountFromInvoicePayload($invoice);
        }

        if ($paidAt === null) {
            $paidAt = $this->paidAtFromInvoicePayload($invoice);
        }

        if ($amount === null) {
            return ['amount' => null, 'paid_at' => null];
        }

        return [
            'amount' => $amount,
            'paid_at' => $paidAt,
        ];
    }

    /**
     * Square Payment の合計金額を返す。取れない、または 0 以下のときは null。
     */
    private function paymentTotalAmount(Payment $payment): ?int
    {
        $totalMoney = $payment->getTotalMoney() ?? $payment->getAmountMoney();
        $amount = $totalMoney?->getAmount();

        return is_int($amount) && $amount > 0 ? $amount : null;
    }

    /**
     * Square Payment の作成日時を返す。
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
     * Invoice の更新日時を決済日時のフォールバックとして返す。
     *
     * @param  array<string, mixed>  $invoice
     */
    private function paidAtFromInvoicePayload(array $invoice): ?Carbon
    {
        $updatedAt = $invoice['updated_at'] ?? null;
        if (! is_string($updatedAt) || $updatedAt === '') {
            return null;
        }

        return Carbon::parse($updatedAt, config('app.timezone'));
    }

    /**
     * Invoice ペイロードから billing_histories 用の金額（最小通貨単位）を返す。
     *
     * payment_requests の入金済み額、なければ請求予定額、最後に請求書の次回支払額を見る。
     * 複数依頼があるときは配列先頭から最初に正の金額が取れた依頼だけを使う。
     *
     * @param  array<string, mixed>  $invoice
     */
    private function amountFromInvoicePayload(array $invoice): ?int
    {
        $paymentRequests = $invoice['payment_requests'] ?? null;
        if (is_array($paymentRequests)) {
            foreach ($paymentRequests as $paymentRequest) {
                if (! is_array($paymentRequest)) {
                    continue;
                }

                // 入金済み額
                $completed = $this->moneyAmountFromPayload($paymentRequest['total_completed_amount_money'] ?? null);
                if ($completed !== null && $completed > 0) {
                    return $completed;
                }

                // 請求予定額
                $computed = $this->moneyAmountFromPayload($paymentRequest['computed_amount_money'] ?? null);
                if ($computed !== null && $computed > 0) {
                    return $computed;
                }
            }
        }

        // 請求書の次回支払額
        $nextPayment = $this->moneyAmountFromPayload($invoice['next_payment_amount_money'] ?? null);
        if ($nextPayment !== null && $nextPayment > 0) {
            return $nextPayment;
        }

        return null;
    }

    /**
     * Webhook の Money 配列から整数金額を返す。
     */
    private function moneyAmountFromPayload(mixed $money): ?int
    {
        if (! is_array($money) || ! array_key_exists('amount', $money)) {
            return null;
        }

        $amount = $money['amount'];

        return is_int($amount) ? $amount : null;
    }

    /**
     * invoice.payment_made で Payment ID が取れなかったときの診断ログ。
     *
     * @param  array<string, mixed>  $invoice
     */
    private function logMissingPaymentIdFromInvoice(array $invoice, string $eventId): void
    {
        $orderId = $invoice['order_id'] ?? null;
        if (! is_string($orderId) || $orderId === '') {
            Log::warning('Square invoice.payment_made: invoice has no order_id for payment id.', [
                'event_id' => $eventId,
                'invoice_id' => $invoice['id'] ?? null,
            ]);

            return;
        }

        try {
            $this->square->retrieveOrder($orderId);
            Log::warning('Square invoice.payment_made: order has no payment tender.', [
                'event_id' => $eventId,
                'order_id' => $orderId,
            ]);
        } catch (SquareApiException $exception) {
            Log::warning('Square invoice.payment_made: failed to retrieve order for payment id.', [
                'event_id' => $eventId,
                'order_id' => $orderId,
                'status_code' => $exception->getCode(),
            ]);
        }
    }

    /**
     * subscription.updated の Webhook からサブスクリプション本体を取り出す。
     *
     * @return array<string, mixed>|null
     */
    private function subscriptionFromPayload(array $payload): ?array
    {
        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            return null;
        }

        $object = $data['object'] ?? null;
        if (! is_array($object) || ! isset($object['subscription']) || ! is_array($object['subscription'])) {
            return null;
        }

        return $this->normalizeSubscriptionFields($object['subscription']);
    }

    /**
     * invoice.payment_made の Webhook から請求書本体を取り出す。
     *
     * @return array<string, mixed>|null
     */
    private function invoiceFromPayload(array $payload): ?array
    {
        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            return null;
        }

        $object = $data['object'] ?? null;
        if (! is_array($object) || ! isset($object['invoice']) || ! is_array($object['invoice'])) {
            return null;
        }

        return $object['invoice'];
    }

    /**
     * payment.updated の Webhook から支払い本体を取り出す。
     *
     * @return array<string, mixed>|null
     */
    private function paymentFromPayload(array $payload): ?array
    {
        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            return null;
        }

        $object = $data['object'] ?? null;
        if (! is_array($object) || ! isset($object['payment']) || ! is_array($object['payment'])) {
            return null;
        }

        return $object['payment'];
    }

    /**
     * サブスクリプション配列を同期に使う項目だけに揃える。
     *
     * @param  array<string, mixed>  $subscription
     * @return array{
     *     id: string|null,
     *     customer_id: string|null,
     *     plan_variation_id: string|null,
     *     status: string|null,
     *     start_date: string|null,
     *     charged_through_date: string|null,
     *     canceled_date: string|null,
     * }
     */
    private function normalizeSubscriptionFields(array $subscription): array
    {
        $id = $subscription['id'] ?? null;

        return [
            'id' => is_string($id) && $id !== '' ? $id : null,
            'customer_id' => $subscription['customer_id'] ?? null,
            'plan_variation_id' => $subscription['plan_variation_id'] ?? null,
            'status' => $subscription['status'] ?? null,
            'start_date' => $subscription['start_date'] ?? null,
            'charged_through_date' => $subscription['charged_through_date'] ?? null,
            'canceled_date' => $subscription['canceled_date'] ?? null,
        ];
    }

    /**
     * status が ACTIVE かを返す。
     *
     * @param  array<string, mixed>  $subscription
     */
    private function grantsPaidPlan(array $subscription): bool
    {
        $status = strtolower((string) ($subscription['status'] ?? ''));

        return $status === 'active';
    }

    /**
     * 有料プランを FREE に戻すかを判定する。
     *
     * @param  array<string, mixed>  $subscription
     */
    private function shouldRevokePlan(array $subscription): bool
    {
        $status = strtolower((string) ($subscription['status'] ?? ''));

        // PAUSED は請求周期を待たず FREE に戻す
        if ($status === 'paused') {
            return true;
        }

        // CANCELED / DEACTIVATED 以外（PENDING など）は有料プランを維持する
        if (! in_array($status, ['canceled', 'deactivated'], true)) {
            return false;
        }

        $periodEnd = SubscriptionHelper::periodEnd(
            $subscription['charged_through_date'] ?? null,
            $subscription['canceled_date'] ?? null,
        );
        // 請求周期終了日が不明なら FREE に戻す
        if ($periodEnd === null) {
            return true;
        }

        // 請求周期を過ぎていれば FREE に戻す（期間内は有料プランを維持）
        return $periodEnd->isPast();
    }

    /**
     * Square Webhook の event_id を返す。無い場合は空文字。
     */
    private function eventIdFromPayload(array $payload): string
    {
        $eventId = $payload['event_id'] ?? '';

        return is_string($eventId) ? $eventId : '';
    }

    /**
     * 同一 event_id の再送では処理を一度だけ実行する。event_id が空のときは毎回実行する。
     */
    private function oncePerEvent(string $eventId, callable $callback): void
    {
        if ($eventId !== '') {
            if (! Cache::add("square_webhook:{$eventId}", true, now()->addDays(30))) {
                return;
            }
        }

        $callback();
    }
}
