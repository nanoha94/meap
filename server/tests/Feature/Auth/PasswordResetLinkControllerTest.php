<?php

namespace Tests\Feature\Auth;

use App\Custom\Auth\CustomPasswordBroker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('2-4-1: 正常なパスワードリセットリンク送信', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('password.request'), [
        'email' => $user->email,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'パスワードリセットリンクをメールで送信しました。']);
});

test('2-4-2: 存在しないユーザー', function () {
    $response = $this->postJson(route('password.request'), [
        'email' => 'nonexistent@example.com',
    ]);
    $response->assertStatus(422);
    $response->assertJson(['message' => '指定されたメールアドレスのユーザーが見つかりませんでした。', 'errors' => []]);
});

test('2-4-3: メールアドレス未入力', function () {
    $response = $this->postJson(route('password.request'), []);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $responseData = $response->json();
    $this->assertContains('emailは必ず指定してください。', $responseData['errors']['email']);
});

test('2-4-4: 無効なメール形式', function () {
    $response = $this->postJson(route('password.request'), [
        'email' => 'invalid-email',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $responseData = $response->json();
    $this->assertContains('emailには、有効なメールアドレスを指定してください。', $responseData['errors']['email']);
});

test('2-4-5: リセットリンク送信のレート制限', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 6; $i++) {
        $this->postJson(route('password.request'), [
            'email' => $user->email,
        ]);
    }

    $response = $this->postJson(route('password.request'), [
        'email' => $user->email,
    ]);

    $response->assertStatus(429);
    $response->assertJson(['message' => 'パスワードリセットリンクの送信は、短時間に複数回リクエストすることはできません。']);
});

test('2-4-6: サーバーエラー', function () {
    // サーバーエラーをシミュレーション
    Password::shouldReceive('sendResetLink')
        ->andThrow(new \Exception('Intentional Server Error'));

    $user = User::factory()->create();
    $response = $this->postJson(route('password.request'), [
        'email' => $user->email,
    ]);

    $response->assertStatus(500);
    $response->assertJson(['message' => 'サーバー内部エラーが発生しました。']);

    // モックのクリア
    \Mockery::close();
});

test('2-4-7: トークン生成失敗', function () {
    // Passwordファサードをモックしてトークン生成失敗をシミュレーション
    Password::shouldReceive('sendResetLink')
        ->andReturn(CustomPasswordBroker::RETRY_TOKEN);

    $user = User::factory()->create();
    $response = $this->postJson(route('password.request'), [
        'email' => $user->email,
    ]);

    $response->assertStatus(500);
    $response->assertJson(['message' => 'トークン生成に失敗しました。']);

    // モックのクリア
    \Mockery::close();
});
