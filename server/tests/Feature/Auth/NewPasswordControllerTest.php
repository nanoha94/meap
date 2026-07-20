<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('2-3-1: 正常なパスワードリセット', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response =  $this->post('/password/reset', [
        'token' => $token,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'パスワードがリセットされました。']);
});

DB::shouldReceive('table->where->get->filter')
    ->andReturn(collect([])); // Return an empty collection for invalid token

test('2-3-2: トークン未入力', function () {
    $response = $this->postJson('/password/reset', [
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('token');
    $responseData = $response->json();
    $this->assertContains('tokenは必ず指定してください。', $responseData['errors']['token']);
});

test('2-3-3: パスワード未入力', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'token' => $token,
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは必ず指定してください。', $responseData['errors']['password']);
});

test('2-3-4: パスワード確認未入力', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'token' => $token,
        'password' => 'NewPassword1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password_confirmation');
    $responseData = $response->json();
    $this->assertContains('password_confirmationは必ず指定してください。', $responseData['errors']['password_confirmation']);
});

test('2-3-5: パスワード確認不一致', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordが一致しません。', $responseData['errors']['password']);
});

test('2-3-6: パスワードが短すぎる', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'token' => $token,
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは、8文字以上で指定してください。', $responseData['errors']['password']);
});

test('2-3-7: パスワードに英字が含まれない', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'token' => $token,
        'password' => '12345678!',
        'password_confirmation' => '12345678!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは、1文字以上の文字を含めてください。', $responseData['errors']['password']);
});

test('2-3-8: パスワードに数字が含まれない', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'token' => $token,
        'password' => 'Password!',
        'password_confirmation' => 'Password!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは、1文字以上の数字を含めてください。', $responseData['errors']['password']);
});

test('2-3-9: パスワードに記号が含まれない', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/password/reset', [
        'token' => $token,
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('passwordは、1文字以上の記号を含めてください。', $responseData['errors']['password']);
});

test('2-3-10: 無効なトークン', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/password/reset', [
        'token' => 'invalid-token',
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ]);

    $response->assertStatus(404);
    $response->assertJson(['message' => '指定されたパスワードリセットトークンは無効です。']);
});

test('2-3-11: ユーザーが存在しない', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $user->delete();
    $response = $this->postJson('/password/reset', [
        'token' => $token,
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['message' => '指定されたメールアドレスのユーザーが見つかりませんでした。']);
});
