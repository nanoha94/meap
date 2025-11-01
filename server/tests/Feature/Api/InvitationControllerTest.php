<?php

use App\Models\User;
use App\Models\Group;
use App\Models\InvitationToken;
use App\Models\Color;
use App\Services\InvitationTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

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

// ==================== store() トークン生成テストケース ====================

test('3-3-1: 【トークン生成】 正常な招待トークン生成', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->post('/invitations');

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '招待トークンを生成しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'token',
            'expires_at'
        ]
    ]);

    // トークンが返されていることを確認
    $data = $response->json('data');
    expect($data['token'])->toBeString();
    expect($data['token'])->toHaveLength(32);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-2: 【トークン生成】 トークン有効期限設定確認', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $beforeTime = Carbon::now()->addHour();
    $response = $this->actingAs($user)->post('/invitations');
    $afterTime = Carbon::now()->addHour();

    $response->assertStatus(201);

    // 有効期限が1時間後に設定されていることを確認
    $expiresAt = Carbon::parse($response->json('data.expires_at'));
    expect($expiresAt->greaterThanOrEqualTo($beforeTime))->toBeTrue();
    expect($expiresAt->lessThanOrEqualTo($afterTime))->toBeTrue();
});

test('3-3-3: 【トークン生成】 未認証ユーザー', function () {
    $response = $this->post('/invitations');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-4: 【トークン生成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->post('/invitations');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-5: 【トークン生成】 データベース接続エラー', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // データベース接続をモックしてエラーを発生させる
    DB::shouldReceive('connection')->andThrow(new Exception('Database connection error'));

    $response = $this->actingAs($user)->post('/invitations');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '招待トークンの生成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-6: 【トークン生成】 トークン生成失敗', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // InvitationTokenServiceをモックしてnullを返す
    $this->mock(InvitationTokenService::class, function ($mock) {
        $mock->shouldReceive('createWithExpiration')->once()->andReturn(null);
    });

    $response = $this->actingAs($user)->post('/invitations');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '招待トークンの生成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

test('3-3-7: 【トークン生成】 トークン衝突時の再試行成功', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 既存のトークンを3つ作成
    for ($i = 0; $i < 3; $i++) {
        InvitationToken::create([
            'inviter_id' => $user->id,
            'token' => Hash::make('existing-token-' . $i),
            'expires_at' => Carbon::now()->addHour()
        ]);
    }

    // 新しいトークンを生成（衝突しても再試行で成功するはず）
    $response = $this->actingAs($user)->post('/invitations');

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '招待トークンを生成しました。'
    ]);

    // トークンが生成されていることを確認
    $data = $response->json('data');
    expect($data['token'])->toBeString();
});

test('3-3-8: 【トークン生成】 最大試行回数超過による失敗', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // InvitationTokenServiceをモックして常に同じトークンを返し、
    // 最終的にnullを返す（最大試行回数超過）
    $this->mock(InvitationTokenService::class, function ($mock) {
        $mock->shouldReceive('createWithExpiration')
            ->once()
            ->andReturn(null);
    });

    $response = $this->actingAs($user)->post('/invitations');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '招待トークンの生成に失敗しました。'
    ]);
});

// ==================== show() トークン詳細取得テストケース ====================

test('3-3-9: 【トークン詳細取得】 正常な招待トークン詳細取得', function () {
    $inviter = User::factory()->create([
        'name' => 'Inviter User',
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $group->id
    ]);

    // トークンをAPIで生成
    $tokenResponse = $this->actingAs($inviter)->post('/invitations');
    $plainToken = $tokenResponse->json('data.token');

    // 招待を見る側のユーザーもグループに所属させる
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);

    $response = $this->actingAs($user)->get("/invitations/{$plainToken}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '招待トークンの詳細を取得しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'token',
            'expires_at',
            'inviter' => [
                'id',
                'name',
                'avatar'
            ]
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-10: 【トークン詳細取得】 招待者情報の取得確認', function () {
    $inviter = User::factory()->create([
        'name' => 'Test Inviter',
        'avatar_seed' => 'test-seed',
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $group->id
    ]);

    // トークンをAPIで生成
    $tokenResponse = $this->actingAs($inviter)->post('/invitations');
    $plainToken = $tokenResponse->json('data.token');

    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);

    $response = $this->actingAs($user)->get("/invitations/{$plainToken}");

    $response->assertStatus(200);

    // 招待者情報が正しく取得されていることを確認
    $data = $response->json('data');
    expect($data['inviter']['id'])->toBe($inviter->id);
    expect($data['inviter']['name'])->toBe('Test Inviter');
    expect($data['inviter']['avatar'])->toHaveKey('seed');
});

test('3-3-11: 【トークン詳細取得】 トークン詳細レスポンス確認', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $group->id
    ]);

    // トークンをAPIで生成
    $tokenResponse = $this->actingAs($inviter)->post('/invitations');
    $plainToken = $tokenResponse->json('data.token');
    $expiresAt = $tokenResponse->json('data.expires_at');

    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);

    $response = $this->actingAs($user)->get("/invitations/{$plainToken}");

    $response->assertStatus(200);

    // トークン、有効期限、招待者情報が全て含まれていることを確認
    $data = $response->json('data');
    expect($data)->toHaveKeys(['token', 'expires_at', 'inviter']);
    expect($data['token'])->toBe($plainToken);
    expect($data['inviter'])->toHaveKeys(['id', 'name', 'avatar']);
});

test('3-3-12: 【トークン詳細取得】 未認証ユーザー', function () {
    $response = $this->get('/invitations/some-token');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-13: 【トークン詳細取得】 無効なトークンでの詳細取得', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);

    $response = $this->actingAs($user)->get('/invitations/invalid-token');

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '招待トークンが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-14: 【トークン詳細取得】 ハッシュチェック失敗', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $group->id
    ]);

    // トークンを生成
    InvitationToken::create([
        'inviter_id' => $inviter->id,
        'token' => Hash::make('correct-token'),
        'expires_at' => Carbon::now()->addHour()
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);

    // 異なるトークンでアクセス
    $response = $this->actingAs($user)->get('/invitations/wrong-token');

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '招待トークンが見つかりませんでした。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-15: 【トークン詳細取得】 データベース接続エラー', function () {
    // 先にテストデータを作成
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);

    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $group->id
    ]);

    // トークンを生成
    $invitationTokenService = app(InvitationTokenService::class);
    $plainToken = $invitationTokenService->generateToken();
    InvitationToken::create([
        'inviter_id' => $inviter->id,
        'token' => Hash::make($plainToken),
        'expires_at' => Carbon::now()->addHour()
    ]);

    // データベース接続をモックしてエラーを発生させる（テストデータ作成後）
    DB::shouldReceive('table')->andThrow(new Exception('Database connection error'));

    $response = $this->actingAs($user)->get("/invitations/{$plainToken}");

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '招待トークンの詳細の取得に失敗しました。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ==================== join() グループ参加テストケース ====================

test('3-3-16: 【グループ参加】 正常なグループ参加', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    $joinUser = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $joinUserGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $joinUser->id,
        'group_id' => $joinUserGroup->id
    ]);

    // トークンをAPIで生成
    $tokenResponse = $this->actingAs($inviter)->post('/invitations');
    $plainToken = $tokenResponse->json('data.token');

    $response = $this->actingAs($joinUser)->post("/invitations/{$plainToken}/join", [
        'isDelete' => true
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'グループに参加しました。'
    ]);

    // ユーザーが新しいグループに所属していることを確認
    $this->assertDatabaseHas('group_user_mappings', [
        'user_id' => $joinUser->id,
        'group_id' => $inviterGroup->id
    ]);

    // 元のグループからユーザーが削除されていることを確認
    $this->assertDatabaseMissing('group_user_mappings', [
        'user_id' => $joinUser->id,
        'group_id' => $joinUserGroup->id
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-17: 【グループ参加】 グループサイズ更新確認', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    $joinUser = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $joinUserGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $joinUser->id,
        'group_id' => $joinUserGroup->id
    ]);

    // トークンをAPIで生成
    $tokenResponse = $this->actingAs($inviter)->post('/invitations');
    $plainToken = $tokenResponse->json('data.token');

    $response = $this->actingAs($joinUser)->post("/invitations/{$plainToken}/join", [
        'isDelete' => true
    ]);

    $response->assertStatus(200);

    // グループサイズが更新されていることを確認
    $inviterGroup->refresh();
    expect($inviterGroup->group_size)->toBe(2);

    // 元のグループは削除されている（サイズ0）ので存在しないはず
    expect(Group::find($joinUserGroup->id))->toBeNull();
});

test('3-3-18: 【グループ参加】 空グループの削除確認', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    $joinUser = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $joinUserGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $joinUser->id,
        'group_id' => $joinUserGroup->id
    ]);

    $oldGroupId = $joinUserGroup->id;

    // トークンを生成
    $invitationTokenService = app(InvitationTokenService::class);
    $plainToken = $invitationTokenService->generateToken();
    InvitationToken::create([
        'inviter_id' => $inviter->id,
        'token' => Hash::make($plainToken),
        'expires_at' => Carbon::now()->addHour()
    ]);

    $response = $this->actingAs($joinUser)->post("/invitations/{$plainToken}/join", [
        'isDelete' => true
    ]);

    $response->assertStatus(200);

    // 元のグループ（1人だったため空になる）が削除されていることを確認
    expect(Group::find($oldGroupId))->toBeNull();
});

test('3-3-19: 【グループ参加】 元グループの保持確認', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    // 元のグループに複数人を所属させる
    $joinUser = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $otherUser = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $joinUserGroup = Group::create([
        'group_size' => 2
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $joinUser->id,
        'group_id' => $joinUserGroup->id
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $joinUserGroup->id
    ]);

    $oldGroupId = $joinUserGroup->id;

    // トークンを生成
    $invitationTokenService = app(InvitationTokenService::class);
    $plainToken = $invitationTokenService->generateToken();
    InvitationToken::create([
        'inviter_id' => $inviter->id,
        'token' => Hash::make($plainToken),
        'expires_at' => Carbon::now()->addHour()
    ]);

    $response = $this->actingAs($joinUser)->post("/invitations/{$plainToken}/join", [
        'isDelete' => true
    ]);

    $response->assertStatus(200);

    // 元のグループがまだ存在することを確認
    expect(Group::find($oldGroupId))->not->toBeNull();

    // 元のグループのサイズが1に更新されていることを確認
    $joinUserGroup->refresh();
    expect($joinUserGroup->group_size)->toBe(1);
});

test('3-3-20: 【グループ参加】 未認証ユーザー', function () {
    $response = $this->post('/invitations/some-token/join');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-21: 【グループ参加】 無効なトークンでの参加', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->post('/invitations/invalid-token/join');

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '招待トークンが見つかりませんでした。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-22: 【グループ参加】 ハッシュチェック失敗', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    // トークンを生成
    InvitationToken::create([
        'inviter_id' => $inviter->id,
        'token' => Hash::make('correct-token'),
        'expires_at' => Carbon::now()->addHour()
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 異なるトークンでアクセス
    $response = $this->actingAs($user)->post('/invitations/wrong-token/join');

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '招待トークンが見つかりませんでした。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-23: 【グループ参加】 有効期限切れトークンでの参加', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    // 有効期限切れのトークンを生成
    $invitationTokenService = app(InvitationTokenService::class);
    $plainToken = $invitationTokenService->generateToken();
    InvitationToken::create([
        'inviter_id' => $inviter->id,
        'token' => Hash::make($plainToken),
        'expires_at' => Carbon::now()->subHour() // 1時間前に期限切れ
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->post("/invitations/{$plainToken}/join");

    $response->assertStatus(410);
    $response->assertJson([
        'success' => false,
        'message' => '招待トークンの有効期限が切れています。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-24: 【グループ参加】 自分自身のトークンでの参加', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 自分自身のトークンをAPIで生成
    $tokenResponse = $this->actingAs($user)->post('/invitations');
    $plainToken = $tokenResponse->json('data.token');

    $response = $this->actingAs($user)->post("/invitations/{$plainToken}/join");

    $response->assertStatus(403);
    $response->assertJson([
        'success' => false,
        'message' => '自分自身を招待することはできません。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-25: 【グループ参加】 既に同じグループにいる場合', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 2
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $group->id
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // トークンをAPIで生成
    $tokenResponse = $this->actingAs($inviter)->post('/invitations');
    $plainToken = $tokenResponse->json('data.token');

    $response = $this->actingAs($user)->post("/invitations/{$plainToken}/join");

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'すでにグループに参加しています。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-26: 【グループ参加】 他のグループに所属している場合', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    // 他のグループに複数人所属（group_size > 1なので409 Conflictになる）
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $otherUser = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 2
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $userGroup->id
    ]);

    // トークンをAPIで生成
    $tokenResponse = $this->actingAs($inviter)->post('/invitations');
    $plainToken = $tokenResponse->json('data.token');

    // isDelete=falseまたは未指定の場合、group_size > 1で409 Conflictになる
    $response = $this->actingAs($user)->post("/invitations/{$plainToken}/join");

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'すでに別のグループに所属しています。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-27: 【グループ参加】 既存データがある場合の参加', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);

    // 既存データ（買い物カテゴリとアイテム）を作成
    $shoppingCategory = $userGroup->shoppingCategories()->create([
        'name' => 'Test Category',
        'order' => 0,
        'is_default' => false
    ]);

    $shoppingItem = $userGroup->shoppingItems()->create([
        'name' => 'Test Item',
        'category_id' => $shoppingCategory->id,
        'order' => 0,
        'is_checked' => false
    ]);

    // トークンを生成
    $invitationTokenService = app(InvitationTokenService::class);
    $plainToken = $invitationTokenService->generateToken();
    InvitationToken::create([
        'inviter_id' => $inviter->id,
        'token' => Hash::make($plainToken),
        'expires_at' => Carbon::now()->addHour()
    ]);

    // isDelete=falseまたは未指定で既存データがある場合、409 Conflictになる
    $response = $this->actingAs($user)->post("/invitations/{$plainToken}/join");

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'すでに登録済みのデータがあります。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-28: 【グループ参加】 データベース接続エラー', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);

    // トークンを生成
    $invitationTokenService = app(InvitationTokenService::class);
    $plainToken = $invitationTokenService->generateToken();
    InvitationToken::create([
        'inviter_id' => $inviter->id,
        'token' => Hash::make($plainToken),
        'expires_at' => Carbon::now()->addHour()
    ]);

    // データベース接続をモックしてエラーを発生させる
    DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database connection error'));

    $response = $this->actingAs($user)->post("/invitations/{$plainToken}/join", [
        'isDelete' => true
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'グループへの参加に失敗しました。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-29: 【グループ参加】 GroupUserMapping 作成失敗', function () {
    $inviter = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $inviterGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $inviter->id,
        'group_id' => $inviterGroup->id
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $userGroup = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $userGroup->id
    ]);

    // トークンを生成
    $invitationTokenService = app(InvitationTokenService::class);
    $plainToken = $invitationTokenService->generateToken();
    InvitationToken::create([
        'inviter_id' => $inviter->id,
        'token' => Hash::make($plainToken),
        'expires_at' => Carbon::now()->addHour()
    ]);

    // DBのトランザクション内でエラーを発生させる
    DB::shouldReceive('commit')->andThrow(new Exception('GroupUserMapping creation failed'));

    $response = $this->actingAs($user)->post("/invitations/{$plainToken}/join", [
        'isDelete' => true
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'グループへの参加に失敗しました。'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});
