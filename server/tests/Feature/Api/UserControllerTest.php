<?php

use App\Models\User;
use App\Models\Group;
use App\Models\Color;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

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
                    'url' => null,
                    'width' => null,
                    'height' => null,
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
                'name',
                'language',
                'avatar' => [
                    'seed',
                    'url',
                    'width',
                    'height'
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
    $user = User::factory()->create([
        'name' => 'Test User',
        'language' => 'ja',
        'avatar_seed' => 'testseed',
        'avatar_image_url' => 'https://example.com/avatar.jpg',
        'avatar_image_width' => 100,
        'avatar_image_height' => 100,
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
                    'url' => 'https://example.com/avatar.jpg',
                    'width' => 100,
                    'height' => 100,
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
                    'url',
                    'width',
                    'height'
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
                'name',
                'language',
                'avatar' => [
                    'seed',
                    'url',
                    'width',
                    'height'
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
            'avatar_seed' => 'testseed123'
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
            'avatar_seed'
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
            'avatar_seed' => $user->avatar_seed
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
            'avatar_seed'
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
