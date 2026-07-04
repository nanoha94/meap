<?php

namespace App\Services;

use App\Enums\BillingSubscriptionType;
use App\Enums\GroupPlan;
use App\Models\Group;
use App\Traits\ExtractsSubscriptionEndsAt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Cashier;
use Stripe\Subscription as StripeSubscription;

class BillingWebhookService
{
    use ExtractsSubscriptionEndsAt;
    public function __construct(
        private readonly AiUsageService $aiUsageService,
    ) {}

    /**
     * サブスク請求成功時にプラン更新と利用回数リセットを行う。
     */
    public function handleInvoicePaid(array $payload): void
    {
        $this->oncePerEvent($payload['id'] ?? '', function () use ($payload): void {
            $invoice = $payload['data']['object'] ?? [];

            if (empty($invoice['subscription'])) {
                return;
            }

            $group = $this->findGroupByStripeId($invoice['customer'] ?? null);
            if ($group === null) {
                return;
            }

            $priceId = $this->extractFirstPriceId($invoice, 'lines');
            $plan = $this->planFromPriceId($priceId);
            if ($plan !== null) {
                $this->updateGroupPlan($group, $plan);
            }

            $periodEnd = $this->extractInvoicePeriodEnd($invoice);
            if ($periodEnd !== null) {
                $this->aiUsageService->renewBillingPeriod($group, $periodEnd);
            }
        });
    }

    /**
     * サブスクリプション状態に応じて Group.plan を同期する。
     *
     * 有効と判断できる status のみ有料プランを付与し、それ以外はすべて FREE に戻す（安全側）。
     */
    public function syncPlanFromSubscription(Group $group, array $subscription): void
    {
        if (! $this->isManagedSubscription($subscription)) {
            return;
        }

        $status = $subscription['status'] ?? null;

        if (in_array($status, [
            StripeSubscription::STATUS_ACTIVE,
            StripeSubscription::STATUS_TRIALING,
            StripeSubscription::STATUS_PAST_DUE,
        ], true)) {
            $priceId = $this->extractFirstPriceId($subscription, 'items');
            $plan = $this->planFromPriceId($priceId);

            if ($plan !== null) {
                $this->updateGroupPlan($group, $plan);
            }

            return;
        }

        $this->updateGroupPlan($group, GroupPlan::FREE);
    }

    /**
     * Stripe の解約予定状態と DB の ends_at を同期する。
     *
     * cancel_at_period_end=true かつ ends_at 未設定のときは current_period_end（なければ cancel_at）から ends_at を設定する。
     * 解約キャンセル（再開）後も ends_at が残ると UI が「解約予定」のままになるため、
     * cancel_at_period_end=false の active サブスクでは ends_at をクリアする。
     *
     * @param  array<string, mixed>  $subscription
     */
    public function syncSubscriptionCancellationSchedule(Group $group, array $subscription): void
    {
        if (! $this->isManagedSubscription($subscription)) {
            return;
        }

        $subscriptionType = config('billing.subscription_type');

        if (! is_string($subscriptionType) || $subscriptionType === '') {
            return;
        }

        $localSubscription = $group->subscription($subscriptionType);

        if ($localSubscription === null) {
            return;
        }

        $status = $subscription['status'] ?? null;

        if (! in_array($status, [
            StripeSubscription::STATUS_ACTIVE,
            StripeSubscription::STATUS_TRIALING,
        ], true)) {
            return;
        }

        if ((bool) ($subscription['cancel_at_period_end'] ?? false)) {
            if ($localSubscription->ends_at === null) {
                $timestamp = $this->extractEndsAtTimestamp($subscription);

                if ($timestamp !== null) {
                    $endsAt = Carbon::createFromTimestamp($timestamp, config('app.timezone'));
                    $localSubscription->forceFill(['ends_at' => $endsAt])->save();
                }
            }

            return;
        }

        if ($localSubscription->ends_at !== null) {
            $localSubscription->forceFill(['ends_at' => null])->save();
        }
    }

    /**
     * 買い切りパック購入時に ai_pack_remaining を加算する。
     */
    public function handleCheckoutSessionCompleted(array $payload): void
    {
        $this->oncePerEvent($payload['id'] ?? '', function () use ($payload): void {
            $session = $payload['data']['object'] ?? [];

            if (($session['metadata']['type'] ?? null) !== 'pack') {
                return;
            }

            if (($session['payment_status'] ?? null) !== 'paid') {
                return;
            }

            $groupId = $session['metadata']['group_id'] ?? null;
            $credits = (int) ($session['metadata']['credits'] ?? 0);

            if (! is_string($groupId) || $groupId === '' || $credits <= 0) {
                return;
            }

            $group = Group::query()->find($groupId);
            if ($group === null) {
                return;
            }

            $this->addPackCredits($group, $credits);
        });
    }

    /**
     * Group.plan を更新し、プラン変更時は月間残数を調整する。
     *
     * ai_usage_reset_at は更新しない。周期リセットは handleInvoicePaid 内の renewBillingPeriod() が担当する。
     */
    public function updateGroupPlan(Group $group, GroupPlan $plan): void
    {
        DB::transaction(function () use ($group, $plan): void {
            $lockedGroup = Group::query()->lockForUpdate()->find($group->id);

            if ($lockedGroup === null) {
                return;
            }

            $oldPlan = $lockedGroup->plan ?? GroupPlan::FREE;

            // プランが変更された場合のみ月間残数を調整する
            if ($oldPlan !== $plan) {
                $this->aiUsageService->adjustMonthlyRemainingForPlanChange(
                    $lockedGroup,
                    $oldPlan,
                    $plan,
                );
            }

            $lockedGroup->plan = $plan;
            $lockedGroup->save();
        });
    }

    /**
     * Cashier::useCustomerModel(Group::class) を前提とする。
     */
    private function findGroupByStripeId(?string $stripeCustomerId): ?Group
    {
        if ($stripeCustomerId === null || $stripeCustomerId === '') {
            return null;
        }

        /** @var Group|null */
        return Cashier::findBillable($stripeCustomerId);
    }

    /**
     * 価格IDからプランを取得する。
     */
    private function planFromPriceId(?string $priceId): ?GroupPlan
    {
        if ($priceId === null || $priceId === '') {
            return null;
        }

        $standardPriceId = config('billing.price_ids.' . BillingSubscriptionType::STANDARD->configKey());

        if (is_string($standardPriceId) && $standardPriceId !== '' && $priceId === $standardPriceId) {
            return GroupPlan::STANDARD;
        }

        return null;
    }

    private function addPackCredits(Group $group, int $credits): void
    {
        DB::transaction(function () use ($group, $credits): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);
            $lockedGroup->ai_pack_remaining += $credits;
            $lockedGroup->save();
        });
    }

    /**
     * サブスクリプションが管理対象かどうかを判断する。
     *
     * サブスクリプションの type が config('billing.subscription_type') と一致するかどうかを判断する。
     * 一致する場合は true、一致しない場合は false を返す。
     *
     * @param  array<string, mixed>  $subscription
     */
    private function isManagedSubscription(array $subscription): bool
    {
        $subscriptionType = config('billing.subscription_type');

        if (! is_string($subscriptionType) || $subscriptionType === '') {
            return false;
        }

        $type = $subscription['metadata']['type']
            ?? $subscription['metadata']['name']
            ?? $subscriptionType;

        return $type === $subscriptionType;
    }

    /**
     * Stripe オブジェクトの明細配列から先頭の price ID を取得する。
     *
     * @param  array<string, mixed>  $object
     * @param  'items'|'lines'  $lineItemsKey
     */
    private function extractFirstPriceId(array $object, string $lineItemsKey): ?string
    {
        $items = $object[$lineItemsKey]['data'] ?? [];

        if ($items === []) {
            return null;
        }

        return $items[0]['price']['id'] ?? null;
    }

    /**
     * 請求期間の終了日を取得する。
     *
     * @param  array<string, mixed>  $invoice
     */
    private function extractInvoicePeriodEnd(array $invoice): ?Carbon
    {
        $lines = $invoice['lines']['data'] ?? [];
        $periodEnd = $lines[0]['period']['end'] ?? null;

        if ($periodEnd === null) {
            return null;
        }

        return Carbon::createFromTimestamp($periodEnd, config('app.timezone'));
    }

    /**
     * 同一の Stripe イベント ID（evt_xxx）の再送による二重処理を防ぐ。
     *
     * 購入内容やユーザー単位の制限ではない。別の購入は別 event ID として処理される。
     * TTL は Stripe の再送期間（おおよそ数日）より長めの余裕。
     */
    private function oncePerEvent(string $eventId, callable $callback): void
    {
        if ($eventId !== '') {
            if (! Cache::add("stripe_webhook:{$eventId}", true, now()->addDays(30))) {
                return;
            }
        }

        $callback();
    }
}
