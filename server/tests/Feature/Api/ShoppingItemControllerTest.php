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

test('3-9-1: 【一覧取得】 正常な買い物アイテム一覧取得', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
            [
                'name' => 'パン',
                'categoryId' => $this->categoryId,
                'order' => 1,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
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
                'id',
                'name',
                'isPinned',
                'isChecked',
                'categoryId',
                'tags',
                'order'
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-2: 【一覧取得】 カテゴリ別アイテム取得確認', function () {
    // 別のカテゴリをAPIで作成
    $this->actingAs($this->user)->post('/shopping-categories/bulk', [
        'data' => [
            ['name' => 'カテゴリ2', 'order' => 1]
        ]
    ]);
    $categoryIndexResponse = $this->actingAs($this->user)->get('/shopping-categories');
    $category2Id = collect($categoryIndexResponse->json('data'))->firstWhere('name', 'カテゴリ2')['id'];
    expect($category2Id)->not->toBeNull();

    // カテゴリ1・2のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
            [
                'name' => 'パン',
                'categoryId' => $category2Id,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-items');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 1次元配列であることを確認
    expect($responseData)->toBeArray();
    expect(count($responseData))->toBeGreaterThanOrEqual(2);

    // 各要素がShoppingItemの構造を持つことを確認
    expect($responseData[0])->toHaveKey('id');
    expect($responseData[0])->toHaveKey('name');
    expect($responseData[0])->toHaveKey('categoryId');
    expect($responseData[1])->toHaveKey('id');
    expect($responseData[1])->toHaveKey('name');
    expect($responseData[1])->toHaveKey('categoryId');
});

test('3-9-3: 【一覧取得】 アイテムの並び順確認', function () {
    // クライアント指定の order で並び順を再現
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => 'アイテム1',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
            [
                'name' => 'アイテム2',
                'categoryId' => $this->categoryId,
                'order' => 1,
                'isPinned' => false,
                'isChecked' => false,
            ],
            [
                'name' => 'アイテム3',
                'categoryId' => $this->categoryId,
                'order' => 2,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-items');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 同じカテゴリのアイテムをフィルタして並び順を確認
    $items = array_filter($responseData, fn($item) => $item['categoryId'] === $this->categoryId);
    $items = array_values($items);
    expect($items[0]['name'])->toBe('アイテム1');
    expect($items[1]['name'])->toBe('アイテム2');
    expect($items[2]['name'])->toBe('アイテム3');
});

test('3-9-4: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);

    $response = $this->actingAs($this->user)->get('/shopping-items');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'isPinned',
                'isChecked',
                'categoryId',
                'tags',
                'order'
            ]
        ],
        'total'
    ]);

    // 正しいJSON形式でレスポンスが返されることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-5: 【一覧取得】 未認証ユーザー', function () {
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

test('3-9-6: 【一覧取得】 グループが存在しない', function () {
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

test('3-9-7: 【一覧取得】 データベース接続エラー', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('index')
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

test('3-9-8: 【一覧取得】 ShoppingService 例外', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('index')
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

// ===== bulkStore() メソッドのテストケース =====

test('3-9-9: 【一括作成】 1件の一括作成（タグなし）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを1件作成しました。'
    ]);

    $this->assertDatabaseHas('shopping_items', [
        'group_id' => $this->group->id,
        'category_id' => $this->categoryId,
        'name' => '牛乳'
    ]);

    $response->assertJsonStructure(['success', 'message']);
    $response->assertJsonPath('data', null);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-10: 【一括作成】 1件の一括作成（タグあり）', function () {
    $tag = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);

    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
                'tags' => [
                    [
                        'id' => $tag->id,
                        'name' => '特売品'
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(201);

    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemData = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId);
    $item = ShoppingItem::with('tags')->find($itemData['id']);
    expect($item->tags)->toHaveCount(1);
    expect($item->tags[0]->name)->toBe('特売品');
});

test('3-9-11: 【一括作成】 id 指定でタグを紐づけ（name 省略）', function () {
    $tag = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品',
    ]);

    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
                'tags' => [
                    [
                        'id' => $tag->id,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(201);

    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemData = collect($indexResponse->json('data'))->first(fn ($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId);
    $item = ShoppingItem::with('tags')->find($itemData['id']);
    expect($item->tags)->toHaveCount(1);
    expect($item->tags[0]->id)->toBe($tag->id);
    expect($item->tags[0]->name)->toBe('特売品');
});

test('3-9-12: 【一括作成】 タグ未指定でアイテム作成（tags 省略）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを1件作成しました。',
    ]);

    $item = ShoppingItem::with('tags')->where('group_id', $this->group->id)->where('name', '牛乳')->first();
    expect($item->tags)->toHaveCount(0);
});

test('3-9-13: 【一括作成】 タグ空配列でアイテム作成（tags=[]）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
                'tags' => [],
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを1件作成しました。',
    ]);
    $response->assertJsonStructure(['success', 'message']);
});

test('3-9-14: 【一括作成】 タグ null でアイテム作成（tags=null）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
                'tags' => null,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを1件作成しました。',
    ]);
    $response->assertJsonStructure(['success', 'message']);
});

test('3-9-15: 【一括作成】 数量情報の確認', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 5,
                'isPinned' => true,
                'isChecked' => true,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(201);

    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemData = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId);
    expect($itemData['isPinned'])->toBeTrue();
    expect($itemData['isChecked'])->toBeTrue();
    expect($itemData['order'])->toBe(5);
});

test('3-9-16: 【一括作成】 複数件の一括作成', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
            [
                'name' => 'パン',
                'categoryId' => $this->categoryId,
                'order' => 1,
                'isPinned' => false,
                'isChecked' => false,
            ],
            [
                'name' => '卵',
                'categoryId' => $this->categoryId,
                'order' => 2,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '買い物アイテムを3件作成しました。'
    ]);

    $this->assertDatabaseHas('shopping_items', [
        'group_id' => $this->group->id,
        'name' => '牛乳'
    ]);
    $this->assertDatabaseHas('shopping_items', [
        'group_id' => $this->group->id,
        'name' => 'パン'
    ]);
    $this->assertDatabaseHas('shopping_items', [
        'group_id' => $this->group->id,
        'name' => '卵'
    ]);
});

test('3-9-17: 【一括作成】 一括作成成功メッセージの確認', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(201);
    $message = $response->json('message');
    expect($message)->toBe('買い物アイテムを1件作成しました。');
});

test('3-9-18: 【一括作成】 バリデーションエラー（data 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

    $responseData = $response->json();
    $this->assertContains('dataは必ず指定してください。', $responseData['errors']['data']);

    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data'
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-19: 【一括作成】 バリデーションエラー（data が配列でない）', function () {
    $data = [
        'data' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

    $responseData = $response->json();
    $this->assertContains('dataは配列でなくてはなりません。', $responseData['errors']['data']);

    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data'
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-20: 【一括作成】 バリデーションエラー（data が空配列）', function () {
    $data = [
        'data' => []
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data']);

    $responseData = $response->json();
    $this->assertContains('dataは1個以上指定してください。', $responseData['errors']['data']);

    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data'
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-21: 【一括作成】 バリデーションエラー（name 未入力）', function () {
    $data = [
        'data' => [
            [
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

    $responseData = $response->json();
    $this->assertContains('data.*.nameは必ず指定してください。', $responseData['errors']['data.0.name']);

    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'data.0.name'
        ]
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-22: 【一括作成】 バリデーションエラー（name が文字列でない）', function () {
    $data = [
        'data' => [
            [
                'name' => 123,
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

    $responseData = $response->json();
    $this->assertContains('data.*.nameは文字列を指定してください。', $responseData['errors']['data.0.name']);
});

test('3-9-23: 【一括作成】 バリデーションエラー（name が 255 文字超過）', function () {
    $data = [
        'data' => [
            [
                'name' => str_repeat('a', 256),
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.name']);

    $responseData = $response->json();
    $this->assertContains('data.*.nameは、255文字以内で指定してください。', $responseData['errors']['data.0.name']);
});

test('3-9-24: 【一括作成】 バリデーションエラー（categoryId 未入力）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.categoryId']);

    $responseData = $response->json();
    $this->assertContains('data.*.categoryIdは必ず指定してください。', $responseData['errors']['data.0.categoryId']);
});

test('3-9-25: 【一括作成】 バリデーションエラー（categoryId が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => 'invalid-uuid',
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.categoryId']);

    $responseData = $response->json();
    $this->assertContains('data.*.categoryIdに有効なUUIDを指定してください。', $responseData['errors']['data.0.categoryId']);
});

test('3-9-26: 【一括作成】 バリデーションエラー（tags が配列でない）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'tags' => 'not_array'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags']);

    $responseData = $response->json();
    $this->assertContains('data.*.tagsは配列でなくてはなりません。', $responseData['errors']['data.0.tags']);
});

test('3-9-27: 【一括作成】 バリデーションエラー（tags.id が UUID 形式でない）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
                'tags' => [
                    [
                        'id' => 'invalid-uuid',
                        'name' => '特売品'
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags.0.id']);

    $responseData = $response->json();
    $this->assertContains('data.*.tags.*.idに有効なUUIDを指定してください。', $responseData['errors']['data.0.tags.0.id']);
});

test('3-9-28: 【一括作成】 バリデーションエラー（tags.id と tags.name が両方未指定）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
                'tags' => [
                    [
                        'id' => null
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags.0.name']);

    $responseData = $response->json();
    $this->assertContains('タグ名にはidまたはnameのいずれかを指定してください。', $responseData['errors']['data.0.tags.0.name']);
});

test('3-9-29: 【一括作成】 バリデーションエラー（tags.name が文字列でない）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
                'tags' => [
                    [
                        'name' => 123
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags.0.name']);

    $responseData = $response->json();
    $this->assertContains('data.*.tags.*.nameは文字列を指定してください。', $responseData['errors']['data.0.tags.0.name']);
});

test('3-9-30: 【一括作成】 バリデーションエラー（tags.name が 255 文字超過）', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
                'tags' => [
                    [
                        'name' => str_repeat('a', 256)
                    ]
                ]
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.0.tags.0.name']);

    $responseData = $response->json();
    $this->assertContains('data.*.tags.*.nameは、255文字以内で指定してください。', $responseData['errors']['data.0.tags.0.name']);
});

test('3-9-31: 【一括作成】 存在しないカテゴリ ID', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => '00000000-0000-0000-0000-000000000000',
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された買い物カテゴリーが見つかりませんでした。'
    ]);
    $response->assertJsonStructure(['success', 'message']);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-32: 【一括作成】 未認証ユーザー', function () {
    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->post('/shopping-items/bulk', $data);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);
    $response->assertJsonStructure(['success', 'message']);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-33: 【一括作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。'
    ]);
    $response->assertJsonStructure(['success', 'message']);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-34: 【一括作成】 サービス例外', function () {
    $this->mock(\App\Services\ShoppingItemService::class, function ($mock) {
        $mock->shouldReceive('bulkCreate')
            ->once()
            ->andThrow(new \Exception('Service exception'));
    });

    $data = [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/shopping-items/bulk', $data);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '買い物アイテムの作成に失敗しました。'
    ]);
    $response->assertJsonStructure(['success', 'message']);
});

// ===== bulkUpdate() メソッドのテストケース =====

test('3-9-35: 【一括更新】 正常な買い物アイテム一括更新', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
            [
                'name' => 'パン',
                'categoryId' => $this->categoryId,
                'order' => 1,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $item1Id = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];
    $item2Id = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === 'パン' && $i['categoryId'] === $this->categoryId)['id'];

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

    // レスポンス構造の確認（success + message のみ）
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-9-36: 【一括更新】 一括更新成功メッセージの確認', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

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

test('3-9-37: 【一括更新】 既存タグを ID 未指定・同名で更新', function () {
    // 既存のタグを作成（タグはAPIでの作成がないため直接作成）
    $tag = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品'
    ]);

    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

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

test('3-9-38: 【一括更新】 新規タグを ID 未指定で追加', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

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

test('3-9-39: 【一括更新】 既存タグと新規タグを混在させた更新', function () {
    // 既存のタグを作成（タグはAPIでの作成がないため直接作成）
    $existingTag = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '既存タグ'
    ]);

    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

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

test('3-9-40: 【一括更新】 タグ未指定でアイテム更新（tags 省略）', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

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

test('3-9-41: 【一括更新】 id 指定でタグを紐づけ（name 省略）', function () {
    $tag = ShoppingTag::create([
        'group_id' => $this->group->id,
        'name' => '特売品',
    ]);

    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn ($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

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
                        'id' => $tag->id,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

    $response->assertStatus(200);

    $updatedItem = ShoppingItem::with('tags')->find($itemId);
    expect($updatedItem->tags)->toHaveCount(1);
    expect($updatedItem->tags[0]->id)->toBe($tag->id);
    expect($updatedItem->tags[0]->name)->toBe('特売品');
});

test('3-9-42: 【一括更新】 タグ空配列でアイテム更新（tags=[]）', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

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

test('3-9-43: 【一括更新】 タグ null でアイテム更新（tags=null）', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

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

test('3-9-44: 【一括更新】 存在しないアイテムの更新', function () {
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

test('3-9-45: 【一括更新】 他グループのアイテム更新', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    // 他グループのカテゴリとアイテムをAPIで作成
    $this->actingAs($otherUser)->post('/shopping-categories/bulk', [
        'data' => [
            ['name' => '他のグループのカテゴリ', 'order' => 0]
        ]
    ]);
    $otherCategoryIndexResponse = $this->actingAs($otherUser)->get('/shopping-categories');
    $otherCategoryId = collect($otherCategoryIndexResponse->json('data'))->firstWhere('name', '他のグループのカテゴリ')['id'];
    expect($otherCategoryId)->not->toBeNull();

    $this->actingAs($otherUser)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '他のグループのアイテム',
                'categoryId' => $otherCategoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $otherItemIndexResponse = $this->actingAs($otherUser)->get('/shopping-items');
    $otherItemId = collect($otherItemIndexResponse->json('data'))->first(fn($i) => $i['name'] === '他のグループのアイテム' && $i['categoryId'] === $otherCategoryId)['id'];

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

test('3-9-46: 【一括更新】 存在しないタグ ID を指定', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

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

test('3-9-47: 【一括更新】 バリデーションエラー（data 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

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

test('3-9-48: 【一括更新】 バリデーションエラー（data が配列でない）', function () {
    $data = [
        'data' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

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

test('3-9-49: 【一括更新】 バリデーションエラー（data が空配列）', function () {
    $data = [
        'data' => []
    ];

    $response = $this->actingAs($this->user)->put('/shopping-items/bulk', $data);

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

test('3-9-50: 【一括更新】 バリデーションエラー（id 未入力）', function () {
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

test('3-9-51: 【一括更新】 バリデーションエラー（id が UUID 形式でない）', function () {
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

test('3-9-52: 【一括更新】 バリデーションエラー（name 未入力）', function () {
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

test('3-9-53: 【一括更新】 バリデーションエラー（name が文字列でない）', function () {
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

test('3-9-54: 【一括更新】 バリデーションエラー（name が 255 文字超過）', function () {
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

test('3-9-55: 【一括更新】 バリデーションエラー（categoryId 未入力）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.categoryIdは必ず指定してください。', $responseData['errors']['data.0.categoryId']);

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

test('3-9-56: 【一括更新】 バリデーションエラー（categoryId が UUID 形式でない）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.categoryIdに有効なUUIDを指定してください。', $responseData['errors']['data.0.categoryId']);

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

test('3-9-57: 【一括更新】 バリデーションエラー（isPinned 未入力）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.isPinnedは必ず指定してください。', $responseData['errors']['data.0.isPinned']);

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

test('3-9-58: 【一括更新】 バリデーションエラー（isPinned が boolean 型でない）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.isPinnedは、trueかfalseを指定してください。', $responseData['errors']['data.0.isPinned']);

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

test('3-9-59: 【一括更新】 バリデーションエラー（isChecked 未入力）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.isCheckedは必ず指定してください。', $responseData['errors']['data.0.isChecked']);

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

test('3-9-60: 【一括更新】 バリデーションエラー（isChecked が boolean 型でない）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.isCheckedは、trueかfalseを指定してください。', $responseData['errors']['data.0.isChecked']);

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

test('3-9-61: 【一括更新】 バリデーションエラー（order 未入力）', function () {
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

test('3-9-62: 【一括更新】 バリデーションエラー（order が数値でない）', function () {
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

test('3-9-63: 【一括更新】 バリデーションエラー（order が負の値）', function () {
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

test('3-9-64: 【一括更新】 バリデーションエラー（tags が配列でない）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.tagsは配列でなくてはなりません。', $responseData['errors']['data.0.tags']);

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

test('3-9-65: 【一括更新】 バリデーションエラー（tags.id が UUID 形式でない）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.tags.*.idに有効なUUIDを指定してください。', $responseData['errors']['data.0.tags.0.id']);

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

test('3-9-66: 【一括更新】 バリデーションエラー（tags.id と tags.name が両方未指定）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('タグ名にはidまたはnameのいずれかを指定してください。', $responseData['errors']['data.0.tags.0.name']);

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

test('3-9-67: 【一括更新】 バリデーションエラー（tags.name が文字列でない）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.tags.*.nameは文字列を指定してください。', $responseData['errors']['data.0.tags.0.name']);

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

test('3-9-68: 【一括更新】 バリデーションエラー（tags.name が 255 文字超過）', function () {
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

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('data.*.tags.*.nameは、255文字以内で指定してください。', $responseData['errors']['data.0.tags.0.name']);

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

test('3-9-69: 【一括更新】 未認証ユーザー', function () {
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

test('3-9-70: 【一括更新】 グループが存在しない', function () {
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

test('3-9-71: 【一括更新】 データベース接続エラー', function () {
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

test('3-9-72: 【一括更新】 アイテム更新失敗', function () {
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

test('3-9-73: 【一括削除】 正常な買い物アイテム一括削除', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
            [
                'name' => 'パン',
                'categoryId' => $this->categoryId,
                'order' => 1,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $item1Id = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];
    $item2Id = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === 'パン' && $i['categoryId'] === $this->categoryId)['id'];

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

test('3-9-74: 【一括削除】 一括削除成功メッセージの確認', function () {
    // テスト用のアイテムをAPIで作成
    $this->actingAs($this->user)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '牛乳',
                'categoryId' => $this->categoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $indexResponse = $this->actingAs($this->user)->get('/shopping-items');
    $itemId = collect($indexResponse->json('data'))->first(fn($i) => $i['name'] === '牛乳' && $i['categoryId'] === $this->categoryId)['id'];

    $data = [
        'ids' => [$itemId]
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('買い物アイテムを1件削除しました。');
});

test('3-9-75: 【一括削除】 存在しないアイテムの削除', function () {
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

test('3-9-76: 【一括削除】 他グループのアイテム削除', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id
    ]);

    // 他グループのカテゴリとアイテムをAPIで作成
    $this->actingAs($otherUser)->post('/shopping-categories/bulk', [
        'data' => [
            ['name' => '他のグループのカテゴリ', 'order' => 0]
        ]
    ]);
    $otherCategoryIndexResponse = $this->actingAs($otherUser)->get('/shopping-categories');
    $otherCategoryId = collect($otherCategoryIndexResponse->json('data'))->firstWhere('name', '他のグループのカテゴリ')['id'];
    expect($otherCategoryId)->not->toBeNull();

    $this->actingAs($otherUser)->post('/shopping-items/bulk', [
        'data' => [
            [
                'name' => '他のグループのアイテム',
                'categoryId' => $otherCategoryId,
                'order' => 0,
                'isPinned' => false,
                'isChecked' => false,
            ],
        ],
    ]);
    $otherItemIndexResponse = $this->actingAs($otherUser)->get('/shopping-items');
    $otherItemId = collect($otherItemIndexResponse->json('data'))->first(fn($i) => $i['name'] === '他のグループのアイテム' && $i['categoryId'] === $otherCategoryId)['id'];

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

test('3-9-77: 【一括削除】 バリデーションエラー（IDs 未入力）', function () {
    $data = [];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

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

test('3-9-78: 【一括削除】 バリデーションエラー（IDs が配列でない）', function () {
    $data = [
        'ids' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

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

test('3-9-79: 【一括削除】 バリデーションエラー（IDs が空配列）', function () {
    $data = [
        'ids' => []
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

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

test('3-9-80: 【一括削除】 バリデーションエラー（ID が UUID 形式でない）', function () {
    $data = [
        'ids' => ['invalid-uuid']
    ];

    $response = $this->actingAs($this->user)->delete('/shopping-items/bulk', $data);

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

test('3-9-81: 【一括削除】 未認証ユーザー', function () {
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

test('3-9-82: 【一括削除】 グループが存在しない', function () {
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

test('3-9-83: 【一括削除】 データベース接続エラー', function () {
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

test('3-9-84: 【一括削除】 アイテム削除失敗', function () {
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
