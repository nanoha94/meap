<?php

use App\Models\User;
use App\Models\Group;
use App\Models\Color;
use Illuminate\Support\Facades\DB;
use App\Services\MealCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $this->group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $this->user->id,
        'group_id' => $this->group->id
    ]);

    // ユーザーとグループの関係をリフレッシュ
    $this->user->refresh();
    $this->user->load('groups');

    // テスト用の色IDを取得
    $this->yellowColorId = Color::where('name', 'イエロー')->first()->id;
    $this->redColorId = Color::where('name', 'レッド')->first()->id;
    $this->blueColorId = Color::where('name', 'ブルー')->first()->id;
});

// ===== index() メソッドのテストケース =====

test('3-5-1: 【一覧取得】 正常な献立カテゴリ一覧取得', function () {
    // テスト用の献立カテゴリをAPIで作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;

    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);
    $mealCategory2Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '昼食')->first()->id;

    $response = $this->actingAs($this->user)->get('/meal-categories');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立カテゴリを2件取得しました。',
        'data' => [
            [
                'id' => $mealCategory1Id,
                'name' => '朝食',
                'colorCodeHex' => '#F5B12E',
                'order' => 0
            ],
            [
                'id' => $mealCategory2Id,
                'name' => '昼食',
                'colorCodeHex' => '#EC3D33',
                'order' => 1
            ]
        ],
        'total' => 2
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'colorCodeHex',
                'order'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-2: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用の献立カテゴリをAPIで作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);

    $response = $this->actingAs($this->user)->get('/meal-categories');

    $response->assertStatus(200);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'colorCodeHex',
                'order'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-3: 【一覧取得】 order 順での取得確認', function () {
    // 異なるorder順で献立カテゴリをAPIで作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '夕食',
        'colorId' => $this->blueColorId,
        'order' => 2
    ]);
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);

    $response = $this->actingAs($this->user)->get('/meal-categories');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // order順で並んでいることを確認
    expect($responseData[0]['name'])->toBe('朝食');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('昼食');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('夕食');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-5-4: 【一覧取得】 空のリスト取得', function () {
    // 献立カテゴリが存在しない状態でテスト
    $response = $this->actingAs($this->user)->get('/meal-categories');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立カテゴリを0件取得しました。',
        'data' => [],
        'total' => 0
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data',
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-5: 【一覧取得】 他グループの献立カテゴリは取得されない', function () {
    // 自グループの献立カテゴリを作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);

    // 他のグループのユーザーと献立カテゴリをAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $this->actingAs($otherUser)->post('/meal-categories', [
        'name' => '他グループの献立カテゴリ',
        'colorId' => $this->redColorId,
        'order' => 0
    ]);

    $response = $this->actingAs($this->user)->get('/meal-categories');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 自グループの献立カテゴリのみが取得される
    expect($responseData)->toHaveCount(1);
    expect($responseData[0]['name'])->toBe('朝食');
});

test('3-5-6: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/meal-categories');

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

test('3-5-7: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/meal-categories');

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

test('3-5-8: 【一覧取得】 データベース接続エラー', function () {
    // MealCategoryServiceをモックして例外を発生させる
    $mock = \Mockery::mock(MealCategoryService::class);
    $mock->shouldReceive('index')
        ->once()
        ->andThrow(new \Exception('Database connection error'));

    $this->app->instance(MealCategoryService::class, $mock);

    $response = $this->actingAs($this->user)->get('/meal-categories');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立カテゴリの取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ===== store() メソッドのテストケース =====

test('3-5-9: 【新規作成】 正常な献立カテゴリ作成', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '献立カテゴリ(朝食)を作成しました。'
    ]);
    $response->assertJsonPath('data', null);

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('meal_categories', [
        'group_id' => $this->group->id,
        'name' => '朝食',
        'color_id' => $this->yellowColorId,
        'order' => 0
    ]);

    // レスポンス構造の確認（success + message + data(null)）
    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-10: 【新規作成】 レスポンス形式確認', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(201);

    // レスポンス構造の確認（success + message + data(null)）
    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);
    $response->assertJsonPath('data', null);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-11: 【新規作成】 バリデーションエラー（献立カテゴリ名未入力）', function () {
    $data = [
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('nameは必ず指定してください。', $responseData['errors']['name']);

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

test('3-5-12: 【新規作成】 バリデーションエラー（献立カテゴリ名が文字列以外）', function () {
    $data = [
        'name' => 123,
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('nameは文字列を指定してください。', $responseData['errors']['name']);

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

test('3-5-13: 【新規作成】 バリデーションエラー（献立カテゴリ名が 255 文字超過）', function () {
    $data = [
        'name' => str_repeat('a', 256),
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('nameは、255文字以内で指定してください。', $responseData['errors']['name']);

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

test('3-5-14: 【新規作成】 バリデーションエラー（色 ID 未入力）', function () {
    $data = [
        'name' => '朝食',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['colorId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('colorIdは必ず指定してください。', $responseData['errors']['colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-15: 【新規作成】 バリデーションエラー（色 ID が UUID 形式でない）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => 'invalid-uuid',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['colorId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('colorIdに有効なUUIDを指定してください。', $responseData['errors']['colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-16: 【新規作成】 バリデーションエラー（色 ID が存在しない）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => '00000000-0000-0000-0000-000000000000',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['colorId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('選択されたcolorIdは正しくありません。', $responseData['errors']['colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-17: 【新規作成】 バリデーションエラー（order 値が未入力）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('orderは必ず指定してください。', $responseData['errors']['order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-18: 【新規作成】 バリデーションエラー（order 値が数値以外）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 'abc'
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('orderは整数で指定してください。', $responseData['errors']['order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-19: 【新規作成】 バリデーションエラー（order 値が負の値）', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => -1
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('orderには、0以上の数字を指定してください。', $responseData['errors']['order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-20: 【新規作成】 未認証ユーザー', function () {
    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->post('/meal-categories', $data);

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

test('3-5-21: 【新規作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($user)->post('/meal-categories', $data);

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

test('3-5-22: 【新規作成】 データベース接続エラー', function () {
    // mealCategoryServiceをモックして例外を発生させる
    $this->mock(MealCategoryService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()->andThrow(new \Exception('Database connection failed'));
    });

    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立カテゴリの作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-23: 【新規作成】 献立カテゴリ作成失敗', function () {
    // mealCategoryServiceをモックして例外を発生させる
    $this->mock(MealCategoryService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()->andThrow(new \Exception('mealCategory create failed'));
    });

    $data = [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/meal-categories', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立カテゴリの作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ===== bulkUpdate() メソッドのテストケース =====

test('3-5-24: 【一括更新】 正常な献立カテゴリ一括更新', function () {
    // テスト用の献立カテゴリをAPIで作成（store は data を返さないため DB から ID 取得）
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;
    $mealCategory2Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '昼食')->first()->id;

    $data = [
        'data' => [
            [
                'id' => $mealCategory1Id,
                'name' => 'モーニング',
                'colorId' => $this->blueColorId,
                'order' => 1
            ],
            [
                'id' => $mealCategory2Id,
                'name' => 'ランチ',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立カテゴリを2件更新しました。'
    ]);

    // データベースの更新を確認
    $this->assertDatabaseHas('meal_categories', [
        'id' => $mealCategory1Id,
        'name' => 'モーニング',
        'color_id' => $this->blueColorId,
        'order' => 1
    ]);
    $this->assertDatabaseHas('meal_categories', [
        'id' => $mealCategory2Id,
        'name' => 'ランチ',
        'color_id' => $this->yellowColorId,
        'order' => 0
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);
    $response->assertJsonPath('data', null);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-25: 【一括更新】 一括更新成功メッセージの確認', function () {
    // テスト用の献立カテゴリをAPIで作成（store は data を返さないため DB から ID 取得）
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;
    $mealCategory2Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '昼食')->first()->id;

    $data = [
        'data' => [
            [
                'id' => $mealCategory1Id,
                'name' => 'モーニング',
                'colorId' => $this->blueColorId,
                'order' => 1
            ],
            [
                'id' => $mealCategory2Id,
                'name' => 'ランチ',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('献立カテゴリを2件更新しました。');
});

test('3-5-26: 【一括更新】 一括更新後のデータ取得確認', function () {
    // テスト用の献立カテゴリをAPIで作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;

    $data = [
        'data' => [
            [
                'id' => $mealCategory1Id,
                'name' => 'モーニング',
                'colorId' => $this->blueColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    $response->assertJsonPath('data', null);

    // 一覧取得で更新後のデータを確認
    $indexResponse = $this->actingAs($this->user)->get('/meal-categories');
    $indexResponse->assertStatus(200);
    $responseData = $indexResponse->json('data');
    expect($responseData)->toHaveCount(1);
    expect($responseData[0]['id'])->toBe($mealCategory1Id);
    expect($responseData[0]['name'])->toBe('モーニング');
    expect($responseData[0]['colorCodeHex'])->toBe('#2673B8');
    expect($responseData[0]['order'])->toBe(0);
});

test('3-5-27: 【一括更新】 バリデーションエラー（data が未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('dataは必ず指定してください。', $responseData['errors']['data']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-28: 【一括更新】 バリデーションエラー（data が配列以外）', function () {
    $data = [
        'data' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('dataは配列でなくてはなりません。', $responseData['errors']['data']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-29: 【一括更新】 バリデーションエラー（data が空配列）', function () {
    $data = [
        'data' => []
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('dataは1個以上指定してください。', $responseData['errors']['data']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-30: 【一括更新】 バリデーションエラー（ID 未入力）', function () {
    $data = [
        'data' => [
            [
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.id']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.idは必ず指定してください。', $responseData['errors']['data.0.id']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.id'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-31: 【一括更新】 バリデーションエラー（ID が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'id' => 'invalid-uuid',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.id']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.idに有効なUUIDを指定してください。', $responseData['errors']['data.0.id']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.id'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-32: 【一括更新】 バリデーションエラー（献立カテゴリ名未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.nameは必ず指定してください。', $responseData['errors']['data.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-33: 【一括更新】 バリデーションエラー（献立カテゴリ名が文字列以外）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => 123,
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.nameは文字列を指定してください。', $responseData['errors']['data.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-34: 【一括更新】 バリデーションエラー（献立カテゴリ名が 255 文字超過）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => str_repeat('a', 256),
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.nameは、255文字以内で指定してください。', $responseData['errors']['data.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-35: 【一括更新】 バリデーションエラー（色 ID 未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.colorId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.colorIdは必ず指定してください。', $responseData['errors']['data.0.colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-36: 【一括更新】 バリデーションエラー（色 ID が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => 'invalid-uuid',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.colorId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.colorIdに有効なUUIDを指定してください。', $responseData['errors']['data.0.colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-37: 【一括更新】 バリデーションエラー（色 ID が存在しない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => '00000000-0000-0000-0000-000000000000',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.colorId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('選択されたdata.*.colorIdは正しくありません。', $responseData['errors']['data.0.colorId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.colorId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-38: 【一括更新】 バリデーションエラー（order 値が未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.orderは必ず指定してください。', $responseData['errors']['data.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-39: 【一括更新】 バリデーションエラー（order 値が数値以外）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 'abc'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.orderは整数で指定してください。', $responseData['errors']['data.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-40: 【一括更新】 バリデーションエラー（order 値が負の値）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => -1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.orderには、0以上の数字を指定してください。', $responseData['errors']['data.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-41: 【一括更新】 存在しない献立カテゴリの更新', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立カテゴリが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-42: 【一括更新】 他グループの献立カテゴリ更新', function () {
    // 他のグループのユーザーと献立カテゴリをAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $this->actingAs($otherUser)->post('/meal-categories', [
        'name' => '他のグループの献立カテゴリ',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $othermealCategoryId = \App\Models\MealCategory::where('group_id', $otherGroup->id)->where('name', '他のグループの献立カテゴリ')->first()->id;

    $data = [
        'data' => [
            [
                'id' => $othermealCategoryId,
                'name' => '朝食',
                'colorId' => $this->redColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立カテゴリが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-43: 【一括更新】 未認証ユーザー', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->put('/meal-categories/bulk', $data);

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

test('3-5-44: 【一括更新】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '朝食',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($user)->put('/meal-categories/bulk', $data);

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

test('3-5-45: 【一括更新】 データベース接続エラー', function () {
    // テスト用の献立カテゴリをAPIで作成（store は data を返さないため DB から ID 取得）
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;

    // mealCategoryServiceをモックして例外を発生させる
    $this->mock(MealCategoryService::class, function ($mock) {
        $mock->shouldReceive('bulkUpdate')
            ->once()->andThrow(new \Exception('Database connection failed'));
    });

    $data = [
        'data' => [
            [
                'id' => $mealCategory1Id,
                'name' => 'モーニング',
                'colorId' => $this->yellowColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立カテゴリの一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-46: 【一括更新】 献立カテゴリ更新失敗', function () {
    // テスト用の献立カテゴリをAPIで作成（store は data を返さないため DB から ID 取得）
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;

    // mealCategoryServiceをモックして例外を発生させる
    $this->mock(MealCategoryService::class, function ($mock) {
        $mock->shouldReceive('bulkUpdate')
            ->once()->andThrow(new \Exception('mealCategory update failed'));
    });

    $data = [
        'data' => [
            [
                'id' => $mealCategory1Id,
                'name' => 'モーニング',
                'colorId' => $this->blueColorId,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/meal-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立カテゴリの一括更新中にエラーが発生しました。'
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

test('3-5-47: 【削除】 正常な献立カテゴリ削除', function () {
    // テスト用の献立カテゴリをAPIで作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;

    $response = $this->actingAs($this->user)->delete("/meal-categories/{$mealCategory1Id}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立カテゴリ(朝食)を削除しました。'
    ]);

    // データベースから削除されていることを確認
    $this->assertDatabaseMissing('meal_categories', [
        'id' => $mealCategory1Id
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-48: 【削除】 削除後の order 整理確認', function () {
    // テスト用の献立カテゴリをAPIで作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;

    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '昼食',
        'colorId' => $this->redColorId,
        'order' => 1
    ]);
    $mealCategory2Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '昼食')->first()->id;

    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '夕食',
        'colorId' => $this->blueColorId,
        'order' => 2
    ]);
    $mealCategory3Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '夕食')->first()->id;

    // 中間の献立カテゴリを削除
    $response = $this->actingAs($this->user)->delete("/meal-categories/{$mealCategory2Id}");

    $response->assertStatus(200);

    // 残りの献立カテゴリのorderが整理されていることを確認
    $this->assertDatabaseHas('meal_categories', [
        'id' => $mealCategory1Id,
        'order' => 0
    ]);
    $this->assertDatabaseHas('meal_categories', [
        'id' => $mealCategory3Id,
        'order' => 1
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-49: 【削除】 削除成功メッセージの確認', function () {
    // テスト用の献立カテゴリをAPIで作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;

    $response = $this->actingAs($this->user)->delete("/meal-categories/{$mealCategory1Id}");

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('献立カテゴリ(朝食)を削除しました。');
});

test('3-5-50: 【削除】 存在しない献立カテゴリ削除', function () {
    $response = $this->actingAs($this->user)->delete('/meal-categories/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立カテゴリが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-51: 【削除】 他グループの献立カテゴリ削除', function () {
    // 他のグループのユーザーと献立カテゴリをAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $this->actingAs($otherUser)->post('/meal-categories', [
        'name' => '他のグループの献立カテゴリ',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $othermealCategoryId = \App\Models\MealCategory::where('group_id', $otherGroup->id)->where('name', '他のグループの献立カテゴリ')->first()->id;

    $response = $this->actingAs($this->user)->delete("/meal-categories/{$othermealCategoryId}");

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立カテゴリが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-52: 【削除】 未認証ユーザー', function () {
    $response = $this->delete('/meal-categories/00000000-0000-0000-0000-000000000000');

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

test('3-5-53: 【削除】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->delete('/meal-categories/00000000-0000-0000-0000-000000000000');

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

test('3-5-54: 【削除】 データベース接続エラー', function () {
    // テスト用の献立カテゴリをAPIで作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;

    // mealCategoryServiceをモックして例外を発生させる
    $this->mock(MealCategoryService::class, function ($mock) {
        $mock->shouldReceive('delete')
            ->once()->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->delete("/meal-categories/{$mealCategory1Id}");

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立カテゴリの削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-55: 【削除】 献立カテゴリ削除失敗', function () {
    // テスト用の献立カテゴリをAPIで作成
    $this->actingAs($this->user)->post('/meal-categories', [
        'name' => '朝食',
        'colorId' => $this->yellowColorId,
        'order' => 0
    ]);
    $mealCategory1Id = \App\Models\MealCategory::where('group_id', $this->group->id)->where('name', '朝食')->first()->id;

    // mealCategoryServiceをモックして例外を発生させる
    $this->mock(MealCategoryService::class, function ($mock) {
        $mock->shouldReceive('delete')
            ->once()->andThrow(new \Exception('mealCategory delete failed'));
    });

    $response = $this->actingAs($this->user)->delete("/meal-categories/{$mealCategory1Id}");

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立カテゴリの削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});
