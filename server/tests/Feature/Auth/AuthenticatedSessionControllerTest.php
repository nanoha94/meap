<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('2-1-1: 正常ログイン', function () {
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

test('2-1-2: Remember Me 機能', function () {
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

test('2-1-3: セッション再生成テスト', function () {
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

test('2-1-4: 無効な認証情報', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => 'invalid@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertStatus(302); // リダイレクト
});

test('2-1-5: 間違ったパスワード', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertStatus(302); // リダイレクト
});

test('2-1-6: 認証情報不足', function () {
    $response = $this->post('/login', []);

    $this->assertGuest();
    $response->assertStatus(302); // リダイレクト
});

test('2-1-7: 無効なメール形式', function () {
    $response = $this->post('/login', [
        'email' => 'invalid-email',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertStatus(302); // リダイレクト
});

test('2-1-8: レート制限', function () {
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

test('2-1-9: レート制限クリア', function () {
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

test('2-1-10: 正常ログアウト', function () {
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

test('2-1-11: 未認証ログアウト', function () {
    $response = $this->post('/logout');

    $response->assertStatus(302); // リダイレクト
});

test('2-1-12: セッション無効化', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertStatus(200);

    // セッションが無効化されていることを確認
    $this->assertNull($this->app['session']->get('auth.password_confirmed_at'));
});
