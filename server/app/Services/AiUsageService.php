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
     *
     * @throws HttpException 月次上限超過時は 429
     */
    public function consumeUsage(Group $group): void
    {
        DB::transaction(function () use ($group): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);

            $this->resetUsageIfNeeded($lockedGroup);
            $this->assertWithinLimits($lockedGroup);

            $lockedGroup->ai_usage_count++;
            $lockedGroup->save();
        });
    }

    /**
     * AI 処理失敗時に消費した利用回数を戻す。
     */
    public function refundUsage(Group $group): void
    {
        DB::transaction(function () use ($group): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);

            $this->resetUsageIfNeeded($lockedGroup);

            if ($lockedGroup->ai_usage_count > 0) {
                $lockedGroup->ai_usage_count--;
            }

            $lockedGroup->save();
        });
    }

    /**
     * 利用状況を取得する（必要なら DB 上のリセットも同期する）。
     *
     * @return array{
     *     plan: string,
     *     usageCount: int,
     *     usageLimit: int,
     *     resetsAt: string|null,
     * }
     */
    public function getUsageStatus(Group $group): array
    {
        DB::transaction(function () use ($group): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);
            $this->resetUsageIfNeeded($lockedGroup);
        });

        $group->refresh();
        $plan = $this->getPlan($group);

        return [
            'plan' => $plan->value,
            'usageCount' => $group->ai_usage_count,
            'usageLimit' => $plan->monthlyLimit(),
            'resetsAt' => $group->ai_usage_reset_at?->toIso8601String(),
        ];
    }

    /**
     * Stripe 等の課金周期更新時に AI 利用回数をリセットする。
     * current_period_end をそのまま渡す想定。
     */
    public function renewBillingPeriod(Group $group, Carbon $periodEnd): void
    {
        DB::transaction(function () use ($group, $periodEnd): void {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->id);

            $lockedGroup->ai_usage_count = 0;
            $lockedGroup->ai_usage_reset_at = $periodEnd;
            $lockedGroup->save();
        });
    }

    /**
     * 次回リセット日時を過ぎている場合にカウンターを初期化する。
     */
    public function resetUsageIfNeeded(Group $group): void
    {
        $now = now();

        if ($group->ai_usage_reset_at === null || $now->gte($group->ai_usage_reset_at)) {
            $group->ai_usage_count = 0;
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
     * グループのプランを取得する。
     */
    private static function getPlan(Group $group): GroupPlan
    {
        return $group->plan ?? GroupPlan::FREE;
    }

    /**
     * AI 利用回数がプランの上限を超えている場合はエラーを投げる。
     * @throws HttpException
     */
    private function assertWithinLimits(Group $group): void
    {
        $plan = $this->getPlan($group);

        if ($group->ai_usage_count >= $plan->monthlyLimit()) {
            throw new HttpException(
                HttpStatusCode::TOO_MANY_REQUESTS->value,
                __('api.ai.usage.monthly_limit_exceeded'),
                null,
                ['X-Error-Type' => 'ai_monthly_limit_exceeded'],
            );
        }
    }
}
