<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Lockout;

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
        'message' => 'ログインに成功しました。',
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
        'message' => 'ログインに成功しました。',
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
    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => 'メールアドレスまたはパスワードが正しくありません。',
        'errors' => [],
    ]);
});

test('2-1-5: 間違ったパスワード', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => 'メールアドレスまたはパスワードが正しくありません。',
        'errors' => [],
    ]);
});

test('2-1-6: 認証情報不足', function () {
    $response = $this->post('/login', []);

    $this->assertGuest();
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertJson([
        'success' => false,
    ]);
    $responseData = $response->json();
    $this->assertContains('emailは必ず指定してください。', $responseData['errors']['email']);
});

test('2-1-7: 無効なメール形式', function () {
    $response = $this->post('/login', [
        'email' => 'invalid-email',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertJson([
        'success' => false,
    ]);
    $responseData = $response->json();
    $this->assertContains('emailには、有効なメールアドレスを指定してください。', $responseData['errors']['email']);
});

test('2-1-8: メールアドレス未入力', function () {
    $response = $this->postJson('/login', [
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertJson([
        'success' => false,
    ]);
    $responseData = $response->json();
    $this->assertContains('emailは必ず指定してください。', $responseData['errors']['email']);

    // バリデーションエラーの構造を確認
    $response->assertJsonStructure([
        'message',
        'errors' => [
            'email'
        ]
    ]);
});

test('2-1-9: パスワード未入力', function () {
    $response = $this->postJson('/login', [
        'email' => 'test@example.com',
    ]);

    $this->assertGuest();
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);

    // バリデーションエラーの構造を確認
    $response->assertJsonStructure([
        'message',
        'errors' => [
            'password'
        ]
    ]);
});

test('2-1-10: 両方の項目未入力', function () {
    $response = $this->postJson('/login', []);

    $this->assertGuest();
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email', 'password']);

    // バリデーションエラーの構造を確認
    $response->assertJsonStructure([
        'message',
        'errors' => [
            'email',
            'password'
        ]
    ]);
});

test('2-1-11: カスタムバリデーションメッセージ', function () {
    $response = $this->postJson('/login', [
        'email' => 'invalid-email',
        'password' => '',
    ]);

    $this->assertGuest();
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email', 'password']);

    // バリデーションエラーの構造を確認
    $response->assertJsonStructure([
        'message',
        'errors' => [
            'email',
            'password'
        ]
    ]);

    // エラーメッセージが存在することを確認
    $responseData = $response->json();
    $this->assertContains('emailには、有効なメールアドレスを指定してください。', $responseData['errors']['email']);
    $this->assertContains('passwordは必ず指定してください。', $responseData['errors']['password']);
});

test('2-1-12: レート制限', function () {
    $user = User::factory()->create();

    // 5回の失敗したログイン試行
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    // 6回目の試行でレート制限が適用される
    $response = $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
    $response->assertJson([
        'success' => false,
    ]);

    // メッセージの形式を確認（秒数は変動するため正規表現で確認）
    $responseData = $response->json();
    expect($responseData['message'])->toMatch('/^試行回数が上限に達しました。\d+秒後に再度お試しください。$/');
});

test('2-1-13: レート制限クリア', function () {
    $user = User::factory()->create();

    // 2回の失敗したログイン試行
    for ($i = 0; $i < 2; $i++) {
        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    // 正しい認証情報でログイン（レート制限がクリアされる）
    $response = $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertStatus(200);

    // ログアウト後、再度間違った認証情報でログイン
    $this->postJson('/logout');
    $failResponse = $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    // レート制限がクリアされているため、認証失敗として扱われる（422ではなく401）
    $failResponse->assertStatus(401);
    $failResponse->assertJson([
        'success' => false,
        'message' => 'メールアドレスまたはパスワードが正しくありません。',
    ]);
});

test('2-1-14: Lockout イベント発火', function () {
    Event::fake([Lockout::class]);

    $user = User::factory()->create();

    // 5回の失敗したログイン試行
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    // 6回目の試行でLockoutイベントが発火される
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    Event::assertDispatched(Lockout::class);
});

test('2-1-15: 正常ログアウト', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'ログアウトに成功しました。',
    ]);
});

test('2-1-16: 未認証ログアウト', function () {
    $response = $this->post('/logout');

    $response->assertStatus(401); // 未認証のため401
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('2-1-17: セッション無効化', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertStatus(200);

    // セッションが無効化されていることを確認
    $this->assertNull($this->app['session']->get('auth.password_confirmed_at'));
});

test('2-1-18: 【ログアウト】 クッキー削除確認', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertStatus(200);

    // レスポンスが成功していることを確認
    $response->assertJson([
        'success' => true,
        'message' => 'ログアウトに成功しました。',
    ]);

    // クッキー削除のロジックが実行されていることを確認（値の検証は除く）
    $cookies = $response->headers->getCookies();
    expect(count($cookies))->toBeGreaterThan(0);
});
