<?php

use App\Models\User;
use App\Models\Group;
use App\Models\ShoppingCategory;
use App\Models\ShoppingItem;
use App\Models\ShoppingTag;
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

    // テスト用のカテゴリを直接作成（beforeEachでは認証状態の漏れを防ぐため）
    $this->category = ShoppingCategory::create([
        'group_id' => $this->group->id,
        'name' => 'テストカテゴリ',
        'is_default' => false,
        'order' => 0
    ]);
    $this->categoryId = $this->category->id;
});

// ===== index() メソッドのテストケース =====

test('3-10-1: 【一覧取得】 正常な買い物アイテム一覧取得', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $this->actingAs($this->user)->post('/shopping-items', [
        'name' => 'パン',
        'categoryId' => $this->categoryId
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-items');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを2件取得しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'category' => [
                    'id',
                    'name',
                    'isDefault',
                    'order'
                ],
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'isPinned',
                        'isChecked',
                        'order',
                        'categoryId',
                        'tags'
                    ]
                ]
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-2: 【一覧取得】 カテゴリ別アイテム取得確認', function () {
    // 別のカテゴリをAPIで作成
    $category2Response = $this->actingAs($this->user)->post('/shopping-categories', [
        'name' => 'カテゴリ2',
        'order' => 1
    ]);
    $category2Id = $category2Response->json('data.id');

    // カテゴリ1のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);

    // カテゴリ2のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items', [
        'name' => 'パン',
        'categoryId' => $category2Id
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-items');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // カテゴリ別にグループ化されていることを確認
    expect($responseData)->toBeArray();
    expect(count($responseData))->toBeGreaterThanOrEqual(2);

    // 各要素がcategoryとitemsを持つことを確認
    expect($responseData[0])->toHaveKey('category');
    expect($responseData[0])->toHaveKey('items');
    expect($responseData[1])->toHaveKey('category');
    expect($responseData[1])->toHaveKey('items');
});

test('3-10-3: 【一覧取得】 アイテムの並び順確認', function () {
    // 異なるorder順でアイテムをAPIで作成（作成順序でorderが自動設定される）
    $this->actingAs($this->user)->post('/shopping-items', [
        'name' => 'アイテム1',
        'categoryId' => $this->categoryId
    ]);
    $this->actingAs($this->user)->post('/shopping-items', [
        'name' => 'アイテム2',
        'categoryId' => $this->categoryId
    ]);
    $this->actingAs($this->user)->post('/shopping-items', [
        'name' => 'アイテム3',
        'categoryId' => $this->categoryId
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-items');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // カテゴリごとにグループ化されているため、最初のカテゴリのアイテムを確認
    $firstCategory = $responseData[0];
    $items = $firstCategory['items'];
    expect($items[0]['name'])->toBe('アイテム1');
    expect($items[1]['name'])->toBe('アイテム2');
    expect($items[2]['name'])->toBe('アイテム3');
});

test('3-10-4: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-items');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'category',
                'items'
            ]
        ],
        'total'
    ]);

    // 正しいJSON形式でレスポンスが返されることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-5: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/shopping-items');

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

test('3-10-6: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/shopping-items');

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

test('3-10-7: 【一覧取得】 データベース接続エラー', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('indexGroupedByCategory')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->get('/shopping-items');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物リストの取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

test('3-10-8: 【一覧取得】 ShoppingService 例外', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('indexGroupedByCategory')
            ->once()
            ->andThrow(new \Exception('Service exception'));
    });

    $response = $this->actingAs($this->user)->get('/shopping-items');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物リストの取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

// ===== store() メソッドのテストケース =====

test('3-10-9: 【新規作成】 正常な買い物アイテム作成', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => []
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテム(牛乳)を作成しました。'
    ]);

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('shopping_items', [
        'group_id' => $this->group->id,
        'category_id' => $this->categoryId,
        'name' => '牛乳'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name',
            'isPinned',
            'isChecked',
            'order',
            'category' => [
                'id',
                'name',
                'isDefault',
                'order'
            ],
            'tags'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-10: 【新規作成】 タグ紐づけ機能確認', function () {
    // タグを作成
    $tag = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);

    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => [
            [
                'id' => $tag->id,
                'name' => '特売品'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(201);

    // タグが正しく紐づけられていることを確認
    $itemId = $response->json('data.id');
    $item = ShoppingItem::with('tags')->find($itemId);
    expect($item->tags)->toHaveCount(1);
    expect($item->tags[0]->name)->toBe('特売品');
});

test('3-10-11: 【新規作成】 タグ未指定でアイテム作成（tags 省略）', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテム(牛乳)を作成しました。'
    ]);

    // タグが空配列で返されることを確認
    expect($response->json('data.tags'))->toBe([]);
});

test('3-10-12: 【新規作成】 タグ空配列でアイテム作成（tags=[]）', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => []
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテム(牛乳)を作成しました。'
    ]);

    // タグが空配列で返されることを確認
    expect($response->json('data.tags'))->toBe([]);
});

test('3-10-13: 【新規作成】 タグ null でアイテム作成（tags=null）', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => null
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテム(牛乳)を作成しました。'
    ]);

    // タグが空配列で返されることを確認
    expect($response->json('data.tags'))->toBe([]);
});

test('3-10-14: 【新規作成】 数量情報の確認', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => []
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(201);

    // 数量情報が正しく保存されていることを確認（isPinned, isChecked, orderなど）
    $itemData = $response->json('data');
    expect($itemData)->toHaveKey('isPinned');
    expect($itemData)->toHaveKey('isChecked');
    expect($itemData)->toHaveKey('order');
});

test('3-10-15: 【新規作成】 バリデーションエラー（アイテム名未入力）', function () {
    $data = [
        'categoryId' => $this->categoryId
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

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

test('3-10-16: 【新規作成】 バリデーションエラー（アイテム名が 255 文字超過）', function () {
    $data = [
        'name' => str_repeat('a', 256),
        'categoryId' => $this->categoryId
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

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

test('3-10-17: 【新規作成】 バリデーションエラー（カテゴリ ID 未入力）', function () {
    $data = [
        'name' => '牛乳'
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'categoryId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-18: 【新規作成】 バリデーションエラー（カテゴリ ID が UUID 形式でない）', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => 'invalid-uuid'
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'categoryId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-19: 【新規作成】 バリデーションエラー（tags が配列でない）', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tags']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'tags'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-20: 【新規作成】 バリデーションエラー（tags.id が UUID 形式でない）', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => [
            [
                'id' => 'invalid-uuid',
                'name' => '特売品'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tags.0.id']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'tags.0.id'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-21: 【新規作成】 バリデーションエラー（tags.name 未入力）', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => [
            [
                'id' => null
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tags.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'tags.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-22: 【新規作成】 バリデーションエラー（tags.name が文字列でない）', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => [
            [
                'name' => 123
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tags.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'tags.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-23: 【新規作成】 バリデーションエラー（tags.name が 255 文字超過）', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId,
        'tags' => [
            [
                'name' => str_repeat('a', 256)
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tags.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'tags.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-24: 【新規作成】 存在しないカテゴリ ID', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => '00000000-0000-0000-0000-000000000000'
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

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

test('3-10-25: 【新規作成】 未認証ユーザー', function () {
    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ];

    $response = $this->post('/shopping-items', $data);

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

test('3-10-26: 【新規作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ];

    $response = $this->actingAs($user)->post('/shopping-items', $data);

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

test('3-10-27: 【新規作成】 データベース接続エラー', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物アイテムの作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

test('3-10-28: 【新規作成】 アイテム作成失敗', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Create failed'));
    });

    $data = [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物アイテムの作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

// ===== bulkUpdate() メソッドのテストケース =====

test('3-10-29: 【一括更新】 正常な買い物アイテム一括更新', function () {
    // テスト用のアイテムをAPIで作成
    $item1Response = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $item1Id = $item1Response->json('data.id');

    $item2Response = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => 'パン',
        'categoryId' => $this->categoryId
    ]);
    $item2Id = $item2Response->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $item1Id,
                'name' => '牛乳(更新)',
                'categoryId' => $this->categoryId,
                'isPinned' => true,
                'isChecked' => false,
                'order' => 1,
                'tags' => []
            ],
            [
                'id' => $item2Id,
                'name' => 'パン(更新)',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => true,
                'order' => 0,
                'tags' => []
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを2件更新しました。'
    ]);

    // データベースの更新を確認
    $this->assertDatabaseHas('shopping_items', [
        'id' => $item1Id,
        'name' => '牛乳(更新)',
        'is_pinned' => true,
        'order' => 1
    ]);
    $this->assertDatabaseHas('shopping_items', [
        'id' => $item2Id,
        'name' => 'パン(更新)',
        'is_checked' => true,
        'order' => 0
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-30: 【一括更新】 一括更新成功メッセージの確認', function () {
    // テスト用のアイテムをAPIで作成
    $itemResponse = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $itemId = $itemResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $itemId,
                'name' => '牛乳(更新)',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => []
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('買い物アイテムを1件更新しました。');
});

test('3-10-31: 【一括更新】 既存タグを ID 未指定・同名で更新', function () {
    // 既存のタグを作成（タグはAPIでの作成がないため直接作成）
    $tag = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);

    // テスト用のアイテムをAPIで作成
    $itemResponse = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $itemId = $itemResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $itemId,
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => [
                    [
                        'name' => '特売品'  // IDを指定せず、同じ名前を提供
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(200);

    // 新しいタグが作成されず、既存のタグIDが再利用されることを確認
    $tagsCount = ShoppingTag::where('group_id', $this->group->id)
        ->where('name', '特売品')
        ->count();
    expect($tagsCount)->toBe(1);

    // アイテムに既存のタグが紐づけられていることを確認
    $updatedItem = ShoppingItem::with('tags')->find($itemId);
    expect($updatedItem->tags)->toHaveCount(1);
    expect($updatedItem->tags[0]->id)->toBe($tag->id);
});

test('3-10-32: 【一括更新】 新規タグを ID 未指定で追加', function () {
    // テスト用のアイテムをAPIで作成
    $itemResponse = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $itemId = $itemResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $itemId,
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => [
                    [
                        'name' => '新しいタグ'  // IDを指定せず、新しい名前を提供
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(200);

    // 新しいタグが作成されることを確認
    $this->assertDatabaseHas('shopping_tags', [
        'group_id' => $this->group->id,
        'name' => '新しいタグ'
    ]);

    // アイテムに新しいタグが紐づけられていることを確認
    $updatedItem = ShoppingItem::with('tags')->find($itemId);
    expect($updatedItem->tags)->toHaveCount(1);
    expect($updatedItem->tags[0]->name)->toBe('新しいタグ');
});

test('3-10-33: 【一括更新】 既存タグと新規タグを混在させた更新', function () {
    // 既存のタグを作成（タグはAPIでの作成がないため直接作成）
    $existingTag = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '既存タグ'
    ]);

    // テスト用のアイテムをAPIで作成
    $itemResponse = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $itemId = $itemResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $itemId,
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => [
                    [
                        'id' => $existingTag->id,
                        'name' => '既存タグ'
                    ],
                    [
                        'name' => '新規タグ'
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(200);

    // 既存タグは再利用され、新規タグは作成されることを確認
    $this->assertDatabaseHas('shopping_tags', [
        'id' => $existingTag->id,
        'name' => '既存タグ'
    ]);
    $this->assertDatabaseHas('shopping_tags', [
        'group_id' => $this->group->id,
        'name' => '新規タグ'
    ]);

    // アイテムに両方のタグが紐づけられていることを確認
    $updatedItem = ShoppingItem::with('tags')->find($itemId);
    expect($updatedItem->tags)->toHaveCount(2);
});

test('3-10-34: 【一括更新】 タグ未指定でアイテム更新（tags 省略）', function () {
    // テスト用のアイテムをAPIで作成
    $itemResponse = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $itemId = $itemResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $itemId,
                'name' => '牛乳(更新)',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを1件更新しました。'
    ]);

    // データベースの更新を確認
    $this->assertDatabaseHas('shopping_items', [
        'id' => $itemId,
        'name' => '牛乳(更新)'
    ]);
});

test('3-10-35: 【一括更新】 タグ空配列でアイテム更新（tags=[]）', function () {
    // テスト用のアイテムをAPIで作成
    $itemResponse = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $itemId = $itemResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $itemId,
                'name' => '牛乳(更新)',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => []
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを1件更新しました。'
    ]);

    // データベースの更新を確認
    $this->assertDatabaseHas('shopping_items', [
        'id' => $itemId,
        'name' => '牛乳(更新)'
    ]);
});

test('3-10-36: 【一括更新】 タグ null でアイテム更新（tags=null）', function () {
    // テスト用のアイテムをAPIで作成
    $itemResponse = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $itemId = $itemResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $itemId,
                'name' => '牛乳(更新)',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => null
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを1件更新しました。'
    ]);

    // データベースの更新を確認
    $this->assertDatabaseHas('shopping_items', [
        'id' => $itemId,
        'name' => '牛乳(更新)'
    ]);
});

test('3-10-37: 【一括更新】 存在しないアイテムの更新', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => []
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された買い物アイテムが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-38: 【一括更新】 他グループのアイテム更新', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    // 他グループのカテゴリとアイテムをAPIで作成
    $otherCategoryResponse = $this->actingAs($otherUser)->post('/shopping-categories', [
        'name' => '他のグループのカテゴリ',
        'order' => 0
    ]);
    $otherCategoryId = $otherCategoryResponse->json('data.id');

    $otherItemResponse = $this->actingAs($otherUser)->post('/shopping-items', [
        'name' => '他のグループのアイテム',
        'categoryId' => $otherCategoryId
    ]);
    $otherItemId = $otherItemResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $otherItemId,
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => []
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された買い物アイテムが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-39: 【一括更新】 存在しないタグ ID を指定', function () {
    // テスト用のアイテムをAPIで作成
    $itemResponse = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $itemId = $itemResponse->json('data.id');

    $data = [
        'data' => [
            [
                'id' => $itemId,
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => [
                    [
                        'id' => '00000000-0000-0000-0000-000000000000',
                        'name' => '存在しないタグ'
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

test('3-10-40: 【一括更新】 バリデーションエラー（data 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

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

test('3-10-37: 【一括更新】 バリデーションエラー（data が配列でない）', function () {
    $data = [
        'data' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

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

test('3-10-38: 【一括更新】 バリデーションエラー（data が空配列）', function () {
    $data = [
        'data' => []
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

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

test('3-10-39: 【一括更新】 バリデーションエラー（id 未入力）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

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

test('3-10-40: 【一括更新】 バリデーションエラー（id が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'id' => 'invalid-uuid',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

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

test('3-10-41: 【一括更新】 バリデーションエラー（name 未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

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

test('3-10-42: 【一括更新】 バリデーションエラー（name が文字列でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => 123,
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

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

test('3-10-43: 【一括更新】 バリデーションエラー（name が 255 文字超過）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => str_repeat('a', 256),
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

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

test('3-10-44: 【一括更新】 バリデーションエラー（categoryId 未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.categoryId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.categoryId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-45: 【一括更新】 バリデーションエラー（categoryId が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => 'invalid-uuid',
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.categoryId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.categoryId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-46: 【一括更新】 バリデーションエラー（isPinned 未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.isPinned']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.isPinned'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-47: 【一括更新】 バリデーションエラー（isPinned が boolean 型でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => 'not_boolean',
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.isPinned']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.isPinned'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-48: 【一括更新】 バリデーションエラー（isChecked 未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.isChecked']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.isChecked'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-49: 【一括更新】 バリデーションエラー（isChecked が boolean 型でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => 'not_boolean',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.isChecked']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.isChecked'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-50: 【一括更新】 バリデーションエラー（order 未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);

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

test('3-10-51: 【一括更新】 バリデーションエラー（order が数値でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 'abc'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);

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

test('3-10-52: 【一括更新】 バリデーションエラー（order が負の値）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => -1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.order']);

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

test('3-10-53: 【一括更新】 バリデーションエラー（tags が配列でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => 'not_array'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.tags'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-54: 【一括更新】 バリデーションエラー（tags.id が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => [
                    [
                        'id' => 'invalid-uuid',
                        'name' => 'タグ'
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags.0.id']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.tags.0.id'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-55: 【一括更新】 バリデーションエラー（tags.name 未入力）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => [
                    [
                        'id' => null
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.tags.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-56: 【一括更新】 バリデーションエラー（tags.name が文字列でない）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => [
                    [
                        'name' => 123
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.tags.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-57: 【一括更新】 バリデーションエラー（tags.name が 255 文字超過）', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0,
                'tags' => [
                    [
                        'name' => str_repeat('a', 256)
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.tags.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-58: 【一括更新】 未認証ユーザー', function () {
    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->put('/shopping-items/bulk', $data);

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

test('3-10-59: 【一括更新】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($user)->put('/shopping-items/bulk', $data);

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

test('3-10-60: 【一括更新】 データベース接続エラー', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('bulkUpdate')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物アイテムの一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

test('3-10-61: 【一括更新】 アイテム更新失敗', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('bulkUpdate')
            ->once()
            ->andThrow(new \Exception('Update failed'));
    });

    $data = [
        'data' => [
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'isPinned' => false,
                'isChecked' => false,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物アイテムの一括更新中にエラーが発生しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

// ===== bulkDestroy() メソッドのテストケース =====

test('3-10-62: 【一括削除】 正常な買い物アイテム一括削除', function () {
    // テスト用のアイテムをAPIで作成
    $item1Response = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $item1Id = $item1Response->json('data.id');

    $item2Response = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => 'パン',
        'categoryId' => $this->categoryId
    ]);
    $item2Id = $item2Response->json('data.id');

    $data = [
        'ids' => [$item1Id, $item2Id]
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを2件削除しました。'
    ]);

    // データベースから削除されていることを確認
    $this->assertDatabaseMissing('shopping_items', [
        'id' => $item1Id
    ]);
    $this->assertDatabaseMissing('shopping_items', [
        'id' => $item2Id
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-63: 【一括削除】 一括削除成功メッセージの確認', function () {
    // テスト用のアイテムをAPIで作成
    $itemResponse = $this->actingAs($this->user)->post('/shopping-items', [
        'name' => '牛乳',
        'categoryId' => $this->categoryId
    ]);
    $itemId = $itemResponse->json('data.id');

    $data = [
        'ids' => [$itemId]
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('買い物アイテムを1件削除しました。');
});

test('3-10-64: 【一括削除】 存在しないアイテムの削除', function () {
    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された買い物アイテムが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-65: 【一括削除】 他グループのアイテム削除', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    // 他グループのカテゴリとアイテムをAPIで作成
    $otherCategoryResponse = $this->actingAs($otherUser)->post('/shopping-categories', [
        'name' => '他のグループのカテゴリ',
        'order' => 0
    ]);
    $otherCategoryId = $otherCategoryResponse->json('data.id');

    $otherItemResponse = $this->actingAs($otherUser)->post('/shopping-items', [
        'name' => '他のグループのアイテム',
        'categoryId' => $otherCategoryId
    ]);
    $otherItemId = $otherItemResponse->json('data.id');

    $data = [
        'ids' => [$otherItemId]
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された買い物アイテムが見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-66: 【一括削除】 バリデーションエラー（IDs 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

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

test('3-10-67: 【一括削除】 バリデーションエラー（IDs が配列でない）', function () {
    $data = [
        'ids' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

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

test('3-10-68: 【一括削除】 バリデーションエラー（IDs が空配列）', function () {
    $data = [
        'ids' => []
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

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

test('3-10-69: 【一括削除】 バリデーションエラー（ID が UUID 形式でない）', function () {
    $data = [
        'ids' => ['invalid-uuid']
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids.0']);

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

test('3-10-70: 【一括削除】 未認証ユーザー', function () {
    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->delete('/shopping-items/bulk', $data);

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

test('3-10-71: 【一括削除】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($user)->delete('/shopping-items/bulk', $data);

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

test('3-10-72: 【一括削除】 データベース接続エラー', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('bulkDelete')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物アイテムの削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-10-73: 【一括削除】 アイテム削除失敗', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('bulkDelete')
            ->once()
            ->andThrow(new \Exception('Delete failed'));
    });

    $data = [
        'ids' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物アイテムの削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});
