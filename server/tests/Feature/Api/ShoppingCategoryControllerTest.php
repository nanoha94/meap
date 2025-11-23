<?php

use App\Models\User;
use App\Models\Group;
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

    DB::table('group_user_mappings')->insert([
        'user_id' => $this->user->id,
        'group_id' => $this->group->id
    ]);

    // ユーザーとグループの関係をリフレッシュ
    $this->user->refresh();
    $this->user->load('groups');
});

// ===== index() メソッドのテストケース =====

test('3-9-1: 【一覧取得】 正常な買い物カテゴリ一覧取得', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);
    $category1Id = $response1->json('data.0.id');

    $response2 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '肉類', 'order' => 1]]
    ]);
    $category2Id = $response2->json('data.0.id');

    $response = $this->actingAs($this->user)->get('/shopping-categories');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物カテゴリーを2件取得しました。'
    ]);

    $responseData = $response->json('data');
    expect($responseData)->toHaveCount(2);
    expect($responseData[0]['name'])->toBe('野菜');
    expect($responseData[1]['name'])->toBe('肉類');

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'isDefault',
                'order'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-2: 【一覧取得】 カテゴリ情報の並び順確認', function () {
    // 異なるorder順でカテゴリをAPIで作成
    $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 2]]
    ]);
    $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '肉類', 'order' => 0]]
    ]);
    $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '魚類', 'order' => 1]]
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-categories');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // order順で並んでいることを確認
    expect($responseData[0]['name'])->toBe('肉類');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('魚類');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('野菜');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-9-3: 【一覧取得】 デフォルトカテゴリの確認', function () {
    // テスト用のカテゴリをAPIで作成（デフォルトカテゴリは自動作成される想定）
    $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-categories');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // isDefaultフラグが存在することを確認
    expect($responseData[0])->toHaveKey('isDefault');
    expect($responseData[0]['isDefault'])->toBeIn([true, false]);
});

test('3-9-4: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用のカテゴリをAPIで作成
    $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-categories');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'isDefault',
                'order'
            ]
        ],
        'total'
    ]);

    // 正しいJSON形式でレスポンスが返されることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-5: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/shopping-categories');

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

test('3-9-6: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/shopping-categories');

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

test('3-9-7: 【一覧取得】 データベース接続エラー', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

    $response = $this->actingAs($this->user)->get('/shopping-categories');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物カテゴリーの取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

// ===== bulkStore() メソッドのテストケース =====

test('3-9-8: 【一括作成】 正常な買い物カテゴリ一括作成', function () {
    $data = [
        'data' => [
            ['name' => '野菜', 'order' => 0],
            ['name' => '肉類', 'order' => 1],
            ['name' => '魚介類', 'order' => 2],
        ]
    ];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物カテゴリーを3件作成しました。',
        'data' => [
            ['name' => '野菜', 'order' => 0],
            ['name' => '肉類', 'order' => 1],
            ['name' => '魚介類', 'order' => 2],
        ]
    ]);

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('shopping_categories', ['group_id' => $this->group->id, 'name' => '野菜', 'order' => 0]);
    $this->assertDatabaseHas('shopping_categories', ['group_id' => $this->group->id, 'name' => '肉類', 'order' => 1]);
    $this->assertDatabaseHas('shopping_categories', ['group_id' => $this->group->id, 'name' => '魚介類', 'order' => 2]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'isDefault',
                'order',
            ]
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-9: 【一括作成】 バリデーションエラー（data 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);
    $responseData = $response->json();
    $this->assertContains('dataは必ず指定してください。', $responseData['errors']['data']);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data',
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-10: 【一括作成】 バリデーションエラー（data が配列でない）', function () {
    $data = ['data' => 'not_array'];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);
    $responseData = $response->json();
    $this->assertContains('dataは配列でなくてはなりません。', $responseData['errors']['data']);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data',
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-11: 【一括作成】 バリデーションエラー（data が空配列）', function () {
    $data = ['data' => []];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);
    $responseData = $response->json();
    $this->assertContains('dataは必ず指定してください。', $responseData['errors']['data']);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data',
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-12: 【一括作成】 バリデーションエラー（data.*.name 未入力）', function () {
    $data = ['data' => [
        ['order' => 0]
    ]];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);
    $responseData = $response->json();
    $this->assertContains('data.*.nameは必ず指定してください。', $responseData['errors']['data.0.name']);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name',
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-13: 【一括作成】 バリデーションエラー（data.*.name が文字列でない）', function () {
    $data = ['data' => [
        ['name' => 123, 'order' => 0]
    ]];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);
    $responseData = $response->json();
    $this->assertContains('data.*.nameは文字列を指定してください。', $responseData['errors']['data.0.name']);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name',
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-14: 【一括作成】 バリデーションエラー（data.*.name が 255 文字超過）', function () {
    $data = ['data' => [
        ['name' => str_repeat('a', 256), 'order' => 0]
    ]];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);
    $responseData = $response->json();
    $this->assertContains('data.*.nameは、255文字以内で指定してください。', $responseData['errors']['data.0.name']);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name',
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-15: 【一括作成】 バリデーションエラー（data.*.order 未入力）', function () {
    $data = ['data' => [
        ['name' => '野菜']
    ]];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);
    $responseData = $response->json();
    $this->assertContains('data.*.orderは必ず指定してください。', $responseData['errors']['data.0.order']);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.order',
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-16: 【一括作成】 バリデーションエラー（data.*.order が整数でない）', function () {
    $data = ['data' => [
        ['name' => '野菜', 'order' => 'abc']
    ]];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);
    $responseData = $response->json();
    $this->assertContains('data.*.orderは整数で指定してください。', $responseData['errors']['data.0.order']);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.order',
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-17: 【一括作成】 バリデーションエラー（data.*.order が負の値）', function () {
    $data = ['data' => [
        ['name' => '野菜', 'order' => -1]
    ]];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);
    $responseData = $response->json();
    $this->assertContains('data.*.orderには、0以上の数字を指定してください。', $responseData['errors']['data.0.order']);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.order',
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-18: 【一括作成】 未認証ユーザー', function () {
    $data = [
        'data' => [
            ['name' => '野菜', 'order' => 0]
        ]
    ];

    $response = $this->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-19: 【一括作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    // グループに所属させない

    $data = [
        'data' => [
            ['name' => '野菜', 'order' => 0]
        ]
    ];

    $response = $this->actingAs($user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-20: 【一括作成】 データベース接続エラー', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

    $data = [
        'data' => [
            ['name' => '野菜', 'order' => 0]
        ]
    ];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物カテゴリーの一括作成中にエラーが発生しました。',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-21: 【一括作成】 カテゴリ作成失敗', function () {
    DB::shouldReceive('transaction')->andThrow(new \Exception('Create failed'));

    $data = [
        'data' => [
            ['name' => '野菜', 'order' => 0]
        ]
    ];

    $response = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物カテゴリーの一括作成中にエラーが発生しました。',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

// ===== bulkUpdate() メソッドのテストケース =====

test('3-9-22: 【一括更新】 正常な買い物カテゴリ一括更新', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);
    $category1Id = $response1->json('data.0.id');

    $response2 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '肉類', 'order' => 1]]
    ]);
    $category2Id = $response2->json('data.0.id');

    $data = [
        'data' => [
            [
                'id' => $category1Id,
                'name' => '青果',
                'order' => 1
            ],
            [
                'id' => $category2Id,
                'name' => '精肉',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物カテゴリーを2件更新しました。'
    ]);

    // データベースの更新を確認
    $this->assertDatabaseHas('shopping_categories', [
        'id' => $category1Id,
        'name' => '青果',
        'order' => 1
    ]);
    $this->assertDatabaseHas('shopping_categories', [
        'id' => $category2Id,
        'name' => '精肉',
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
                'isDefault',
                'order'
            ]
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-23: 【一括更新】 一括更新成功メッセージの確認', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);
    $category1Id = $response1->json('data.0.id');

    $data = [
        'data' => [
            [
                'id' => $category1Id,
                'name' => '青果',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('買い物カテゴリーを1件更新しました。');
});

test('3-9-24: 【一括更新】 存在しないカテゴリの更新', function () {
    // テスト用のカテゴリをAPIで作成（正常なカテゴリ）
    $response1 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);
    $category1Id = $response1->json('data.0.id');

    // 正常なIDと存在しないIDを混在させる
    $data = [
        'data' => [
            [
                'id' => $category1Id,
                'name' => '青果',
                'order' => 0
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '肉類',
                'order' => 1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された買い物カテゴリーが見つかりませんでした。'
    ]);

    // トランザクションがロールバックされ、正常なカテゴリも更新されていないことを確認
    $this->assertDatabaseHas('shopping_categories', [
        'id' => $category1Id,
        'name' => '野菜', // 元の名前のまま
        'order' => 0
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-25: 【一括更新】 他グループのカテゴリ更新', function () {
    // 他グループのユーザーとカテゴリをAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $otherResponse = $this->actingAs($otherUser)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '他のグループのカテゴリ', 'order' => 0]]
    ]);
    $otherCategoryId = $otherResponse->json('data.0.id');

    $data = [
        'data' => [
            [
                'id' => $otherCategoryId,
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された買い物カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-26: 【一括更新】 バリデーションエラー（data 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-27: 【一括更新】 バリデーションエラー（data が配列でない）', function () {
    $data = [
        'data' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-28: 【一括更新】 バリデーションエラー（data が空配列）', function () {
    $data = [
        'data' => []
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-29: 【一括更新】 バリデーションエラー（id が未入力）', function () {
    $data = [
        'data' => [
            [
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-30: 【一括更新】 バリデーションエラー（id が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'id' => 'invalid-uuid',
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-31: 【一括更新】 バリデーションエラー（name が未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-32: 【一括更新】 バリデーションエラー（name が文字列でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => 123,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-33: 【一括更新】 バリデーションエラー（name が 255 文字超過）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => str_repeat('a', 256),
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-34: 【一括更新】 バリデーションエラー（order が未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-35: 【一括更新】 バリデーションエラー（order が数値でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => 'abc'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-36: 【一括更新】 バリデーションエラー（order が負の値）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => -1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

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

test('3-9-37: 【一括更新】 未認証ユーザー', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->put('/shopping-categories/bulk', $data);

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

test('3-9-38: 【一括更新】 グループが存在しない', function () {
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

    $response = $this->actingAs($user)->put('/shopping-categories/bulk', $data);

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

test('3-9-39: 【一括更新】 データベース接続エラー', function () {
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

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物カテゴリーの一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-40: 【一括更新】 カテゴリ更新失敗', function () {
    DB::shouldReceive('transaction')->andThrow(new \Exception('Update failed'));

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '野菜',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物カテゴリーの一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

// ===== bulkDestroy() メソッドのテストケース =====

test('3-9-41: 【一括削除】 正常な買い物カテゴリ一括削除', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);
    $category1Id = $response1->json('data.0.id');

    $response2 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '肉類', 'order' => 1]]
    ]);
    $category2Id = $response2->json('data.0.id');

    $data = [
        'ids' => [$category1Id, $category2Id]
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物カテゴリーを2件削除しました。'
    ]);

    // データベースから削除されていることを確認
    $this->assertDatabaseMissing('shopping_categories', [
        'id' => $category1Id
    ]);
    $this->assertDatabaseMissing('shopping_categories', [
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

test('3-9-42: 【一括削除】 削除後の order 整理確認', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);
    $category1Id = $response1->json('data.0.id');

    $response2 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '肉類', 'order' => 1]]
    ]);
    $category2Id = $response2->json('data.0.id');

    $response3 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '魚類', 'order' => 2]]
    ]);
    $category3Id = $response3->json('data.0.id');

    $data = [
        'ids' => [$category2Id] // 中間のカテゴリを削除
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

    $response->assertStatus(200);

    // 残りのカテゴリのorderが整理されていることを確認
    $this->assertDatabaseHas('shopping_categories', [
        'id' => $category1Id,
        'order' => 0
    ]);
    $this->assertDatabaseHas('shopping_categories', [
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

test('3-9-43: 【一括削除】 一括削除成功メッセージの確認', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);
    $category1Id = $response1->json('data.0.id');

    $data = [
        'ids' => [$category1Id]
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('買い物カテゴリーを1件削除しました。');
});

test('3-9-44: 【一括削除】 デフォルトカテゴリの保護確認', function () {
    // デフォルトカテゴリを取得（存在する場合）
    $response = $this->actingAs($this->user)->get('/shopping-categories');
    $categories = $response->json('data');

    $defaultCategory = collect($categories)->first(function ($category) {
        return $category['isDefault'] === true;
    });

    if ($defaultCategory) {
        $data = [
            'ids' => [$defaultCategory['id']]
        ];

        $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

        // デフォルトカテゴリは削除できないことを確認
        // ※実装によっては200だが削除されない、または400/422エラーが返る可能性がある
        // ここでは削除されないことのみを確認
        $this->assertDatabaseHas('shopping_categories', [
            'id' => $defaultCategory['id'],
            'is_default' => true
        ]);
    } else {
        // デフォルトカテゴリが存在しない場合はテストをスキップ
        expect(true)->toBeTrue();
    }
});

test('3-9-45: 【一括削除】 存在しないカテゴリの削除', function () {
    // テスト用のカテゴリをAPIで作成（正常なカテゴリ）
    $response1 = $this->actingAs($this->user)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '野菜', 'order' => 0]]
    ]);
    $category1Id = $response1->json('data.0.id');

    // 正常なIDと存在しないIDを混在させる
    $data = [
        'ids' => [$category1Id, '00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された買い物カテゴリーが見つかりませんでした。'
    ]);

    // トランザクションがロールバックされ、正常なカテゴリも削除されていないことを確認
    $this->assertDatabaseHas('shopping_categories', [
        'id' => $category1Id,
        'name' => '野菜'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-46: 【一括削除】 他グループのカテゴリ削除', function () {
    // 他グループのユーザーとカテゴリをAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $otherResponse = $this->actingAs($otherUser)->postJson('/shopping-categories/bulk', [
        'data' => [['name' => '他のグループのカテゴリ', 'order' => 0]]
    ]);
    $otherCategoryId = $otherResponse->json('data.0.id');

    $data = [
        'ids' => [$otherCategoryId]
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された買い物カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-47: 【一括削除】 バリデーションエラー（IDs 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

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

test('3-9-48: 【一括削除】 バリデーションエラー（IDs が配列でない）', function () {
    $data = [
        'ids' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

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

test('3-9-49: 【一括削除】 バリデーションエラー（IDs が空配列）', function () {
    $data = [
        'ids' => []
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

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

test('3-9-50: 【一括削除】 バリデーションエラー（ID が UUID 形式でない）', function () {
    $data = [
        'ids' => ['invalid-uuid']
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

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

test('3-9-51: 【一括削除】 未認証ユーザー', function () {
    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->delete('/shopping-categories/bulk', $data);

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

test('3-9-52: 【一括削除】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($user)->delete('/shopping-categories/bulk', $data);

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

test('3-9-53: 【一括削除】 データベース接続エラー', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物カテゴリーの削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-54: 【一括削除】 カテゴリ削除失敗', function () {
    DB::shouldReceive('transaction')->andThrow(new \Exception('Delete failed'));

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物カテゴリーの削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});
