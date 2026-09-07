<?php

use App\Models\Color;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([
        ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
        ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
        ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
    ] as $color) {
        Color::create($color);
    }

    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->group = Group::createGroup();
    $this->group->users()->attach($this->user->id);
    $this->user->refresh();
    $this->user->load('groups');
});

// ===== show() メソッドのテストケース =====

test('3-14-1: 【AI利用状況取得】 正常に利用状況を取得できる', function () {
    $response = $this->actingAs($this->user)->get('/ai/usage');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'AI利用状況を取得しました。',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'plan',
            'monthlyRemaining',
            'monthlyLimit',
            'packRemaining',
            'resetsAt',
        ],
    ]);

    expect($response->json('data.monthlyRemaining'))->toBe(3);
    expect($response->json('data.monthlyLimit'))->toBe(3);
    expect($response->json('data.packRemaining'))->toBe(0);
    expect($response->json('data.resetsAt'))->not->toBeNull();
});

test('3-14-2: 【AI利用状況取得】 リセット待ちのフリー枠を同期して返す', function () {
    $resetAt = now()->copy()->subMonths(2)->startOfDay();

    $this->group->update([
        'ai_monthly_remaining' => 0,
        'ai_usage_reset_at' => $resetAt,
    ]);

    $response = $this->actingAs($this->user)->get('/ai/usage');

    $response->assertStatus(200);
    expect($response->json('data.monthlyRemaining'))->toBe(3);
    expect($response->json('data.resetsAt'))->not->toBeNull();

    $this->group->refresh();
    expect($this->group->ai_monthly_remaining)->toBe(3);
});

test('3-14-3: 【AI利用状況取得】 未認証', function () {
    $response = $this->get('/ai/usage');

    $response->assertStatus(401);
    $response->assertJson(['success' => false, 'message' => '認証が必要です。']);
});
