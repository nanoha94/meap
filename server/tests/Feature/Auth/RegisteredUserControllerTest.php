<?php

use App\Models\User;
use App\Models\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $colors = [
        ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
        ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
        ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
    ];

    foreach ($colors as $color) {
        Color::create($color);
    }
});

// ===== store() メソッドのテストケース =====

test('2-5-1: 【store】 正常なユーザー登録', function () {
    Event::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->assertDatabaseHas('groups', []);
    $this->assertAuthenticated();

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'ユーザー登録に成功しました。',
        'data' => null,
    ]);

    Event::assertDispatched(Registered::class);
});

test('2-5-2: 【store】 グループとユーザーの関連付け確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    $this->assertDatabaseHas('group_user_mappings', [
        'user_id' => $user->id,
    ]);

    $groupUserMapping = DB::table('group_user_mappings')->where('user_id', $user->id)->first();
    $this->assertNotNull($groupUserMapping);
    $this->assertNotNull($groupUserMapping->group_id);

    $response->assertStatus(200);
});

test('2-5-3: 【store】 デフォルトデータの自動作成確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    $groupUserMapping = DB::table('group_user_mappings')->where('user_id', $user->id)->first();
    $groupId = $groupUserMapping->group_id;

    $this->assertDatabaseHas('shopping_categories', [
        'group_id' => $groupId,
        'name' => 'その他のカテゴリー',
        'is_default' => true,
    ]);

    $this->assertDatabaseHas('meal_categories', [
        'group_id' => $groupId,
        'name' => '昼食',
    ]);

    $this->assertDatabaseHas('ingredient_units', [
        'group_id' => $groupId,
        'name' => 'g',
    ]);

    $response->assertStatus(200);
});

test('2-5-4: 【store】 自動ログイン処理確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    $this->assertEquals($user->id, Auth::id());

    $response->assertStatus(200);
});

test('2-5-5: 【store】 セッション再生成確認', function () {
    $this->startSession();
    $oldSessionId = session()->getId();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(200);
    $this->assertAuthenticated();
    $this->assertNotEquals($oldSessionId, session()->getId());
});

test('2-5-6: 【store】 メール認証イベント発火確認', function () {
    Event::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    Event::assertDispatched(Registered::class, function ($event) {
        return $event->user->email === 'test@example.com';
    });

    $response->assertStatus(200);
});

test('2-5-7: 【store】 アバターシード生成確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    $this->assertNotNull($user->avatar_seed);
    $this->assertIsString($user->avatar_seed);
    $this->assertEquals(8, strlen($user->avatar_seed));

    $response->assertStatus(200);
});

test('2-5-8: 【store】 レスポンス形式確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data',
    ]);

    $response->assertJson([
        'success' => true,
        'data' => null,
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('2-5-9: 【store】 成功メッセージの国際化確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'ユーザー登録に成功しました。',
        'data' => null,
    ]);
});

test('2-5-10: 【store】 レート制限（1 分間に 6 回超過）', function () {
    $invalidPayload = [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ];

    for ($i = 0; $i < 6; $i++) {
        $this->post('/register', $invalidPayload);
    }

    $response = $this->post('/register', $invalidPayload);

    $response->assertStatus(429);
});

test('2-5-11: 【store】 バリデーションエラー（名前未入力）', function () {
    $response = $this->post('/register', [
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('name');
    $responseData = $response->json();
    $this->assertContains('名前は必ず指定してください。', $responseData['errors']['name']);
    $this->assertGuest();
});

test('2-5-12: 【store】 バリデーションエラー（メールアドレス未入力）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $responseData = $response->json();
    $this->assertContains('メールアドレスは必ず指定してください。', $responseData['errors']['email']);
    $this->assertGuest();
});

test('2-5-13: 【store】 バリデーションエラー（パスワード未入力）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('パスワードは必ず指定してください。', $responseData['errors']['password']);
    $this->assertGuest();
});

test('2-5-14: 【store】 バリデーションエラー（パスワード確認未入力）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('パスワードが一致しません。', $responseData['errors']['password']);
    $this->assertGuest();
});

test('2-5-15: 【store】 バリデーションエラー（パスワード確認不一致）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'DifferentPassword1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('パスワードが一致しません。', $responseData['errors']['password']);
    $this->assertGuest();
});

test('2-5-16: 【store】 バリデーションエラー（無効なメール形式）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $responseData = $response->json();
    $this->assertContains('メールアドレスには、有効なメールアドレスを指定してください。', $responseData['errors']['email']);
    $this->assertGuest();
});

test('2-5-17: 【store】 バリデーションエラー（メールアドレスが大文字）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'TEST@EXAMPLE.COM',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $responseData = $response->json();
    $this->assertContains('メールアドレスは、小文字のみで指定してください。', $responseData['errors']['email']);
    $this->assertGuest();
});

test('2-5-18: 【store】 バリデーションエラー（名前が 255 文字超過）', function () {
    $longName = str_repeat('a', 256);

    $response = $this->post('/register', [
        'name' => $longName,
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('name');
    $responseData = $response->json();
    $this->assertContains('名前は、255文字以内で指定してください。', $responseData['errors']['name']);
    $this->assertGuest();
});

test('2-5-19: 【store】 バリデーションエラー（メールアドレスが 255 文字超過）', function () {
    $longEmail = str_repeat('a', 250) . '@example.com';

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => $longEmail,
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $responseData = $response->json();
    $this->assertContains('メールアドレスは、255文字以内で指定してください。', $responseData['errors']['email']);
    $this->assertGuest();
});

test('2-5-20: 【store】 バリデーションエラー（パスワードが短すぎる）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Pass1!',
        'password_confirmation' => 'Pass1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('パスワードは、8文字以上で指定してください。', $responseData['errors']['password']);
    $this->assertGuest();
});

test('2-5-21: 【store】 バリデーションエラー（パスワードに英字が含まれない）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '12345678!',
        'password_confirmation' => '12345678!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('パスワードは、1文字以上の文字を含めてください。', $responseData['errors']['password']);
    $this->assertGuest();
});

test('2-5-22: 【store】 バリデーションエラー（パスワードに数字が含まれない）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password!',
        'password_confirmation' => 'Password!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('パスワードは、1文字以上の数字を含めてください。', $responseData['errors']['password']);
    $this->assertGuest();
});

test('2-5-23: 【store】 バリデーションエラー（パスワードに記号が含まれない）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $responseData = $response->json();
    $this->assertContains('パスワードは、1文字以上の記号を含めてください。', $responseData['errors']['password']);
    $this->assertGuest();
});

test('2-5-24: 【store】 バリデーションエラー（重複メールアドレス）', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $responseData = $response->json();
    $this->assertContains('登録に失敗しました。入力内容をご確認ください。', $responseData['errors']['email']);
    $this->assertGuest();
});

test('2-5-25: 【store】 既にログイン済みのユーザー', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => '既にログインしています。',
        'error_code' => 409,
    ]);
});

test('2-5-26: 【store】 データベース接続エラー', function () {
    DB::shouldReceive('transaction')
        ->andThrow(new \Exception('Database connection error'));

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(500);
    $this->assertGuest();

    \Mockery::close();
});

test('2-5-27: 【store】 トランザクション処理中の例外', function () {
    User::create([
        'name' => 'Existing User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'avatar_seed' => User::generateUniqueCustomId(),
    ]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $this->assertFalse(Auth::check());

    $userCount = User::where('email', 'test@example.com')->count();
    $this->assertEquals(1, $userCount, '重複メールアドレスにより新しいユーザーは作成されない');
});

test('2-5-28: 【store】 グループ作成失敗', function () {
    DB::shouldReceive('transaction')
        ->andThrow(new \Exception('Group creation failed'));

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(500);
    $this->assertGuest();
    \Mockery::close();
});

test('2-5-29: 【store】 GroupUserMapping 作成失敗', function () {
    DB::shouldReceive('commit')->andThrow(new \Exception('Attach failed'));

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(500);
    $this->assertGuest();
    \Mockery::close();
});

test('2-5-30: 【store】 アバターシード生成失敗', function () {
    $connection = \Mockery::mock($this->app['db']->connection())->makePartial();
    $connection->shouldReceive('select')->andThrow(new \Exception('Avatar seed generation failed'));
    DB::shouldReceive('connection')->andReturn($connection);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(500);
    $this->assertGuest();
    \Mockery::close();
});

test('2-5-31: 【store】 メール認証イベント発火失敗', function () {
    Event::listen(Registered::class, function () {
        throw new \Exception('Event dispatch failed');
    });

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(500);
    $this->assertGuest();

    Event::forget(Registered::class);
});

test('2-5-32: 【store】 自動ログイン失敗', function () {
    $guard = \Mockery::mock();
    $guard->shouldReceive('check')->andReturn(false);
    $guard->shouldReceive('user')->andReturn(null);

    Auth::shouldReceive('login')
        ->andThrow(new \Exception('Login failed'));
    Auth::shouldReceive('userResolver')->andReturn(fn ($guard = null) => null);
    Auth::shouldReceive('guard')->andReturn($guard);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(500);
    $this->assertGuest();
    \Mockery::close();
});
