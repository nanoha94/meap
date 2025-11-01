<?php

use App\Models\User;
use App\Models\Group;
use App\Models\GroupUserMapping;
use App\Models\IngredientCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $this->group = Group::create([
        'group_size' => 1
    ]);

    GroupUserMapping::create([
        'user_id' => $this->user->id,
        'group_id' => $this->group->id
    ]);

    // ユーザーとグループの関係をリフレッシュ
    $this->user->refresh();
    $this->user->load('group');
});

// ===== index() メソッドのテストケース =====

test('3-3-1: 【一覧取得】 正常な食材カテゴリ一覧取得', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '野菜',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '肉類',
        'order' => 1
    ]);
    $category2Id = $response2->json('data.id');

    $response = $this->actingAs($this->user)->get('/ingredient-categories');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '食材カテゴリーを2件取得しました。',
        'data' => [
            [
                'id' => $category1Id,
                'name' => '野菜',
                'order' => 0
            ],
            [
                'id' => $category2Id,
                'name' => '肉類',
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
                'order'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-2: 【一覧取得】 カテゴリ情報の並び順確認', function () {
    // 異なるorder順でカテゴリをAPIで作成
    $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '野菜',
        'order' => 2
    ]);
    $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '肉類',
        'order' => 0
    ]);
    $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '調味料',
        'order' => 1
    ]);

    $response = $this->actingAs($this->user)->get('/ingredient-categories');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // order順で並んでいることを確認
    expect($responseData[0]['name'])->toBe('肉類');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('調味料');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('野菜');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-3-3: 【一覧取得】 空のカテゴリー一覧', function () {
    // カテゴリーが存在しない状態でテスト
    $response = $this->actingAs($this->user)->get('/ingredient-categories');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '食材カテゴリーを0件取得しました。',
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

test('3-3-4: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/ingredient-categories');

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

test('3-3-5: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/ingredient-categories');

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

// ===== store() メソッドのテストケース =====

test('3-3-6: 【新規作成】 正常な食材カテゴリ作成', function () {
    $data = [
        'name' => '野菜',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/ingredient-categories', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '食材カテゴリー(野菜)を作成しました。',
        'data' => [
            'name' => '野菜',
            'order' => 0
        ]
    ]);

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('ingredient_categories', [
        'group_id' => $this->group->id,
        'name' => '野菜',
        'order' => 0
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name',
            'order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-7: 【新規作成】 未認証ユーザー', function () {
    $data = [
        'name' => '野菜',
        'order' => 0
    ];

    $response = $this->post('/ingredient-categories', $data);

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

test('3-3-8: 【新規作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'name' => '野菜',
        'order' => 0
    ];

    $response = $this->actingAs($user)->post('/ingredient-categories', $data);

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

test('3-3-9: 【新規作成】 データベースエラー', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));


    $data = [
        'name' => '野菜',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/ingredient-categories', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '食材カテゴリーの作成に失敗しました。'
    ]);

    // 設定を元に戻す
    config(['database.connections.sqlite.database' => database_path('database.sqlite')]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});


test('3-3-10: 【新規作成】 バリデーションエラー（カテゴリ名未入力）', function () {
    $data = [
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/ingredient-categories', $data);

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

test('3-3-11: 【新規作成】 バリデーションエラー（カテゴリ名が文字列以外）', function () {
    $data = [
        'name' => 123,
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/ingredient-categories', $data);

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

test('3-3-12: 【新規作成】 バリデーションエラー（カテゴリ名が 255 文字超過）', function () {
    $data = [
        'name' => str_repeat('a', 256),
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/ingredient-categories', $data);

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

test('3-3-13: 【新規作成】 バリデーションエラー（order 値が未入力）', function () {
    $data = [
        'name' => '野菜'
    ];

    $response = $this->actingAs($this->user)->post('/ingredient-categories', $data);

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

test('3-3-14: 【新規作成】 バリデーションエラー（order 値が数値以外）', function () {
    $data = [
        'name' => '野菜',
        'order' => 'abc'
    ];

    $response = $this->actingAs($this->user)->post('/ingredient-categories', $data);

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

test('3-3-15: 【新規作成】 バリデーションエラー（order 値が負の数）', function () {
    $data = [
        'name' => '野菜',
        'order' => -1
    ];

    $response = $this->actingAs($this->user)->post('/ingredient-categories', $data);

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

// ===== bulkUpdate() メソッドのテストケース =====

test('3-3-16: 【一括更新】 正常な食材カテゴリ一括更新', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '野菜',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '肉類',
        'order' => 1
    ]);
    $category2Id = $response2->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $category1Id,
                'name' => '野菜類',
                'order' => 1
            ],
            [
                'id' => $category2Id,
                'name' => '肉類',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '食材カテゴリーを2件更新しました。'
    ]);

    // データベースの更新を確認
    $this->assertDatabaseHas('ingredient_categories', [
        'id' => $category1Id,
        'name' => '野菜類',
        'order' => 1
    ]);
    $this->assertDatabaseHas('ingredient_categories', [
        'id' => $category2Id,
        'name' => '肉類',
        'order' => 0
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'order'
            ]
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-17: 【一括更新】 部分的な一括更新失敗', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '野菜',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '肉類',
        'order' => 1
    ]);

    $data = [
        'data' => [
            [
                'id' => $category1Id,
                'name' => '野菜類',
                'order' => 1
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000000', // 存在しないID
                'name' => '調味料',
                'order' => 2
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された食材カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-18: 【一括更新】 未認証ユーザー', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->put('/ingredient-categories/bulk', $data);

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

test('3-3-19: 【一括更新】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-20: 【一括更新】 データベース接続エラー', function () {
    // データベース接続を無効化してエラーを発生させる
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '食材カテゴリーの一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-21: 【一括更新】 ID が null（nullable 許可）', function () {
    $data = [
        'data' => [
            [
                'id' => null,
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.id']);

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

test('3-3-22: 【一括更新】 グループ外のカテゴリ更新試行', function () {
    // 他のグループのユーザーとカテゴリをAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    GroupUserMapping::create([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $otherResponse = $this->actingAs($otherUser)->post('/ingredient-categories', [
        'name' => '他のグループのカテゴリ',
        'order' => 0
    ]);
    $otherCategoryId = $otherResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $otherCategoryId,
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された食材カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-23: 【一括更新】 バリデーションエラー（データ配列未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-24: 【一括更新】 バリデーションエラー（データ配列が配列以外）', function () {
    $data = [
        'data' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-25: 【一括更新】 バリデーションエラー（データ配列が空）', function () {
    $data = [
        'data' => []
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-26: 【一括更新】 バリデーションエラー（ID が UUID 以外）', function () {
    $data = [
        'data' => [
            [
                'id' => 'invalid-uuid',
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-27: 【一括更新】 IDが存在しない', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された食材カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-28: 【一括更新】 バリデーションエラー（カテゴリ名未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-29: 【一括更新】 バリデーションエラー（カテゴリ名が文字列以外）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => 123,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-30: 【一括更新】 バリデーションエラー（カテゴリ名が 255 文字超過）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => str_repeat('a', 256),
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-31: 【一括更新】 バリデーションエラー（order 値が未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-32: 【一括更新】 バリデーションエラー（order 値が数値以外）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => 'abc'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

test('3-3-33: 【一括更新】 バリデーションエラー（order 値が負の数）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => -1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/ingredient-categories/bulk', $data);

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

// ===== bulkDestroy() メソッドのテストケース =====

test('3-3-34: 【一括削除】 正常な食材カテゴリ一括削除', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '野菜',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '肉類',
        'order' => 1
    ]);
    $category2Id = $response2->json('data.id');

    $data = [
        'ids' => [$category1Id, $category2Id]
    ];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '食材カテゴリーを2件削除しました。'
    ]);

    // データベースから削除されていることを確認
    $this->assertDatabaseMissing('ingredient_categories', [
        'id' => $category1Id
    ]);
    $this->assertDatabaseMissing('ingredient_categories', [
        'id' => $category2Id
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-35: 【一括削除】 削除後の order 整理確認', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '野菜',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '肉類',
        'order' => 1
    ]);
    $category2Id = $response2->json('data.id');

    $response3 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '調味料',
        'order' => 2
    ]);
    $category3Id = $response3->json('data.id');

    $data = [
        'ids' => [$category2Id] // 中間のカテゴリを削除
    ];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(200);

    // 残りのカテゴリのorderが整理されていることを確認
    $this->assertDatabaseHas('ingredient_categories', [
        'id' => $category1Id,
        'order' => 0
    ]);
    $this->assertDatabaseHas('ingredient_categories', [
        'id' => $category3Id,
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

test('3-3-36: 【一括削除】 部分的な一括削除失敗', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '野菜',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $this->actingAs($this->user)->post('/ingredient-categories', [
        'name' => '肉類',
        'order' => 1
    ]);

    $data = [
        'ids' => [$category1Id, '00000000-0000-0000-0000-000000000000'] // 存在しないIDを含む
    ];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された食材カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-37: 【一括削除】 未認証ユーザー', function () {
    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->delete('/ingredient-categories/bulk', $data);

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

test('3-3-38: 【一括削除】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($user)->delete('/ingredient-categories/bulk', $data);

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

test('3-3-39: 【一括削除】 データベース接続エラー', function () {
    // データベース接続を無効化してエラーを発生させる
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '食材カテゴリーの削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-40: 【一括削除】 グループ外のカテゴリ削除試行', function () {
    // 他のグループのユーザーとカテゴリをAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    GroupUserMapping::create([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $otherResponse = $this->actingAs($otherUser)->post('/ingredient-categories', [
        'name' => '他のグループのカテゴリ',
        'order' => 0
    ]);
    $otherCategoryId = $otherResponse->json('data.id');

    $data = [
        'ids' => [$otherCategoryId]
    ];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された食材カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-41: 【一括削除】 バリデーションエラー（ID 配列未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('idsは必ず指定してください。', $responseData['errors']['ids']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ids'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-42: 【一括削除】 バリデーションエラー（ID 配列が配列以外）', function () {
    $data = [
        'ids' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('idsは配列でなくてはなりません。', $responseData['errors']['ids']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ids'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-43: 【一括削除】 バリデーションエラー（ID 配列が空）', function () {
    $data = [
        'ids' => []
    ];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('idsは必ず指定してください。', $responseData['errors']['ids']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ids'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-44: 【一括削除】 バリデーションエラー（ID が UUID 以外）', function () {
    $data = [
        'ids' => ['invalid-uuid']
    ];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids.0']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ids.*に有効なUUIDを指定してください。', $responseData['errors']['ids.0']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ids.0'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-3-45: 【一括削除】 IDが存在しない', function () {
    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/ingredient-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された食材カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});
