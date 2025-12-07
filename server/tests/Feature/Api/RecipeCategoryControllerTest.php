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

test('3-7-1: 【一覧取得】 正常な料理カテゴリ一覧取得', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '洋食',
        'order' => 1
    ]);
    $category2Id = $response2->json('data.id');

    $response = $this->actingAs($this->user)->get('/recipe-categories');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理カテゴリーを2件取得しました。',
        'data' => [
            [
                'id' => $category1Id,
                'name' => '和食',
                'order' => 0
            ],
            [
                'id' => $category2Id,
                'name' => '洋食',
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

test('3-7-2: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用のカテゴリをAPIで作成
    $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);

    $response = $this->actingAs($this->user)->get('/recipe-categories');

    $response->assertStatus(200);
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

    // 正しいJSON形式でレスポンスが返されることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-3: 【一覧取得】 order 順での取得確認', function () {
    // 異なるorder順でカテゴリをAPIで作成
    $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 2
    ]);
    $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '洋食',
        'order' => 0
    ]);
    $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '中華',
        'order' => 1
    ]);

    $response = $this->actingAs($this->user)->get('/recipe-categories');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // order順で並んでいることを確認
    expect($responseData[0]['name'])->toBe('洋食');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('中華');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('和食');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-7-4: 【一覧取得】 空のリスト取得', function () {
    // カテゴリが存在しない状態でテスト
    $response = $this->actingAs($this->user)->get('/recipe-categories');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理カテゴリーを0件取得しました。',
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

test('3-7-5: 【一覧取得】 他グループの料理カテゴリは取得されない', function () {
    // 自グループのカテゴリを作成
    $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);

    // 他グループのユーザーとカテゴリを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $this->actingAs($otherUser)->post('/recipe-categories', [
        'name' => '他のグループのカテゴリ',
        'order' => 0
    ]);

    $response = $this->actingAs($this->user)->get('/recipe-categories');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 自グループのカテゴリのみが取得されることを確認
    expect($responseData)->toHaveCount(1);
    expect($responseData[0]['name'])->toBe('和食');
});

test('3-7-6: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/recipe-categories');

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

test('3-7-7: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/recipe-categories');

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

test('3-7-8: 【一覧取得】 データベース接続エラー', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

    $response = $this->actingAs($this->user)->get('/recipe-categories');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '料理カテゴリーの取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

// ===== store() メソッドのテストケース =====

test('3-7-9: 【新規作成】 正常な料理カテゴリ作成', function () {
    $data = [
        'name' => '和食',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/recipe-categories', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理カテゴリー(和食)を作成しました。',
        'data' => [
            'name' => '和食',
            'order' => 0
        ]
    ]);

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('recipe_categories', [
        'group_id' => $this->group->id,
        'name' => '和食',
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

test('3-7-10: 【新規作成】 レスポンス形式確認', function () {
    $data = [
        'name' => '和食',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/recipe-categories', $data);

    $response->assertStatus(201);
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

test('3-7-11: 【新規作成】 バリデーションエラー（料理カテゴリ名未入力）', function () {
    $data = [
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/recipe-categories', $data);

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

test('3-7-12: 【新規作成】 バリデーションエラー（料理カテゴリ名が 255 文字超過）', function () {
    $data = [
        'name' => str_repeat('a', 256),
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/recipe-categories', $data);

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

test('3-7-13: 【新規作成】 バリデーションエラー（order 値が未入力）', function () {
    $data = [
        'name' => '和食'
    ];

    $response = $this->actingAs($this->user)->post('/recipe-categories', $data);

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

test('3-7-14: 【新規作成】 バリデーションエラー（order 値が数値以外）', function () {
    $data = [
        'name' => '和食',
        'order' => 'abc'
    ];

    $response = $this->actingAs($this->user)->post('/recipe-categories', $data);

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

test('3-7-15: 【新規作成】 バリデーションエラー（order 値が負の値）', function () {
    $data = [
        'name' => '和食',
        'order' => -1
    ];

    $response = $this->actingAs($this->user)->post('/recipe-categories', $data);

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

test('3-7-16: 【新規作成】 未認証ユーザー', function () {
    $data = [
        'name' => '和食',
        'order' => 0
    ];

    $response = $this->post('/recipe-categories', $data);

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

test('3-7-17: 【新規作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'name' => '和食',
        'order' => 0
    ];

    $response = $this->actingAs($user)->post('/recipe-categories', $data);

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

test('3-7-18: 【新規作成】 データベース接続エラー', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

    $data = [
        'name' => '和食',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/recipe-categories', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '料理カテゴリーの作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

test('3-7-19: 【新規作成】 料理カテゴリ作成失敗', function () {
    DB::shouldReceive('transaction')->andThrow(new \Exception('Create failed'));

    $data = [
        'name' => '和食',
        'order' => 0
    ];

    $response = $this->actingAs($this->user)->post('/recipe-categories', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '料理カテゴリーの作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

// ===== bulkUpdate() メソッドのテストケース =====

test('3-7-20: 【一括更新】 正常な料理カテゴリ一括更新', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '洋食',
        'order' => 1
    ]);
    $category2Id = $response2->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $category1Id,
                'name' => '日本料理',
                'order' => 1
            ],
            [
                'id' => $category2Id,
                'name' => '西洋料理',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理カテゴリーを2件更新しました。'
    ]);

    // データベースの更新を確認
    $this->assertDatabaseHas('recipe_categories', [
        'id' => $category1Id,
        'name' => '日本料理',
        'order' => 1
    ]);
    $this->assertDatabaseHas('recipe_categories', [
        'id' => $category2Id,
        'name' => '西洋料理',
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

test('3-7-21: 【一括更新】 一括更新成功メッセージの確認', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $category1Id,
                'name' => '日本料理',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('料理カテゴリーを1件更新しました。');
});

test('3-7-22: 【一括更新】 一括更新後のデータ取得確認', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $category1Id,
                'name' => '日本料理',
                'order' => 0
            ]
        ]
    ];

    $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

    // 一覧取得で更新されたデータを確認
    $response = $this->actingAs($this->user)->get('/recipe-categories');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    expect($responseData[0]['name'])->toBe('日本料理');
});

test('3-7-23: 【一括更新】 存在しない料理カテゴリの更新', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '和食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された料理カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-24: 【一括更新】 他グループの料理カテゴリ更新', function () {
    // 他グループのユーザーとカテゴリをAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $otherResponse = $this->actingAs($otherUser)->post('/recipe-categories', [
        'name' => '他のグループのカテゴリ',
        'order' => 0
    ]);
    $otherCategoryId = $otherResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $otherCategoryId,
                'name' => '和食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された料理カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-25: 【一括更新】 バリデーションエラー（data 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-26: 【一括更新】 バリデーションエラー（data が配列でない）', function () {
    $data = [
        'data' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-27: 【一括更新】 バリデーションエラー（data が空配列）', function () {
    $data = [
        'data' => []
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-28: 【一括更新】 バリデーションエラー（id が未入力）', function () {
    $data = [
        'data' => [
            [
                'name' => '和食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-29: 【一括更新】 バリデーションエラー（id が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'id' => 'invalid-uuid',
                'name' => '和食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-30: 【一括更新】 バリデーションエラー（name が未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-31: 【一括更新】 バリデーションエラー（name が文字列でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => 123,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-32: 【一括更新】 バリデーションエラー（order が未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '和食'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-33: 【一括更新】 バリデーションエラー（order が数値でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '和食',
                'order' => 'abc'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-34: 【一括更新】 バリデーションエラー（order が負の値）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '和食',
                'order' => -1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

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

test('3-7-35: 【一括更新】 未認証ユーザー', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '和食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->put('/recipe-categories/bulk', $data);

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

test('3-7-36: 【一括更新】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '和食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($user)->put('/recipe-categories/bulk', $data);

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

test('3-7-37: 【一括更新】 データベース接続エラー', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '和食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '料理カテゴリーの一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-38: 【一括更新】 料理カテゴリ更新失敗', function () {
    DB::shouldReceive('transaction')->andThrow(new \Exception('Update failed'));

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '和食',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/recipe-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '料理カテゴリーの一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

// ===== bulkDestroy() メソッドのテストケース =====

test('3-7-39: 【一括削除】 正常な料理カテゴリ一括削除', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '洋食',
        'order' => 1
    ]);
    $category2Id = $response2->json('data.id');

    $data = [
        'ids' => [$category1Id, $category2Id]
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理カテゴリーを2件削除しました。'
    ]);

    // データベースから削除されていることを確認
    $this->assertDatabaseMissing('recipe_categories', [
        'id' => $category1Id
    ]);
    $this->assertDatabaseMissing('recipe_categories', [
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

test('3-7-40: 【一括削除】 一括削除成功メッセージの確認', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $data = [
        'ids' => [$category1Id]
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('料理カテゴリーを1件削除しました。');
});

test('3-7-41: 【一括削除】 削除後の order 整理確認', function () {
    // テスト用のカテゴリをAPIで作成
    $response1 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);
    $category1Id = $response1->json('data.id');

    $response2 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '洋食',
        'order' => 1
    ]);
    $category2Id = $response2->json('data.id');

    $response3 = $this->actingAs($this->user)->post('/recipe-categories', [
        'name' => '中華',
        'order' => 2
    ]);
    $category3Id = $response3->json('data.id');

    $data = [
        'ids' => [$category2Id] // 中間のカテゴリを削除
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

    $response->assertStatus(200);

    // 残りのカテゴリのorderが整理されていることを確認
    $this->assertDatabaseHas('recipe_categories', [
        'id' => $category1Id,
        'order' => 0
    ]);
    $this->assertDatabaseHas('recipe_categories', [
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

test('3-7-42: 【一括削除】 存在しない料理カテゴリの削除', function () {
    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された料理カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-43: 【一括削除】 他グループの料理カテゴリ削除', function () {
    // 他グループのユーザーとカテゴリをAPIで作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    $otherResponse = $this->actingAs($otherUser)->post('/recipe-categories', [
        'name' => '他のグループのカテゴリ',
        'order' => 0
    ]);
    $otherCategoryId = $otherResponse->json('data.id');

    $data = [
        'ids' => [$otherCategoryId]
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された料理カテゴリーが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-44: 【一括削除】 バリデーションエラー（IDs 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

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

test('3-7-45: 【一括削除】 バリデーションエラー（IDs が配列でない）', function () {
    $data = [
        'ids' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

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

test('3-7-46: 【一括削除】 バリデーションエラー（IDs が空配列）', function () {
    $data = [
        'ids' => []
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

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

test('3-7-47: 【一括削除】 バリデーションエラー（ID が UUID 形式でない）', function () {
    $data = [
        'ids' => ['invalid-uuid']
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

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

test('3-7-48: 【一括削除】 未認証ユーザー', function () {
    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->delete('/recipe-categories/bulk', $data);

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

test('3-7-49: 【一括削除】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($user)->delete('/recipe-categories/bulk', $data);

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

test('3-7-50: 【一括削除】 データベース接続エラー', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '料理カテゴリーの削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-51: 【一括削除】 料理カテゴリ削除失敗', function () {
    DB::shouldReceive('transaction')->andThrow(new \Exception('Delete failed'));

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/recipe-categories/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '料理カテゴリーの削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});
