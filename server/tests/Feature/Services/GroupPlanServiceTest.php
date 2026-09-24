<?php

use App\Enums\GroupPlan;
use App\Models\Group;
use App\Services\GroupPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(GroupPlanService::class);
});

function makeGroupForPlanUpdate(array $attributes = []): Group
{
    return Group::factory()->create(array_merge([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => GroupPlan::FREE->monthlyLimit(),
        'ai_pack_remaining' => 0,
    ], $attributes));
}

// ===== update() メソッドのテストケース =====

test('4-9-1: 【プラン更新】 FREE から STANDARD へ変更すると月間残数が新上限になる', function () {
    $group = makeGroupForPlanUpdate([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 0,
    ]);

    $this->service->update($group, GroupPlan::STANDARD);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(30);
});

test('4-9-2: 【プラン更新】 同一プランへの更新では月間残数を変更しない', function () {
    $group = makeGroupForPlanUpdate([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
    ]);

    $this->service->update($group, GroupPlan::STANDARD);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::STANDARD)
        ->and($group->ai_monthly_remaining)->toBe(20);
});

test('4-9-3: 【プラン更新】 STANDARD から FREE へ周期内変更では月間残数を維持する', function () {
    $group = makeGroupForPlanUpdate([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
        'ai_usage_reset_at' => now()->addDays(10),
    ]);

    $this->service->update($group, GroupPlan::FREE);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::FREE)
        ->and($group->ai_monthly_remaining)->toBe(20);
});

test('4-9-4: 【プラン更新】 STANDARD から FREE へ周期終了後は月間残数を 0 にする', function () {
    $group = makeGroupForPlanUpdate([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
        'ai_usage_reset_at' => now()->subDay(),
    ]);

    $this->service->update($group, GroupPlan::FREE);

    $group->refresh();
    expect($group->plan)->toBe(GroupPlan::FREE)
        ->and($group->ai_monthly_remaining)->toBe(0);
});

test('4-9-5: 【プラン更新】 プラン変更しても ai_pack_remaining は変更しない', function () {
    $group = makeGroupForPlanUpdate([
        'plan' => GroupPlan::FREE,
        'ai_pack_remaining' => 15,
    ]);

    $this->service->update($group, GroupPlan::STANDARD);

    $group->refresh();
    expect($group->ai_pack_remaining)->toBe(15);
});

test('4-9-6: 【プラン更新】 削除済み Group はサイレントにスキップする', function () {
    $group = makeGroupForPlanUpdate(['plan' => GroupPlan::FREE]);
    $groupId = $group->id;

    $group->delete();

    expect(fn () => $this->service->update($group, GroupPlan::STANDARD))
        ->not->toThrow(Exception::class);

    expect(Group::query()->find($groupId))->toBeNull();
});
