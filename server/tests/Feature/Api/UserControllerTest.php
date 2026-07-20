<?php

use App\Models\User;
use App\Models\Group;
use App\Models\Color;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

test('3-11-1: 【一覧取得】 正常なユーザー一覧取得', function () {
    // ユーザーとグループを作成（メール認証済み）
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    // 軽量なグループ作成（テスト用）
    $group = Group::create([
        'group_size' => 1
    ]);

    // ユーザーをグループに所属させる
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->get('/users');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'ユーザーを1件取得しました。',
        'data' => [
            [
                'name' => $user->name,
                'language' => 'ja',
                'avatar' => [
                    'seed' => null,
                    'image' => null,
                ]
            ]
        ],
        'total' => 1
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'language',
                'avatar' => [
                    'seed',
                    'image'
                ]
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-2: 【一覧取得】 グループ内ユーザー情報の確認', function () {
    // ユーザーとグループを作成（メール認証済み）
    $user1 = User::factory()->create([
        'name' => 'User 1',
        'email_verified_at' => now()
    ]);
    $user2 = User::factory()->create([
        'name' => 'User 2',
        'email_verified_at' => now()
    ]);

    // 軽量なグループ作成（テスト用）
    $group = Group::create([
        'group_size' => 2
    ]);

    // 両ユーザーを同じグループに所属させる
    DB::table('group_user_mappings')->insert([
        'user_id' => $user1->id,
        'group_id' => $group->id
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user2->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user1)->get('/users');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 2人のユーザーが返されることを確認
    expect($responseData)->toHaveCount(2);

    // 両方のユーザーが含まれていることを確認
    $userNames = collect($responseData)->pluck('name')->toArray();
    expect($userNames)->toContain('User 1');
    expect($userNames)->toContain('User 2');

    // メッセージの確認
    $response->assertJson([
        'success' => true,
        'message' => 'ユーザーを2件取得しました。',
        'total' => 2
    ]);
});

test('3-11-3: 【一覧取得】 ユーザー情報フォーマット確認', function () {
    $image = Image::create([
        'src' => '/storage/images/test.jpg',
        'width' => 100,
        'height' => 100
    ]);

    $user = User::factory()->create([
        'name' => 'Test User',
        'language' => 'ja',
        'avatar_seed' => 'testseed',
        'avatar_image_id' => $image->id,
        'email_verified_at' => now()
    ]);

    // 軽量なグループ作成（テスト用）
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->get('/users');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'ユーザーを1件取得しました。',
        'data' => [
            [
                'name' => 'Test User',
                'language' => 'ja',
                'avatar' => [
                    'seed' => 'testseed',
                    'image' => [
                        'id' => $image->id,
                        'src' => '/storage/images/test.jpg',
                        'width' => 100,
                        'height' => 100,
                    ]
                ]
            ]
        ],
        'total' => 1
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'language',
                'avatar' => [
                    'seed',
                    'image' => [
                        'id',
                        'src',
                        'width',
                        'height'
                    ]
                ]
            ]
        ],
        'total'
    ]);
});

test('3-11-4: 【一覧取得】 グループに 1 人のみの場合', function () {
    $user = User::factory()->create([
        'name' => 'Single User',
        'email_verified_at' => now()
    ]);

    // 軽量なグループ作成（テスト用）
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->get('/users');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 自分自身の情報のみが返されることを確認
    expect($responseData)->toHaveCount(1);
    expect($responseData[0]['name'])->toBe('Single User');
    expect($response->json('total'))->toBe(1);

    // メッセージの確認
    $response->assertJson([
        'success' => true,
        'message' => 'ユーザーを1件取得しました。',
        'total' => 1
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'language',
                'avatar' => [
                    'seed',
                    'image'
                ]
            ]
        ],
        'total'
    ]);
});

test('3-11-5: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/users');

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

test('3-11-6: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/users');

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

test('3-11-7: 【一覧取得】 データベース接続エラー', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    // 軽量なグループ作成（テスト用）
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // UserServiceのindexメソッドでデータベース接続エラーを発生させる
    $this->mock(\App\Services\UserService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($user)->get('/users');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーの取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-8: 【一覧取得】 UserService 例外', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    // 軽量なグループ作成（テスト用）
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // UserServiceのindexメソッドで例外を発生させる
    $this->mock(\App\Services\UserService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Service exception'));
    });

    $response = $this->actingAs($user)->get('/users');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーの取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-9: 【詳細取得】 正常なユーザー情報取得', function () {
    // ユーザーを作成（メール認証済み）
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'email_verified_at' => now(),
        'avatar_seed' => 'testseed123'
    ]);

    $response = $this->actingAs($user)->get('/user');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'ユーザーを取得しました。',
        'data' => [
            'id' => $user->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => [
                'seed' => 'testseed123',
                'image' => null
            ]
        ]
    ]);

    // email_verified_at は日付形式で返されるため、個別に確認
    $responseData = $response->json('data');
    expect($responseData['email_verified_at'])->not->toBeNull();
    expect($responseData['email_verified_at'])->toBeString();

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name',
            'email',
            'email_verified_at',
            'avatar' => [
                'seed',
                'image'
            ]
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-10: 【詳細取得】 メール未認証ユーザー', function () {
    // ユーザーを作成（メール未認証）
    $user = User::factory()->create([
        'email_verified_at' => null
    ]);

    $response = $this->actingAs($user)->get('/user');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'ユーザーを取得しました。',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => null,
            'avatar' => [
                'seed' => $user->avatar_seed,
                'image' => null
            ]
        ]
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name',
            'email',
            'email_verified_at',
            'avatar' => [
                'seed',
                'image'
            ]
        ]
    ]);
});

test('3-11-11: 【詳細取得】 未認証ユーザー', function () {
    $response = $this->get('/user');

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

// ===== update() メソッドのテストケース =====

test('3-11-12: 【更新】 名前のみ更新', function () {
    $image = Image::create([
        'src' => '/storage/images/users/dummy/test.jpg',
        'width' => 100,
        'height' => 100
    ]);
    $user = User::factory()->create([
        'name' => 'Old Name',
        'avatar_image_id' => $image->id,
        'email_verified_at' => now()
    ]);

    $response = $this->actingAs($user)->putJson('/user', [
        'name' => 'New Name'
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => null
    ]);
    // メッセージは更新後のユーザー名を含む形式
    expect($response->json('message'))->toBe('ユーザー(New Name)を更新しました。');

    // データベースで名前が更新され、avatar_image_id は送信されないため null になることを確認
    $user->refresh();
    expect($user->name)->toBe('New Name');
    expect($user->avatar_image_id)->toBeNull();

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-13: 【更新】 アバター画像IDのみ更新', function () {
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

    $image = Image::create([
        'src' => "/storage/images/users/{$user->id}/test.jpg",
        'width' => 100,
        'height' => 100
    ]);

    $response = $this->actingAs($user)->putJson('/user', [
        'avatar_image_id' => $image->id
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => null
    ]);
    // メッセージは更新後のユーザー名を含む形式
    expect($response->json('message'))->toContain('ユーザー(');
    expect($response->json('message'))->toContain(')を更新しました。');

    // データベースでアバター画像IDが更新されていることを確認
    $user->refresh();
    expect($user->avatar_image_id)->toBe($image->id);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-14: 【更新】 名前とアバター画像IDを同時に更新', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email_verified_at' => now()
    ]);

    $group = Group::create([
        'group_size' => 1
    ]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $image = Image::create([
        'src' => "/storage/images/users/{$user->id}/test.jpg",
        'width' => 100,
        'height' => 100
    ]);

    $response = $this->actingAs($user)->putJson('/user', [
        'name' => 'New Name',
        'avatar_image_id' => $image->id
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => null
    ]);
    // メッセージは更新後のユーザー名を含む形式
    expect($response->json('message'))->toBe('ユーザー(New Name)を更新しました。');

    // データベースで両方が更新されていることを確認
    $user->refresh();
    expect($user->name)->toBe('New Name');
    expect($user->avatar_image_id)->toBe($image->id);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-15: 【更新】 アバター画像IDをnullに設定（削除）', function () {
    // ストレージに画像ファイルを作成
    $filePath = 'images/test.jpg';
    Storage::disk('public')->put($filePath, 'fake image content');

    $image = Image::create([
        'src' => '/storage/' . $filePath,
        'width' => 100,
        'height' => 100
    ]);

    $user = User::factory()->create([
        'avatar_image_id' => $image->id,
        'email_verified_at' => now()
    ]);

    $imageId = $image->id; // 後で確認するために保存
    $userName = $user->name; // メッセージ検証用に保存

    $response = $this->actingAs($user)->putJson('/user', [
        'avatar_image_id' => null
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => null
    ]);
    // メッセージは更新後のユーザー名を含む形式
    expect($response->json('message'))->toBe("ユーザー({$userName})を更新しました。");

    // データベースでアバター画像IDがnullになっていることを確認
    $user->refresh();
    expect($user->avatar_image_id)->toBeNull();

    // リレーション経由で画像が取得できないことを確認
    expect($user->avatarImage)->toBeNull();

    // 画像レコード自体はデータベースに存在し続けることを確認（削除されていない）
    $this->assertDatabaseHas('images', [
        'id' => $imageId
    ]);

    // 画像ファイルはストレージに存在し続けることを確認（削除されていない）
    expect(Storage::disk('public')->exists($filePath))->toBeTrue();

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-16: 【更新】 アバター画像IDキーを省略した場合、nullになる', function () {
    $filePath = 'images/test.jpg';
    Storage::disk('public')->put($filePath, 'fake image content');
    $image = Image::create([
        'src' => '/storage/' . $filePath,
        'width' => 100,
        'height' => 100
    ]);
    $user = User::factory()->create([
        'name' => 'Test User',
        'avatar_image_id' => $image->id,
        'email_verified_at' => now()
    ]);
    $userName = $user->name;

    // avatar_image_id を送らずに名前のみ送信（キー省略）
    $response = $this->actingAs($user)->putJson('/user', [
        'name' => $userName
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => null
    ]);
    expect($response->json('message'))->toBe("ユーザー({$userName})を更新しました。");

    $user->refresh();
    expect($user->avatar_image_id)->toBeNull();
    expect($user->avatarImage)->toBeNull();

    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-17: 【更新】 バリデーションエラー（name が文字列でない）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $response = $this->actingAs($user)->putJson('/user', [
        'name' => 12345
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-18: 【更新】 バリデーションエラー（name が 255 文字超過）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $response = $this->actingAs($user)->putJson('/user', [
        'name' => str_repeat('a', 256)
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-19: 【更新】 バリデーションエラー（avatar_image_id が UUID 形式でない）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $response = $this->actingAs($user)->putJson('/user', [
        'avatar_image_id' => 'invalid-uuid'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['avatar_image_id']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'avatar_image_id'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-20: 【更新】 avatar_image_id が存在しない画像ID', function () {
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

    // 存在しないUUIDを生成
    $nonExistentUuid = '550e8400-e29b-41d4-a716-446655440000';

    $response = $this->actingAs($user)->putJson('/user', [
        'avatar_image_id' => $nonExistentUuid
    ]);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-21: 【更新】 未認証ユーザー', function () {
    $response = $this->putJson('/user', [
        'name' => 'New Name'
    ]);

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

test('3-11-22: 【更新】 UserService 例外', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    // UserServiceのupdateProfileメソッドで例外を発生させる
    $this->mock(\App\Services\UserService::class, function ($mock) {
        $mock->shouldReceive('updateProfile')
            ->once()
            ->andThrow(new \Exception('Service exception'));
    });

    $response = $this->actingAs($user)->putJson('/user', [
        'name' => 'New Name'
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーの更新に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ===== destroy() メソッドのテストケース =====

test('3-11-23: 【削除】 正常なアカウント削除', function () {
    $user = User::factory()->create([
        'name' => 'Delete Me',
        'email_verified_at' => now()
    ]);

    $group = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $userId = $user->id;
    $groupId = $group->id;
    $userName = $user->name;

    $response = $this->actingAs($user)->delete('/user');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => "ユーザー({$userName})を削除しました。",
        'data' => null
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);
    $response->assertHeader('Content-Type', 'application/json');

    // ユーザーが削除されていること
    $this->assertDatabaseMissing('users', ['id' => $userId]);

    // 当該ユーザーの personal_access_tokens が削除されていること
    $tokenCount = DB::table('personal_access_tokens')
        ->where('tokenable_id', $userId)
        ->where('tokenable_type', \App\Models\User::class)
        ->count();
    expect($tokenCount)->toBe(0);

    // group_user_mappings から当該ユーザーの紐づきが削除されていること
    $this->assertDatabaseMissing('group_user_mappings', ['user_id' => $userId]);

    // グループが1人のみだったため refreshGroupSize によりグループも削除されていること
    $this->assertDatabaseMissing('groups', ['id' => $groupId]);
});

test('3-11-24: 【削除】 アカウント削除時にユーザー配下の画像ディレクトリと images レコードが削除される', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'name' => 'Delete Me With Images',
        'email_verified_at' => now()
    ]);

    $group = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $userId = $user->id;
    $userName = $user->name;

    // ユーザー配下の画像ディレクトリとファイルを作成
    $dirPath = 'images/users/' . $userId;
    $filePath = $dirPath . '/test.jpg';
    Storage::disk('public')->put($filePath, 'fake image content');
    expect(Storage::disk('public')->exists($filePath))->toBeTrue();

    // Image レコード作成（deleteImagesByUser の LIKE 条件に合う src）
    $image = Image::create([
        'src' => url('/storage/' . $filePath),
        'width' => 100,
        'height' => 100
    ]);
    $imageId = $image->id;
    $this->assertDatabaseHas('images', ['id' => $imageId]);

    $response = $this->actingAs($user)->delete('/user');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => "ユーザー({$userName})を削除しました。",
        'data' => null
    ]);

    // ユーザーが削除されていること
    $this->assertDatabaseMissing('users', ['id' => $userId]);

    // 当該ユーザー配下の images レコードが削除されていること
    $this->assertDatabaseMissing('images', ['id' => $imageId]);

    // 当該ユーザー配下の画像ディレクトリが削除されていること（ImageService::deleteImagesByUser のディレクトリ削除）
    expect(Storage::disk('public')->exists($dirPath))->toBeFalse();
});

test('3-11-25: 【削除】 アカウント削除後のレスポンスにクッキー削除が含まれる', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $group = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->delete('/user');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => null
    ]);

    $cookies = $response->headers->getCookies();
    expect($cookies)->not->toBeEmpty();

    $sessionCookieName = config('session.cookie');
    $sessionCookies = array_filter($cookies, fn($c) => $c->getName() === $sessionCookieName);
    $xsrfCookies = array_filter($cookies, fn($c) => $c->getName() === 'XSRF-TOKEN');

    expect($sessionCookies)->not->toBeEmpty();
    expect($xsrfCookies)->not->toBeEmpty();
});

test('3-11-26: 【削除】 未認証ユーザー', function () {
    $response = $this->delete('/user');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-11-27: 【削除】 UserService 例外', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $this->mock(\App\Services\UserService::class, function ($mock) {
        $mock->shouldReceive('deleteAccount')
            ->once()
            ->andThrow(new \Exception('Service exception'));
    });

    $response = $this->actingAs($user)->delete('/user');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーの削除に失敗しました。'
    ]);
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

