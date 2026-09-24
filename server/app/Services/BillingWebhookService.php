<?php

namespace App\Services;

use App\Enums\BillingSubscriptionType;
use App\Enums\GroupPlan;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BillingWebhookService
{
    public function __construct(
        private readonly AiUsageService $aiUsageService,
        private readonly GroupPlanService $groupPlanService,
    ) {}

    /**
     * subscription.renewed （サブスクリプション更新時）の通知時にプラン更新と利用回数リセットを行う。
     */
    public function handleSubscriptionRenewed(array $payload): void
    {
        $this->oncePerEvent($payload['id'] ?? '', function () use ($payload): void {
            $subscription = $this->subscriptionFromPayload($payload);
            if ($subscription === null) {
                return;
            }

            $group = Group::findByPayjpCustomerId($subscription['customer'] ?? null);
            if ($group === null) {
                return;
            }

            $this->updateGroupPlanFromPayjpSubscription($group, $subscription);

            $periodEnd = $this->periodEndFromSubscription($subscription);
            if ($periodEnd !== null) {
                $this->aiUsageService->renewBillingPeriod($group, $periodEnd);
            }
        });
    }

    /**
     * subscription.created / subscription.updated （サブスクリプション作成・更新時）の通知時に Group.plan を同期する。
     */
    public function handleSubscriptionChanged(array $payload): void
    {
        $subscription = $this->subscriptionFromPayload($payload);
        if ($subscription === null) {
            return;
        }

        $group = Group::findByPayjpCustomerId($subscription['customer'] ?? null);
        if ($group === null) {
            return;
        }

        $this->syncPlanFromPayjpSubscription($group, $subscription);
    }

    /**
     * subscription.deleted （サブスクリプション削除時）の通知時に FREE プランへ戻す。
     */
    public function handleSubscriptionDeleted(array $payload): void
    {
        $subscription = $this->subscriptionFromPayload($payload);
        if ($subscription === null) {
            return;
        }

        $group = Group::findByPayjpCustomerId($subscription['customer'] ?? null);
        if ($group === null) {
            return;
        }

        $this->groupPlanService->update($group, GroupPlan::FREE);
    }

    /**
     * subscription.paused （サブスクリプション一時停止時）の通知時に FREE プランへ戻す。
     */
    public function handleSubscriptionPaused(array $payload): void
    {
        $subscription = $this->subscriptionFromPayload($payload);
        if ($subscription === null) {
            return;
        }

        $group = Group::findByPayjpCustomerId($subscription['customer'] ?? null);
        if ($group === null) {
            return;
        }

        $this->groupPlanService->update($group, GroupPlan::FREE);
    }

    /**
     * charge.failed （課金失敗時）の通知時にプラン変更は行わない。
     */
    public function handleChargeFailed(array $payload): void
    {
        $charge = $payload['data'] ?? [];
        $chargeId = is_array($charge) ? ($charge['id'] ?? null) : null;

        Log::warning('PAY.JP charge.failed webhook received.', [
            'event_id' => $payload['id'] ?? null,
            'charge_id' => $chargeId,
        ]);
    }

    /**
     * サブスクリプション status に応じて Group.plan を同期する。
     *
     * @param  array<string, mixed>  $subscription
     */
    private function syncPlanFromPayjpSubscription(Group $group, array $subscription): void
    {
        $status = $subscription['status'] ?? null;

        if (in_array($status, ['trial', 'active'], true)) {
            $this->updateGroupPlanFromPayjpSubscription($group, $subscription);

            return;
        }

        if (in_array($status, ['canceled', 'paused'], true)) {
            $this->groupPlanService->update($group, GroupPlan::FREE);
        }
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    private function updateGroupPlanFromPayjpSubscription(Group $group, array $subscription): void
    {
        $planId = $subscription['plan']['id'] ?? null;
        $plan = BillingSubscriptionType::groupPlanFromPayjpPlanId(is_string($planId) ? $planId : null);

        if ($plan !== null) {
            $this->groupPlanService->update($group, $plan);
        }
    }

    /**
     * PAY.JP Webhook ペイロードからサブスクリプション本体（`data`）を取り出す。
     *
     * @return array<string, mixed>|null
     */
    private function subscriptionFromPayload(array $payload): ?array
    {
        $data = $payload['data'] ?? null;
        return is_array($data) ? $data : null;
    }

    /**
     * PAY.JP のサブスクリプション情報から周期終了日を取得する。
     *
     * @param  array<string, mixed>  $subscription
     */
    private function periodEndFromSubscription(array $subscription): ?Carbon
    {
        $periodEnd = $subscription['current_period_end'] ?? null;

        if ($periodEnd === null) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $periodEnd, config('app.timezone'));
    }

    /**
     * 同一 PAY.JP イベント ID の再送による二重処理を防ぐ。
     */
    private function oncePerEvent(string $eventId, callable $callback): void
    {
        if ($eventId !== '') {
            if (! Cache::add("payjp_webhook:{$eventId}", true, now()->addDays(30))) {
                return;
            }
        }

        $callback();
    }
}
