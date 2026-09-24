<?php

namespace App\Services;

use App\Enums\GroupPlan;
use App\Models\Group;
use Illuminate\Support\Facades\DB;

class GroupPlanService
{
    public function __construct(
        private readonly AiUsageService $aiUsageService,
    ) {}

    /**
     * Group.plan を更新し、プラン変更時は月間残数を更新する。
     *
     * ai_usage_reset_at は更新しない。周期リセットは AiUsageService::renewBillingPeriod() が担当する。
     */
    public function update(Group $group, GroupPlan $plan): void
    {
        DB::transaction(function () use ($group, $plan): void {
            $lockedGroup = Group::query()->lockForUpdate()->find($group->id);

            if ($lockedGroup === null) {
                return;
            }

            $oldPlan = $lockedGroup->plan ?? GroupPlan::FREE;

            if ($oldPlan !== $plan) {
                $this->aiUsageService->updateMonthlyRemainingForPlanChange(
                    $lockedGroup,
                    $oldPlan,
                    $plan,
                );
            }

            $lockedGroup->plan = $plan;
            $lockedGroup->save();
        });
    }
}
