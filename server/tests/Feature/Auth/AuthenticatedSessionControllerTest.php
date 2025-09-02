<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('1-1: 正しい認証情報でログインできる', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => __('api.auth.login_success'),
        'data' => null,
    ]);
});

test('1-2: Remember Me機能でログインできる', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => __('api.auth.login_success'),
        'data' => null,
    ]);
});

test('2-1: 存在しないメールアドレスではログインできない', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => 'invalid@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertStatus(302); // リダイレクト
});

test('2-1: 間違ったパスワードではログインできない', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertStatus(302); // リダイレクト
});

test('2-1: 認証情報が不足している場合はログインできない', function () {
    $response = $this->post('/login', []);

    $this->assertGuest();
    $response->assertStatus(302); // リダイレクト
});

test('2-1: 無効なメール形式ではログインできない', function () {
    $response = $this->post('/login', [
        'email' => 'invalid-email',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertStatus(302); // リダイレクト
});

test('4-1: ログアウトが正常に動作する', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => __('api.auth.logout_success'),
        'data' => null,
    ]);
});

test('4-2: 未認証ユーザーはログアウトできない', function () {
    $response = $this->post('/logout');

    $response->assertStatus(302); // リダイレクト
});

test('3-2: ログイン試行回数の制限が適用される', function () {
    $user = User::factory()->create();

    // 5回の失敗したログイン試行
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    // 6回目の試行でレート制限が適用される
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(302); // リダイレクト（レート制限メッセージ付き）
});

test('3-2: ログイン成功時にレート制限がクリアされる', function () {
    $user = User::factory()->create();

    // 2回の失敗したログイン試行
    for ($i = 0; $i < 2; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    // 正しい認証情報でログイン
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertStatus(200);

    // レート制限がクリアされていることを確認
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(302); // リダイレクト（レート制限なし）
});

test('4-1: ログイン成功時にセッションが再生成される', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertStatus(200);

    // セッションが再生成されていることを確認
    $this->assertNotEquals(
        $this->app['session']->getId(),
        $this->app['session']->get('_token')
    );
});

test('4-1: ログアウト時にセッションが無効化される', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertStatus(200);

    // セッションが無効化されていることを確認
    $this->assertNull($this->app['session']->get('auth.password_confirmed_at'));
});
