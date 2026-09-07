<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('2-2-1: 【store】 既にメールアドレスが確認済みの場合', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->post('/email/verification-notification');

    $response->assertRedirect(config('app.frontend_url') . '/plan');
});

test('2-2-2: 【store】 メールアドレス確認通知の再送信', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($user)->post('/email/verification-notification');

    $response->assertStatus(200);
    $response->assertJson(['message' => '登録時に入力されたメールアドレス宛にメールアドレス確認リンクを再送しました。']);
});

test('2-2-3: 【store】 メール送信失敗', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    // Simulate email sending failure
    Mail::fake();
    Mail::shouldReceive('send')->andThrow(new \Exception('Email sending failed'));

    $response = $this->actingAs($user)->post('/email/verification-notification');

    $response->assertStatus(500);
    $response->assertJson(['message' => 'メールアドレス確認リンクの再送に失敗しました。']);
});
