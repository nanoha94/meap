<?php

use App\Enums\GroupPlan;
use App\Models\Color;
use App\Models\Group;
use App\Services\AiUsageService;
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
    expect($this->group->ai_usage_count)->toBe(1);
});

test('4-1-2: 【利用回数消費】 次回リセット日時を過ぎるとカウンターがリセットされる', function () {
    $resetAt = now()->copy()->subMonths(2)->startOfDay();

    $this->group->update([
        'ai_usage_count' => 3,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $this->service->consumeUsage($this->group);

    $this->group->refresh();
    expect($this->group->ai_usage_count)->toBe(1);
    expect($this->group->ai_usage_reset_at->greaterThan(now()))->toBeTrue();
    expect($this->group->ai_usage_reset_at->day)->toBe($resetAt->day);
});

test('4-1-3: 【利用回数消費】 数か月未使用でも次回操作時に正しくリセットされる', function () {
    $resetAt = now()->copy()->subMonths(5)->startOfDay();

    $this->group->update([
        'ai_usage_count' => 2,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $this->service->consumeUsage($this->group);

    $this->group->refresh();
    expect($this->group->ai_usage_count)->toBe(1);
    expect($this->group->ai_usage_reset_at->greaterThan(now()))->toBeTrue();
    expect($this->group->ai_usage_reset_at->day)->toBe($resetAt->day);
});

test('4-1-4: 【利用回数消費】 月次上限超過で 429 を投げる', function () {
    $this->group->update([
        'plan' => GroupPlan::FREE,
        'ai_usage_count' => 3,
        'ai_usage_reset_at' => now()->addMonth(),
    ]);

    expect(fn () => $this->service->consumeUsage($this->group))
        ->toThrow(HttpException::class, '今月のAI利用回数の上限に達しました。');
});

// ===== getUsageStatus() メソッドのテストケース =====

test('4-1-5: 【利用状況取得】 リセット待ちの古い利用回数を同期する', function () {
    $resetAt = now()->copy()->subMonths(2)->startOfDay();

    $this->group->update([
        'ai_usage_count' => 3,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $status = $this->service->getUsageStatus($this->group);

    expect($status['usageCount'])->toBe(0);
    expect($status['usageLimit'])->toBe(3);
    expect($status['resetsAt'])->not->toBeNull();

    $this->group->refresh();
    expect($this->group->ai_usage_count)->toBe(0);
});

// ===== renewBillingPeriod() メソッドのテストケース =====

test('4-1-6: 【課金周期更新】 Stripe の課金周期でリセットする', function () {
    $periodEnd = now()->addDays(25)->startOfSecond();

    $this->group->update([
        'ai_usage_count' => 3,
    ]);

    $this->service->renewBillingPeriod($this->group, $periodEnd);

    $this->group->refresh();
    expect($this->group->ai_usage_count)->toBe(0);
    expect($this->group->ai_usage_reset_at->eq($periodEnd))->toBeTrue();
});

// ===== refundUsage() メソッドのテストケース =====

test('4-1-7: 【利用回数返却】 消費分を返却できる', function () {
    $this->service->consumeUsage($this->group);
    $this->service->refundUsage($this->group);

    $this->group->refresh();
    expect($this->group->ai_usage_count)->toBe(0);
});

test('4-1-8: 【利用回数返却】 数か月未使用後の返却でカウントが負にならない', function () {
    $resetAt = now()->copy()->subMonths(3)->startOfDay();

    $this->group->update([
        'ai_usage_count' => 2,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $this->service->refundUsage($this->group);

    $this->group->refresh();
    expect($this->group->ai_usage_count)->toBe(0);
    expect($this->group->ai_usage_reset_at->greaterThan(now()))->toBeTrue();
});

// ===== Group::createGroup() メソッドのテストケース =====

test('4-1-9: 【グループ作成】 AI 利用トラッキングの初期値を設定する', function () {
    expect($this->group->plan)->toBe(GroupPlan::FREE);
    expect($this->group->ai_usage_count)->toBe(0);
    expect($this->group->ai_usage_reset_at)->not->toBeNull();
    expect($this->group->ai_usage_reset_at->greaterThan(now()))->toBeTrue();
});
