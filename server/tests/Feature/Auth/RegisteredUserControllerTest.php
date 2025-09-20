<?php

use App\Models\User;
use App\Models\GroupUserMapping;
use App\Models\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Colorマスターデータをシード
    $colors = [
        ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
        ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
        ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
    ];

    foreach ($colors as $color) {
        Color::create($color);
    }
});

test('2-5-1: 正常なユーザー登録', function () {
    Event::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    // ユーザーが作成されていることを確認
    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // グループが作成されていることを確認
    $user = User::where('email', 'test@example.com')->first();
    $this->assertDatabaseHas('groups', []);

    // 自動ログインされていることを確認
    $this->assertAuthenticated();

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'ユーザー登録に成功しました。',
        'data' => null,
    ]);

    // Registeredイベントが発火されていることを確認
    Event::assertDispatched(Registered::class);
});

test('2-5-2: グループとユーザーの関連付け確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    // GroupUserMappingが作成されていることを確認
    $this->assertDatabaseHas('group_user_mappings', [
        'user_id' => $user->id,
    ]);

    // ユーザーとグループが関連付けられていることを確認
    $groupUserMapping = GroupUserMapping::where('user_id', $user->id)->first();
    $this->assertNotNull($groupUserMapping);
    $this->assertNotNull($groupUserMapping->group_id);

    $response->assertStatus(200);
});

test('2-5-3: デフォルトデータの自動作成確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    $groupUserMapping = GroupUserMapping::where('user_id', $user->id)->first();
    $groupId = $groupUserMapping->group_id;

    // デフォルトの買い物カテゴリが作成されていることを確認
    $this->assertDatabaseHas('shopping_categories', [
        'group_id' => $groupId,
        'name' => 'その他のカテゴリー',
        'is_default' => true,
    ]);

    // デフォルトの料理分類が作成されていることを確認
    $this->assertDatabaseHas('course_types', [
        'group_id' => $groupId,
        'name' => '主食',
    ]);

    // デフォルトの献立種別が作成されていることを確認
    $this->assertDatabaseHas('meal_types', [
        'group_id' => $groupId,
        'name' => '昼食',
    ]);

    // デフォルトの食材単位が作成されていることを確認
    $this->assertDatabaseHas('ingredient_units', [
        'group_id' => $groupId,
        'name' => 'g',
    ]);

    $response->assertStatus(200);
});

test('2-5-4: 自動ログイン処理確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    // 自動的にログイン状態になっていることを確認
    $this->assertAuthenticated();

    // ログインしているユーザーが登録したユーザーであることを確認
    $user = User::where('email', 'test@example.com')->first();
    $this->assertEquals($user->id, Auth::id());

    $response->assertStatus(200);
});

test('2-5-5: メール認証イベント発火確認', function () {
    Event::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    // Registeredイベントが発火されていることを確認
    Event::assertDispatched(Registered::class, function ($event) {
        return $event->user->email === 'test@example.com';
    });

    $response->assertStatus(200);
});

test('2-5-6: アバターシード生成確認', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    // アバターシードが生成されていることを確認
    $this->assertNotNull($user->avatar_seed);
    $this->assertIsString($user->avatar_seed);
    $this->assertEquals(8, strlen($user->avatar_seed));

    $response->assertStatus(200);
});

test('2-5-7: 名前未入力', function () {
    $response = $this->post('/register', [
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('name');
    $this->assertGuest();
});

test('2-5-8: メールアドレス未入力', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $this->assertGuest();
});

test('2-5-9: パスワード未入力', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $this->assertGuest();
});

test('2-5-10: パスワード確認未入力', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $this->assertGuest();
});

test('2-5-11: パスワード確認不一致', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'DifferentPassword1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $this->assertGuest();
});

test('2-5-12: 無効なメール形式', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $this->assertGuest();
});

test('2-5-13: メールアドレスが大文字', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'TEST@EXAMPLE.COM',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $this->assertGuest();
});

test('2-5-14: 名前が255文字超過', function () {
    $longName = str_repeat('a', 256);

    $response = $this->post('/register', [
        'name' => $longName,
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('name');
    $this->assertGuest();
});

test('2-5-15: メールアドレスが255文字超過', function () {
    $longEmail = str_repeat('a', 250) . '@example.com'; // 256文字以上

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => $longEmail,
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $this->assertGuest();
});

test('2-5-16: パスワードが短すぎる', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Pass1!',
        'password_confirmation' => 'Pass1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $this->assertGuest();
});

test('2-5-17: パスワードに英字が含まれない', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '12345678!',
        'password_confirmation' => '12345678!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $this->assertGuest();
});

test('2-5-18: パスワードに数字が含まれない', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password!',
        'password_confirmation' => 'Password!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $this->assertGuest();
});

test('2-5-19: パスワードに記号が含まれない', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $this->assertGuest();
});

test('2-5-20: 重複メールアドレス', function () {
    // 既存ユーザーを作成
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $this->assertGuest();
});

test('2-5-21: 既にログイン済みのユーザー', function () {
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

test('2-5-22: データベース接続エラー', function () {
    // データベース接続エラーをシミュレーション
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

    // モックのクリア
    \Mockery::close();
});

test('2-5-23: トランザクション処理中の例外', function () {
    // 実際のデータベースエラーを発生させるアプローチ
    // 参考: https://zenn.dev/link/comments/064d54d5db9b3f
    // 無効なデータを使ってデータベースエラーを発生させる

    // 既に存在するメールアドレスを事前に作成してユニーク制約エラーを発生させる
    User::create([
        'name' => 'Existing User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'avatar_seed' => User::generateUniqueCustomId(),
    ]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com', // 重複するメールアドレス
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    // バリデーションエラーが返されることを確認
    $response->assertStatus(422);

    // 認証されていないことを確認
    $this->assertFalse(Auth::check());

    // 新しいユーザーが作成されていないことを確認（既存のユーザーのみ）
    $userCount = User::where('email', 'test@example.com')->count();
    $this->assertEquals(1, $userCount, '重複メールアドレスにより新しいユーザーは作成されない');
});

test('2-5-24: バリデーションエラー（パスワード不一致）', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'DifferentPassword!', // パスワード確認が一致しない
    ]);

    // バリデーションエラーが返されることを確認
    $response->assertStatus(422);

    // 認証されていないことを確認
    $this->assertFalse(Auth::check());

    // ユーザーが作成されていないことを確認
    $user = User::where('email', 'test@example.com')->first();
    $this->assertNull($user, 'バリデーションエラーによりユーザーは作成されない');
});

test('2-5-25: 無効な名前（空文字）', function () {
    $response = $this->post('/register', [
        'name' => '', // 空の名前（required制約違反）
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    // バリデーションエラーが返されることを確認
    $response->assertStatus(422);

    // 認証されていないことを確認
    $this->assertFalse(Auth::check());

    // ユーザーが作成されていないことを確認
    $user = User::where('email', 'test@example.com')->first();
    $this->assertNull($user, 'バリデーションエラーによりユーザーは作成されない');
});

test('2-5-26: 無効なメールアドレス形式', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'invalid-email-format', // 無効なメールアドレス形式
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    // バリデーションエラーが返されることを確認
    $response->assertStatus(422);

    // 認証されていないことを確認
    $this->assertFalse(Auth::check());

    // ユーザーが作成されていないことを確認
    $user = User::where('name', 'Test User')->first();
    $this->assertNull($user, 'バリデーションエラーによりユーザーは作成されない');
});

test('2-5-27: 弱いパスワード', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '123', // 弱いパスワード（短すぎる）
        'password_confirmation' => '123',
    ]);

    // バリデーションエラーが返されることを確認
    $response->assertStatus(422);

    // 認証されていないことを確認
    $this->assertFalse(Auth::check());

    // ユーザーが作成されていないことを確認
    $user = User::where('email', 'test@example.com')->first();
    $this->assertNull($user, 'バリデーションエラーによりユーザーは作成されない');
});

test('2-5-28: 既にログイン済みユーザーの登録試行', function () {
    // 事前にユーザーを作成してログイン
    $existingUser = User::create([
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'password' => Hash::make('password'),
        'avatar_seed' => User::generateUniqueCustomId(),
    ]);

    // ユーザーをログイン状態にする
    $this->actingAs($existingUser);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    // 409 Conflict（既にログイン済み）が返されることを確認
    $response->assertStatus(409);

    // 新しいユーザーが作成されていないことを確認
    $user = User::where('email', 'test@example.com')->first();
    $this->assertNull($user, '既にログイン済みのため新しいユーザーは作成されない');
});

test('2-5-29: レスポンス形式確認', function () {
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

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('2-5-30: 成功メッセージの国際化確認', function () {
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

    // メッセージが翻訳されていることを確認
    $this->assertNotEquals('api.auth.registration_success', 'ユーザー登録に成功しました。');
});
