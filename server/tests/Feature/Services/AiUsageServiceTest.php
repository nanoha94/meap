<?php

use App\Enums\GroupPlan;
use App\Models\Color;
use App\Models\Group;
use App\Services\AiUsageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([
        ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
        ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
        ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
    ] as $color) {
        Color::create($color);
    }

    $this->service = app(AiUsageService::class);
    $this->group = Group::createGroup();
});

// ===== consumeUsage() メソッドのテストケース =====

test('4-1-1: 【利用回数消費】 利用回数を消費できる', function () {
    $this->service->consumeUsage($this->group);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(2);
});

test('4-1-2: 【利用回数消費】 次回リセット日時を過ぎるとフリー枠がリセットされる', function () {
    $resetAt = now()->copy()->subMonths(2)->startOfDay();

    $this->group->update([
        'ai_monthly_remaining' => 0,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $this->service->consumeUsage($this->group);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(2);
    expect($this->group->ai_usage_reset_at->greaterThan(now()))->toBeTrue();
});

test('4-1-3: 【利用回数消費】 数か月未使用でも次回操作時にフリー枠がリセットされる', function () {
    $resetAt = now()->copy()->subMonths(5)->startOfDay();

    $this->group->update([
        'ai_monthly_remaining' => 1,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $this->service->consumeUsage($this->group);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(2);
    expect($this->group->ai_usage_reset_at->greaterThan(now()))->toBeTrue();
});

test('4-1-4: 【利用回数消費】 月次上限到達後は買い切り残高から消費する', function () {
    $this->group->update([
        'ai_monthly_remaining' => 0,
        'ai_pack_remaining' => 5,
        'ai_usage_reset_at' => now()->addMonth(),
    ]);

    $fromPack = $this->service->consumeUsage($this->group);

    $this->group->refresh();
    expect($fromPack)->toBeTrue();
    expect($this->group->ai_monthly_remaining)->toBe(0);
    expect($this->group->ai_pack_remaining)->toBe(4);
});

test('4-1-5: 【利用回数消費】 月次枠に余裕がある間は ai_monthly_remaining を減算する', function () {
    $this->group->update([
        'ai_monthly_remaining' => 2,
        'ai_pack_remaining' => 5,
        'ai_usage_reset_at' => now()->addMonth(),
    ]);

    $fromPack = $this->service->consumeUsage($this->group);

    $this->group->refresh();
    expect($fromPack)->toBeFalse();
    expect($this->group->ai_monthly_remaining)->toBe(1);
    expect($this->group->ai_pack_remaining)->toBe(5);
});

test('4-1-6: 【利用回数消費】 月次枠・買い切り残高ともに不足で 429 を投げる', function () {
    $this->group->update([
        'ai_monthly_remaining' => 0,
        'ai_pack_remaining' => 0,
        'ai_usage_reset_at' => now()->addMonth(),
    ]);

    expect(fn () => $this->service->consumeUsage($this->group))
        ->toThrow(HttpException::class, '今月のAI利用回数の上限に達しました。');
});

test('4-1-7: 【利用回数消費】 有料プランで月次枠・買い切り残高ともに不足で 429 を投げる', function () {
    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 0,
        'ai_pack_remaining' => 0,
        'ai_usage_reset_at' => now()->addDays(10),
    ]);

    expect(fn () => $this->service->consumeUsage($this->group))
        ->toThrow(HttpException::class, '今月のAI利用回数の上限に達しました。');
});

// ===== refundUsage() メソッドのテストケース =====

test('4-1-8: 【利用回数返却】 消費分を返却できる', function () {
    $fromPack = $this->service->consumeUsage($this->group);
    $this->service->refundUsage($this->group, $fromPack);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(3);
});

test('4-1-9: 【利用回数返却】 リセット後の消費を返却できる', function () {
    $resetAt = now()->copy()->subMonths(3)->startOfDay();

    $this->group->update([
        'ai_monthly_remaining' => 1,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $fromPack = $this->service->consumeUsage($this->group);
    $this->service->refundUsage($this->group, $fromPack);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(3);
});

test('4-1-10: 【利用回数返却】 買い切り残高消費分を返却できる', function () {
    $this->group->update([
        'ai_monthly_remaining' => 0,
        'ai_pack_remaining' => 5,
        'ai_usage_reset_at' => now()->addMonth(),
    ]);

    $fromPack = $this->service->consumeUsage($this->group);
    $this->service->refundUsage($this->group, $fromPack);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(0);
    expect($this->group->ai_pack_remaining)->toBe(5);
});

// ===== getUsageStatus() メソッドのテストケース =====

test('4-1-11: 【利用状況取得】 リセット待ちのフリー枠を同期する', function () {
    $resetAt = now()->copy()->subMonths(2)->startOfDay();

    $this->group->update([
        'ai_monthly_remaining' => 0,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $status = $this->service->getUsageStatus($this->group);

    expect($status['monthlyRemaining'])->toBe(3);
    expect($status['monthlyLimit'])->toBe(3);
    expect($status['resetsAt'])->not->toBeNull();

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(3);
});

test('4-1-12: 【利用状況取得】 解約後周期内はフリー月次リセットで上書きされない', function () {
    $this->group->update([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 20,
        'ai_usage_reset_at' => now()->addDays(10),
    ]);

    $this->service->getUsageStatus($this->group);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(20);
});

test('4-1-13: 【利用状況取得】 周期終了後にフリー月次リセットで枠が復帰する', function () {
    $this->group->update([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 0,
        'ai_usage_reset_at' => now()->subDay(),
    ]);

    $status = $this->service->getUsageStatus($this->group);

    expect($status['monthlyRemaining'])->toBe(3);
});

// ===== renewBillingPeriod() メソッドのテストケース =====

test('4-1-14: 【課金周期更新】 Stripe の課金周期でリセットする', function () {
    $periodEnd = now()->addDays(25)->startOfSecond();

    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 0,
    ]);

    $this->service->renewBillingPeriod($this->group, $periodEnd);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(30);
    expect($this->group->ai_usage_reset_at->eq($periodEnd))->toBeTrue();
});

test('4-1-15: 【課金周期更新】 月間残ありでもプラン上限にリセットする', function () {
    $periodEnd = now()->addDays(25)->startOfSecond();

    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
    ]);

    $this->service->renewBillingPeriod($this->group, $periodEnd);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(30);
    expect($this->group->ai_usage_reset_at->eq($periodEnd))->toBeTrue();
});

test('4-1-16: 【課金周期更新】 Pro プランで課金周期をリセットする', function () {
    $periodEnd = now()->addDays(25)->startOfSecond();

    $this->group->update([
        'plan' => GroupPlan::PRO,
        'ai_monthly_remaining' => 10,
    ]);

    $this->service->renewBillingPeriod($this->group, $periodEnd);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(50);
    expect($this->group->ai_usage_reset_at->eq($periodEnd))->toBeTrue();
});

test('4-1-17: 【課金周期更新】 請求周期更新で ai_usage_reset_at を新しい請求日に更新する', function () {
    $oldPeriodEnd = now()->addDays(2)->startOfSecond();
    $newPeriodEnd = now()->addDays(32)->startOfSecond();

    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 5,
        'ai_usage_reset_at' => $oldPeriodEnd,
    ]);

    $this->service->renewBillingPeriod($this->group, $newPeriodEnd);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(30);
    expect($this->group->ai_usage_reset_at->eq($newPeriodEnd))->toBeTrue();
    expect($this->group->ai_usage_reset_at->eq($oldPeriodEnd))->toBeFalse();
});

test('4-1-18: 【課金周期更新】 ダウングレード後の初回請求で新プラン上限にリセットする', function () {
    $newPeriodEnd = now()->addDays(30)->startOfSecond();

    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 40,
        'ai_usage_reset_at' => now()->subDay(),
    ]);

    $this->service->renewBillingPeriod($this->group, $newPeriodEnd);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(30);
    expect($this->group->ai_usage_reset_at->eq($newPeriodEnd))->toBeTrue();
});

// ===== adjustMonthlyRemainingForPlanChange() メソッドのテストケース =====

test('4-1-19: 【プラン変更】 フリー使い切りからスタンダードへ変更すると新プラン上限が付与される', function () {
    $this->group->update([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 0,
    ]);

    $this->service->adjustMonthlyRemainingForPlanChange(
        $this->group,
        GroupPlan::FREE,
        GroupPlan::STANDARD,
    );

    expect($this->group->ai_monthly_remaining)->toBe(30);
});

test('4-1-20: 【プラン変更】 フリー残数があっても新プラン上限のみ付与される', function () {
    $this->group->update([
        'plan' => GroupPlan::FREE,
        'ai_monthly_remaining' => 2,
    ]);

    $this->service->adjustMonthlyRemainingForPlanChange(
        $this->group,
        GroupPlan::FREE,
        GroupPlan::STANDARD,
    );

    expect($this->group->ai_monthly_remaining)->toBe(30);
});

test('4-1-21: 【プラン変更】 スタンダード解約からフリーへ変更しても周期内は残数を維持する', function () {
    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
        'ai_usage_reset_at' => now()->addDays(10),
    ]);

    $this->service->adjustMonthlyRemainingForPlanChange(
        $this->group,
        GroupPlan::STANDARD,
        GroupPlan::FREE,
    );

    expect($this->group->ai_monthly_remaining)->toBe(20);
});

test('4-1-22: 【プラン変更】 スタンダード解約からフリーへ変更し周期終了後は月間残数0になる', function () {
    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
        'ai_usage_reset_at' => now()->subDay(),
    ]);

    $this->service->adjustMonthlyRemainingForPlanChange(
        $this->group,
        GroupPlan::STANDARD,
        GroupPlan::FREE,
    );

    expect($this->group->ai_monthly_remaining)->toBe(0);
});

test('4-1-23: 【プラン変更】 同一プランへの変更では残数を変更しない', function () {
    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
    ]);

    $this->service->adjustMonthlyRemainingForPlanChange(
        $this->group,
        GroupPlan::STANDARD,
        GroupPlan::STANDARD,
    );

    expect($this->group->ai_monthly_remaining)->toBe(20);
});

test('4-1-24: 【プラン変更】 スタンダードから Pro へアップグレードすると新プラン上限が付与される', function () {
    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
    ]);

    $this->service->adjustMonthlyRemainingForPlanChange(
        $this->group,
        GroupPlan::STANDARD,
        GroupPlan::PRO,
    );

    expect($this->group->ai_monthly_remaining)->toBe(50);
});

test('4-1-25: 【プラン変更】 Pro からスタンダードへダウングレード予約しても周期内は残数を維持する', function () {
    $this->group->update([
        'plan' => GroupPlan::PRO,
        'ai_monthly_remaining' => 40,
        'ai_usage_reset_at' => now()->addDays(10),
    ]);

    $this->service->adjustMonthlyRemainingForPlanChange(
        $this->group,
        GroupPlan::PRO,
        GroupPlan::STANDARD,
    );

    expect($this->group->ai_monthly_remaining)->toBe(40);
});

test('4-1-26: 【プラン変更】 アップグレード後の請求周期更新で残数と請求日が更新される', function () {
    $oldPeriodEnd = now()->addDays(5)->startOfSecond();
    $newPeriodEnd = now()->addDays(35)->startOfSecond();

    $this->group->update([
        'plan' => GroupPlan::STANDARD,
        'ai_monthly_remaining' => 20,
        'ai_usage_reset_at' => $oldPeriodEnd,
    ]);

    $this->service->adjustMonthlyRemainingForPlanChange(
        $this->group,
        GroupPlan::STANDARD,
        GroupPlan::PRO,
    );

    expect($this->group->ai_monthly_remaining)->toBe(50);
    expect($this->group->ai_usage_reset_at->eq($oldPeriodEnd))->toBeTrue();

    $this->group->update(['plan' => GroupPlan::PRO]);

    $this->service->renewBillingPeriod($this->group, $newPeriodEnd);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(50);
    expect($this->group->ai_usage_reset_at->eq($newPeriodEnd))->toBeTrue();
});

test('4-1-27: 【プラン変更】 ダウングレード予約から初回請求まで残数を維持し請求後に新上限へリセットする', function () {
    $oldPeriodEnd = now()->addDays(10)->startOfSecond();
    $newPeriodEnd = now()->addDays(40)->startOfSecond();

    $this->group->update([
        'plan' => GroupPlan::PRO,
        'ai_monthly_remaining' => 40,
        'ai_usage_reset_at' => $oldPeriodEnd,
    ]);

    $this->service->adjustMonthlyRemainingForPlanChange(
        $this->group,
        GroupPlan::PRO,
        GroupPlan::STANDARD,
    );

    expect($this->group->ai_monthly_remaining)->toBe(40);
    expect($this->group->ai_usage_reset_at->eq($oldPeriodEnd))->toBeTrue();

    $this->group->update(['plan' => GroupPlan::STANDARD]);

    $this->service->renewBillingPeriod($this->group, $newPeriodEnd);

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(30);
    expect($this->group->ai_usage_reset_at->eq($newPeriodEnd))->toBeTrue();
});

// ===== Group::createGroup() メソッドのテストケース =====

test('4-1-28: 【グループ作成】 AI 利用トラッキングの初期値を設定する', function () {
    $now = now();
    Carbon::setTestNow($now);

    $group = Group::createGroup();

    expect($group->plan)->toBe(GroupPlan::FREE);
    expect($group->ai_monthly_remaining)->toBe(3);
    expect($group->ai_pack_remaining)->toBe(0);
    expect($group->ai_usage_reset_at)->not->toBeNull();
    expect($group->ai_usage_reset_at->diffInSeconds($now->copy()->addMonth()))->toBeLessThanOrEqual(1);

    Carbon::setTestNow();
});
