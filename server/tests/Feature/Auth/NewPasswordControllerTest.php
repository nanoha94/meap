<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

// ===== store() メソッドのテストケース =====

test('2-3-1: 【store】 正常なパスワードリセット', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response =  $this->post('/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'パスワードがリセットされました。']);
});

test('2-3-2: 【store】 パスワードリセット後に旧セッションが無効化される', function () {
    config(['session.driver' => 'database']);

    $user = User::factory()->create();
    $token = Password::createToken($user);

    $loginResponse = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $loginResponse->assertStatus(200);

    $sessionCookieName = config('session.cookie');
    $sessionCookieValue = $loginResponse->getCookie($sessionCookieName)->getValue();

    $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);

    $this->getJson('/user')->assertStatus(200);

    $this->withoutMiddleware(\Illuminate\Auth\Middleware\RedirectIfAuthenticated::class)
        ->post('/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertStatus(200);

    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);

    $this->flushSession();
    $this->app['auth']->forgetGuards();

    $this->withCookie($sessionCookieName, $sessionCookieValue)
        ->getJson('/user')
        ->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => '認証が必要です。',
        ]);
});

test('2-3-3: 【store】 バリデーションエラー（トークン未入力）', function () {
    $response = $this->postJson('/password/reset', [
        'email' => 'user@example.com',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('token');
    $responseData = $response->json();
    $this->assertContains('tokenは必ず指定してください。', $responseData['errors']['token']);
});

test('2-3-4: 【store】 バリデーションエラー（メールアドレス未入力）', function () {
    $response = $this->postJson('/password/reset', [
        'token' => 'some-token',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $responseData = $response->json();
    $this->assertContains('emailは必ず指定してください。', $responseData['errors']['email']);
});

test('2-3-5: 【store】 バリデーションエラー（パスワード未入力）', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは必ず指定してください。', $responseData['errors']['password']);
});

test('2-3-6: 【store】 バリデーションエラー（パスワード確認未入力）', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'NewPassword1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password_confirmation');
    $responseData = $response->json();
    $this->assertContains('password_confirmationは必ず指定してください。', $responseData['errors']['password_confirmation']);
});

test('2-3-7: 【store】 バリデーションエラー（パスワード確認不一致）', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordが一致しません。', $responseData['errors']['password']);
});

test('2-3-8: 【store】 バリデーションエラー（パスワードが短すぎる）', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは、8文字以上で指定してください。', $responseData['errors']['password']);
});

test('2-3-9: 【store】 バリデーションエラー（パスワードに英字が含まれない）', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => '12345678!',
        'password_confirmation' => '12345678!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは、1文字以上の文字を含めてください。', $responseData['errors']['password']);
});

test('2-3-10: 【store】 バリデーションエラー（パスワードに数字が含まれない）', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'Password!',
        'password_confirmation' => 'Password!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは、1文字以上の数字を含めてください。', $responseData['errors']['password']);
});

test('2-3-11: 【store】 バリデーションエラー（パスワードに記号が含まれない）', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは、1文字以上の記号を含めてください。', $responseData['errors']['password']);
});

test('2-3-12: 【store】 無効なトークン', function () {
    User::factory()->create(['email' => 'user@example.com']);

    $response = $this->postJson('/password/reset', [
        'email' => 'user@example.com',
        'token' => 'invalid-token',
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ]);

    $response->assertStatus(404);
    $response->assertJson(['message' => '指定されたパスワードリセットトークンは無効です。']);
});

test('2-3-13: 【store】 ユーザーが存在しない', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $user->delete();
    $response = $this->postJson('/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['message' => '指定されたメールアドレスのユーザーが見つかりませんでした。']);
});
