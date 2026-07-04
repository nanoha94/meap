<?php

namespace App\Services;

use App\Enums\GroupPlan;
use App\Enums\HttpStatusCode;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AiUsageService
{
    /**
     * AI 利用可能か確認し、利用回数を 1 消費する。
     * 月間枠に余裕があれば ai_monthly_remaining を減算し、
     * 使い切っていれば ai_pack_remaining から減算する。
     *
     * @return bool 買い切り残高から消費した場合 true（refundUsage への引き渡し用）
     * @throws HttpException 月間枠・買い切り残高ともに不足時は 429
     */
    public function consumeUsage(Group $group): bool
    {
        $fromPack = false;

        DB::transaction(function () use ($group, &$fromPack): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);

            $this->resetFreeMonthlyUsageIfNeeded($lockedGroup);
            $this->assertWithinLimits($lockedGroup);

            if ($lockedGroup->ai_monthly_remaining > 0) {
                $lockedGroup->ai_monthly_remaining--;
            } else {
                $lockedGroup->ai_pack_remaining--;
                $fromPack = true;
            }

            $lockedGroup->save();
        });

        return $fromPack;
    }

    /**
     * AI 処理失敗時に消費した利用回数を戻す。
     *
     * @param  bool  $fromPack  consumeUsage() の戻り値。true のとき買い切り残高へ返却する。
     */
    public function refundUsage(Group $group, bool $fromPack = false): void
    {
        DB::transaction(function () use ($group, $fromPack): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);

            $this->resetFreeMonthlyUsageIfNeeded($lockedGroup);

            if ($fromPack) {
                $lockedGroup->ai_pack_remaining++;
            } else {
                $lockedGroup->ai_monthly_remaining++;
            }

            $lockedGroup->save();
        });
    }

    /**
     * 利用状況を取得する（フリープランは必要なら月次リセットを同期する）。
     *
     * @return array{
     *     plan: string,
     *     monthlyRemaining: int,
     *     monthlyLimit: int,
     *     packRemaining: int,
     *     resetsAt: string|null,
     * }
     */
    public function getUsageStatus(Group $group): array
    {
        DB::transaction(function () use ($group): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);
            $this->resetFreeMonthlyUsageIfNeeded($lockedGroup);
        });

        $group->refresh();
        $plan = $this->getPlan($group);

        return [
            'plan' => $plan->value,
            'monthlyRemaining' => $group->ai_monthly_remaining,
            'monthlyLimit' => $plan->monthlyLimit(),
            'packRemaining' => $group->ai_pack_remaining,
            'resetsAt' => $group->ai_usage_reset_at?->toIso8601String(),
        ];
    }

    /**
     * Stripe 等の課金周期更新時に月間枠を満タンにリセットする。
     * current_period_end をそのまま渡す想定。有料プラン専用。
     */
    public function renewBillingPeriod(Group $group, Carbon $periodEnd): void
    {
        DB::transaction(function () use ($group, $periodEnd): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);
            $plan = $this->getPlan($lockedGroup);

            $lockedGroup->ai_monthly_remaining = $plan->monthlyLimit();
            $lockedGroup->ai_usage_reset_at = $periodEnd;
            $lockedGroup->save();
        });
    }

    /**
     * プラン変更時に ai_monthly_remaining を調整する。
     * BillingWebhookService::updateGroupPlan() から呼ばれる。
     *
     * Group.plan の更新は呼び出し元（BillingWebhookService::updateGroupPlan）が行う。
     * ai_usage_reset_at の更新は renewBillingPeriod() の責務（handleInvoicePaid から呼ばれる）。
     *
     * 月間残数の満タン化は次の2経路に分かれる:
     * - 即時プラン変更（FREE→有料・アップグレード）: 本メソッドが新上限を設定
     * - 周期更新・ダウングレード後の初回請求: renewBillingPeriod() が新上限を設定
     *
     * ai_pack_remaining は本メソッドでは触らない。パック購入時のみ
     * BillingWebhookService::handleCheckoutSessionCompleted() で加算され、プラン変更の影響を受けない。
     *
     * ## プラン変更時の月間残数ルール
     *
     * oldPlan → newPlan:
     *   同一                      　… 変更なし
     *   FREE → 有料               　… 新プラン上限
     *   有料 → FREE（周期内）     　 … 変更なし
     *   有料 → FREE（周期終了後）    … 0
     *   有料 → 有料（アップグレード） … 新プラン上限
     *   有料 → 有料（ダウングレード） … 変更なし
     */
    public function adjustMonthlyRemainingForPlanChange(
        Group $group,
        GroupPlan $oldPlan,
        GroupPlan $newPlan,
    ): void {
        if ($oldPlan === $newPlan) {
            return;
        }

        // FREE → 有料: 新プラン上限にリセット
        if ($oldPlan === GroupPlan::FREE && $newPlan !== GroupPlan::FREE) {
            $group->ai_monthly_remaining = $newPlan->monthlyLimit();

            return;
        }

        // 有料 → FREE: GracePeriod 中は残数維持、周期終了後のみ 0
        if ($oldPlan !== GroupPlan::FREE && $newPlan === GroupPlan::FREE) {
            if ($this->isWithinBillingPeriod($group)) {
                return;
            }

            $group->ai_monthly_remaining = 0;

            return;
        }

        $oldLimit = $oldPlan->monthlyLimit();
        $newLimit = $newPlan->monthlyLimit();

        // 有料 → 有料（アップグレード）: 新上限にリセット
        if ($newLimit > $oldLimit) {
            $group->ai_monthly_remaining = $newLimit;

            return;
        }

        // 有料 → 有料（ダウングレード）: 周期末まで旧プランの残数を維持（新上限は renewBillingPeriod）
    }

    /**
     * フリープラン: 次回リセット日時を過ぎていれば月間枠を満タンに戻す。
     * 有料プランは Stripe Webhook（renewBillingPeriod）のみがリセット経路。
     */
    private function resetFreeMonthlyUsageIfNeeded(Group $group): void
    {
        if (self::getPlan($group) !== GroupPlan::FREE) {
            return;
        }

        if ($this->isWithinBillingPeriod($group)) {
            return;
        }

        $now = now();

        if ($group->ai_usage_reset_at === null || $now->gte($group->ai_usage_reset_at)) {
            $group->ai_monthly_remaining = GroupPlan::FREE->monthlyLimit();
            $group->ai_usage_reset_at = $group->ai_usage_reset_at !== null
                ? self::advanceResetAt($group->ai_usage_reset_at, $now)
                : $now->copy()->addMonth();
            $group->save();
        }
    }

    /**
     * ai_usage_reset_at を1か月ずつ進めて from を超える値にする。
     */
    private static function advanceResetAt(Carbon $resetAt, ?Carbon $from = null): Carbon
    {
        $from ??= now();

        $next = $resetAt->copy();

        while ($next->lte($from)) {
            $next->addMonth();
        }

        return $next;
    }

    /**
     * Stripe 課金周期（ai_usage_reset_at）内かどうか。
     * 解約後も周期終了までは true となり、有料分の残数を維持する。
     */
    private function isWithinBillingPeriod(Group $group): bool
    {
        return $group->ai_usage_reset_at !== null && now()->lt($group->ai_usage_reset_at);
    }

    /**
     * グループのプランを取得する。
     */
    private static function getPlan(Group $group): GroupPlan
    {
        return $group->plan ?? GroupPlan::FREE;
    }

    /**
     * 月間枠・買い切り残高の両方が使い切れている場合はエラーを投げる。
     *
     * @throws HttpException
     */
    private function assertWithinLimits(Group $group): void
    {
        if ($group->ai_monthly_remaining > 0) {
            return;
        }

        if ($group->ai_pack_remaining > 0) {
            return;
        }

        throw new HttpException(
            HttpStatusCode::TOO_MANY_REQUESTS->value,
            __('api.ai.usage.monthly_limit_exceeded'),
            null,
            ['X-Error-Type' => 'ai_monthly_limit_exceeded'],
        );
    }
}
