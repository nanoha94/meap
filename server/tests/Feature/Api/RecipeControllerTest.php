<?php

use App\Models\User;
use App\Models\Group;
use App\Models\RecipeCategory;
use Illuminate\Support\Facades\DB;
use App\Models\IngredientCategory;
use App\Models\IngredientUnit;
use App\Models\Image;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @var User $user
 * @var Group $group
 * @var RecipeCategory $recipeCategory
 * @var IngredientCategory $ingredientCategory
 * @var IngredientUnit $ingredientUnit
 * @var Image $image
 */
beforeEach(function () {
    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $this->group = Group::create([
        'group_size' => 1
    ]);

    $this->group->users()->attach($this->user->id);

    // ユーザーとグループの関係をリフレッシュ
    $this->user->refresh();
    $this->user->load('groups');

    // テスト用の料理カテゴリを作成
    $this->recipeCategory = RecipeCategory::create([
        'group_id' => $this->group->id,
        'name' => 'テスト料理カテゴリ',
        'order' => 0
    ]);

    // テスト用の食材カテゴリを作成
    $this->ingredientCategory = IngredientCategory::create([
        'group_id' => $this->group->id,
        'name' => 'テスト食材カテゴリ',
        'order' => 0
    ]);

    // テスト用の食材単位を作成
    $this->ingredientUnit = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'g',
        'position' => 'suffix',
        'order' => 0,
        'requires_quantity' => true
    ]);

    // テスト用の画像を作成
    $this->image = Image::create([
        'src' => "/storage/images/{$this->group->id}/test.jpg",
        'width' => 800,
        'height' => 600
    ]);
});

// ===== index() メソッドのテストケース =====

test('3-8-1: 【一覧取得】 正常な料理一覧取得', function () {
    // テスト用の料理をAPIで作成
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '肉じゃが',
        'servingCount' => 2
    ]);

    $response = $this->actingAs($this->user)->get('/recipes');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name'
            ]
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// TODO: 未実装のためコメントアウト
// test('3-8-2: 【一覧取得】 ページネーション機能確認', function () {
//     // 複数の料理を作成
//     for ($i = 1; $i <= 15; $i++) {
//         $this->actingAs($this->user)->post('/recipes', [
//             'name' => "料理{$i}"
//         ]);
//     }

//     $response = $this->actingAs($this->user)->get('/recipes?page=1&per_page=10');

//     $response->assertStatus(200);
//     $responseData = $response->json('data');

//     // 10件取得されることを確認
//     expect(count($responseData))->toBeLessThanOrEqual(10);
// });

// TODO: 未実装のためコメントアウト
// test('3-8-3: 【一覧取得】 検索機能確認', function () {
//     // テスト用の料理をAPIで作成
//     $this->actingAs($this->user)->post('/recipes', [
//         'name' => 'カレーライス'
//     ]);
//     $this->actingAs($this->user)->post('/recipes', [
//         'name' => '肉じゃが'
//     ]);

//     $response = $this->actingAs($this->user)->get('/recipes?search=カレー');

//     $response->assertStatus(200);
//     $responseData = $response->json('data');

//     // 検索結果に「カレーライス」が含まれることを確認
//     $found = false;
//     foreach ($responseData as $recipe) {
//         if (str_contains($recipe['name'], 'カレー')) {
//             $found = true;
//             break;
//         }
//     }
//     expect($found)->toBeTrue();
// });

test('3-8-4: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用の料理をAPIで作成
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);

    $response = $this->actingAs($this->user)->get('/recipes');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'thumbnail',
                'categories' => [
                    '*' => [
                        'id',
                        'name',
                        'order'
                    ]
                ]
            ]
        ]
    ]);

    // 正しいJSON形式でレスポンスが返されることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-5: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/recipes');

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

test('3-8-6: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/recipes');

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

test('3-8-7: 【一覧取得】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->get('/recipes');

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

test('3-8-8: 【一覧取得】 RecipeService 例外', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Service exception'));
    });

    $response = $this->actingAs($this->user)->get('/recipes');

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

// ===== store() メソッドのテストケース =====

test('3-8-9: 【新規作成】 正常な料理作成', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を作成しました。'
    ]);

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('recipes', [
        'group_id' => $this->group->id,
        'owner_user_id' => $this->user->id,
        'name' => 'カレーライス',
        'status' => 'limited',
        'published_recipe_id' => null
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-10: 【新規作成】 最小限のデータで料理作成', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を作成しました。'
    ]);

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('recipes', [
        'group_id' => $this->group->id,
        'owner_user_id' => $this->user->id,
        'name' => 'カレーライス',
        'status' => 'limited',
        'published_recipe_id' => null
    ]);
});

test('3-8-11: 【新規作成】 料理にカテゴリを紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => [$this->recipeCategory->id]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // カテゴリが正しく紐づけられていることを確認
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('categories')->find($recipeId);
    expect($recipe->categories)->toHaveCount(1);
    expect($recipe->categories[0]->id)->toBe($this->recipeCategory->id);
});

test('3-8-12: 【新規作成】 料理に食材を紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 100
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 食材が正しく紐づけられていることを確認
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
});

test('3-8-13: 【新規作成】 最小限の必須フィールドのみで食材を紐づけ', function () {
    // requires_quantity=falseの単位を作成
    $unitWithoutQuantityRequired = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '個',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $unitWithoutQuantityRequired->id,
                'categoryId' => $this->ingredientCategory->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 食材が正しく紐づけられていることを確認
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
});

test('3-8-14: 【新規作成】 料理に手順を紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 手順が正しく紐づけられていることを確認
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('steps')->find($recipeId);
    expect($recipe->steps)->toHaveCount(1);
    expect($recipe->steps[0]->order)->toBe(0);
    expect($recipe->steps[0]->recipe_id)->toBe($recipeId);
});

test('3-8-15: 【新規作成】 最小限の必須フィールドのみで手順を紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 手順が正しく紐づけられていることを確認
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('steps')->find($recipeId);
    expect($recipe->steps)->toHaveCount(1);
    expect($recipe->steps[0]->instruction)->toBe('玉ねぎを切る');
    expect($recipe->steps[0]->order)->toBe(0);
    expect($recipe->steps[0]->recipe_id)->toBe($recipeId);
});

test('3-8-16: 【新規作成】 料理に画像を紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 画像が正しく紐づけられていることを確認
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);
});

test('3-8-17: 【新規作成】 requires_quantity=true の食材単位で数量指定', function () {
    // requires_quantity=true の食材単位を作成
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            'categoryId' => $this->ingredientCategory->id,
            'quantity' => 2.5
        ]]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'data' => [
            'name' => 'カレーライス',
            'ingredients' => [
                [
                    'name' => '玉ねぎ',
                    'unit' => [
                        'id' => $unitWithQuantity->id,
                        'name' => 'kg',
                        'position' => 'suffix',
                        'requiresQuantity' => true,
                        'order' => 1,
                    ],
                    'quantity' => 2.5,
                    'categoryId' => $this->ingredientCategory->id
                ]
            ]
        ]
    ]);
});

test('3-8-18: 【新規作成】 requires_quantity=false の食材単位で数量指定', function () {
    // requires_quantity=false の食材単位を作成
    $unitWithoutQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '適量',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithoutQuantity->id,
            'categoryId' => $this->ingredientCategory->id,
            'quantity' => 2.5
        ]]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'data' => [
            'name' => 'カレーライス',
            'ingredients' => [
                [
                    'name' => '玉ねぎ',
                    'unit' => [
                        'id' => $unitWithoutQuantity->id,
                        'name' => '適量',
                        'position' => 'suffix',
                        'requiresQuantity' => false,
                        'order' => 1,
                    ],
                    'quantity' => 2.5,
                    'categoryId' => $this->ingredientCategory->id
                ]
            ]
        ]
    ]);
});

test('3-8-19: 【新規作成】 requires_quantity=false の食材単位で数量省略', function () {
    // requires_quantity=false の食材単位を作成
    $unitWithoutQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '適量',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithoutQuantity->id,
            'categoryId' => $this->ingredientCategory->id
            // quantity を省略
        ]]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'data' => [
            'name' => 'カレーライス',
            'ingredients' => [
                [
                    'name' => '玉ねぎ',
                    'unit' => [
                        'id' => $unitWithoutQuantity->id,
                        'name' => '適量',
                        'position' => 'suffix',
                        'requiresQuantity' => false,
                        'order' => 1,
                    ],
                    'quantity' => null,
                    'categoryId' => $this->ingredientCategory->id
                ]
            ]
        ]
    ]);
});

test('3-8-20: 【新規作成】 すべての項目を含む料理作成', function () {
    // 追加の画像を作成（手順用）
    $stepImage = Image::create([
        'src' => "/storage/images/{$this->group->id}/step.jpg",
        'width' => 800,
        'height' => 600
    ]);

    $data = [
        'name' => 'スパイスカレー',
        'servingCount' => 4,
        'url' => 'https://example.com/recipe',
        'memo' => 'これはテスト用のメモです',
        'thumbnailId' => $this->image->id,
        'categoryIds' => [$this->recipeCategory->id],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 200,
                'order' => 0
            ],
            [
                'name' => 'にんじん',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 100,
                'order' => 1
            ]
        ],
        'steps' => [
            [
                'instruction' => '野菜を切る',
                'imageId' => $stepImage->id,
                'order' => 0
            ],
            [
                'instruction' => '野菜を炒める',
                'order' => 1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(スパイスカレー)を作成しました。'
    ]);

    $recipeId = $response->json('data.id');

    // データベースにすべての項目が正しく保存されていることを確認
    $this->assertDatabaseHas('recipes', [
        'id' => $recipeId,
        'name' => 'スパイスカレー',
        'serving_count' => 4,
        'url' => 'https://example.com/recipe',
        'memo' => 'これはテスト用のメモです'
    ]);

    // サムネイルが紐づけられていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);
    expect($recipe->thumbnails[0]->id)->toBe($this->image->id);

    // カテゴリが紐づけられていることを確認
    $recipe = Recipe::with('categories')->find($recipeId);
    expect($recipe->categories)->toHaveCount(1);
    expect($recipe->categories[0]->id)->toBe($this->recipeCategory->id);

    // 食材が紐づけられていることを確認
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(2);

    // 食材IDを取得
    $onionIngredient = $recipe->ingredients->firstWhere('name', '玉ねぎ');
    $carrotIngredient = $recipe->ingredients->firstWhere('name', 'にんじん');

    expect($onionIngredient)->not->toBeNull();
    expect($onionIngredient->name)->toBe('玉ねぎ');
    expect($onionIngredient->pivot->quantity)->toBe(200.0);
    expect($onionIngredient->pivot->order)->toBe(0);

    expect($carrotIngredient)->not->toBeNull();
    expect($carrotIngredient->name)->toBe('にんじん');
    expect($carrotIngredient->pivot->quantity)->toBe(100.0);
    expect($carrotIngredient->pivot->order)->toBe(1);

    // 手順が紐づけられていることを確認
    $recipe = Recipe::with('steps.images')->find($recipeId);
    expect($recipe->steps)->toHaveCount(2);
    expect($recipe->steps[0]->instruction)->toBe('野菜を切る');
    expect($recipe->steps[0]->order)->toBe(0);
    expect($recipe->steps[0]->images->first()?->id)->toBe($stepImage->id);
    expect($recipe->steps[1]->instruction)->toBe('野菜を炒める');
    expect($recipe->steps[1]->order)->toBe(1);
    expect($recipe->steps[1]->images->first())->toBeNull();

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-21: 【新規作成】 バリデーションエラー（requires_quantity=true の単位で数量省略）', function () {
    // requires_quantity=true の食材単位を作成
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true
    ]);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            'categoryId' => $this->ingredientCategory->id
            // quantity を省略
        ]]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.0.quantity']);
});

test('3-8-22: 【新規作成】 バリデーションエラー（name 未入力）', function () {
    $data = [
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-23: 【新規作成】 バリデーションエラー（name が文字列でない）', function () {
    $data = [
        'name' => 123,
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-24: 【新規作成】 バリデーションエラー（name が 255 文字超過）', function () {
    $data = [
        'name' => str_repeat('a', 256)
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-25: 【新規作成】 バリデーションエラー（url が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'url' => 123
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('urlは文字列を指定してください。', $responseData['errors']['url']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'url'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-26: 【新規作成】 バリデーションエラー（url が 2048 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'url' => 'https://' . str_repeat('a', 2050) . '.com'
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('urlは、2048文字以内で指定してください。', $responseData['errors']['url']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'url'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-27: 【新規作成】 バリデーションエラー（thumbnailId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'thumbnailId' => 'invalid-uuid'
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['thumbnailId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('thumbnailIdに有効なUUIDを指定してください。', $responseData['errors']['thumbnailId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'thumbnailId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-28: 【新規作成】 バリデーションエラー（categoryIds が配列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'categoryIds' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryIds']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('categoryIdsは配列でなくてはなりません。', $responseData['errors']['categoryIds']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'categoryIds'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-29: 【新規作成】 バリデーションエラー（categoryIds.* が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'categoryIds' => ['invalid-uuid']
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryIds.0']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('categoryIds.*に有効なUUIDを指定してください。', $responseData['errors']['categoryIds.0']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'categoryIds.0'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-30: 【新規作成】 バリデーションエラー（ingredients が配列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredientsは配列でなくてはなりません。', $responseData['errors']['ingredients']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-31: 【新規作成】 バリデーションエラー（ingredients.*.id が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'id' => 'invalid-uuid',
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.id']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.idに有効なUUIDを指定してください。', $responseData['errors']['ingredients.0.id']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.id'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-32: 【新規作成】 バリデーションエラー（ingredients.*.name 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.nameは必ず指定してください。', $responseData['errors']['ingredients.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-33: 【新規作成】 バリデーションエラー（ingredients.*.name が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => 123,
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.nameは文字列を指定してください。', $responseData['errors']['ingredients.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-34: 【新規作成】 バリデーションエラー（ingredients.*.name が 255 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => str_repeat('a', 256),
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.nameは、255文字以内で指定してください。', $responseData['errors']['ingredients.0.name']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-35: 【新規作成】 バリデーションエラー（ingredients.*.unitId 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'categoryId' => $this->ingredientCategory->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.unitId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.unitIdは必ず指定してください。', $responseData['errors']['ingredients.0.unitId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.unitId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-36: 【新規作成】 バリデーションエラー（ingredients.*.unitId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => 'invalid-uuid',
                'categoryId' => $this->ingredientCategory->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.unitId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.unitIdに有効なUUIDを指定してください。', $responseData['errors']['ingredients.0.unitId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.unitId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-37: 【新規作成】 バリデーションエラー（ingredients.*.categoryId 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.categoryIdは必ず指定してください。', $responseData['errors']['ingredients.0.categoryId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.categoryId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-38: 【新規作成】 バリデーションエラー（ingredients.*.categoryId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => 'invalid-uuid'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.categoryIdに有効なUUIDを指定してください。', $responseData['errors']['ingredients.0.categoryId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.categoryId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-39: 【新規作成】 バリデーションエラー（ingredients.*.quantity が数値でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 'not_numeric'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantity']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.quantityには、数字を指定してください。', $responseData['errors']['ingredients.0.quantity']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.quantity'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-40: 【新規作成】 バリデーションエラー（ingredients.*.order が整数でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'order' => 'not_integer'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.orderは整数で指定してください。', $responseData['errors']['ingredients.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-41: 【新規作成】 バリデーションエラー（ingredients.*.order が負の値）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'order' => -1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ingredients.*.orderには、0以上の数字を指定してください。', $responseData['errors']['ingredients.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-42: 【新規作成】 バリデーションエラー（steps が配列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => 'not_array'
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('stepsは配列でなくてはなりません。', $responseData['errors']['steps']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'steps'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-43: 【新規作成】 バリデーションエラー（steps.*.id が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'id' => 'invalid-uuid',
                'instruction' => '玉ねぎを切る',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.id']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('steps.*.idに有効なUUIDを指定してください。', $responseData['errors']['steps.0.id']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'steps.0.id'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-44: 【新規作成】 バリデーションエラー（steps.*.instruction 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.instruction']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('steps.*.instructionは必ず指定してください。', $responseData['errors']['steps.0.instruction']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'steps.0.instruction'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-45: 【新規作成】 バリデーションエラー（steps.*.instruction が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'instruction' => 123,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.instruction']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('steps.*.instructionは文字列を指定してください。', $responseData['errors']['steps.0.instruction']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'steps.0.instruction'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-46: 【新規作成】 バリデーションエラー（steps.*.instruction が 255 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'instruction' => str_repeat('a', 256),
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.instruction']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('steps.*.instructionは、255文字以内で指定してください。', $responseData['errors']['steps.0.instruction']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'steps.0.instruction'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-47: 【新規作成】 バリデーションエラー（steps.*.imageId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'imageId' => 'invalid-uuid',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.imageId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('steps.*.imageIdに有効なUUIDを指定してください。', $responseData['errors']['steps.0.imageId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'steps.0.imageId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-48: 【新規作成】 バリデーションエラー（steps.*.order 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'instruction' => '玉ねぎを切る'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('steps.*.orderは必ず指定してください。', $responseData['errors']['steps.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'steps.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-49: 【新規作成】 バリデーションエラー（steps.*.order が整数でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'order' => 'not_integer'
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('steps.*.orderは整数で指定してください。', $responseData['errors']['steps.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'steps.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-50: 【新規作成】 バリデーションエラー（steps.*.order が負の値）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'order' => -1
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.order']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('steps.*.orderには、0以上の数字を指定してください。', $responseData['errors']['steps.0.order']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'steps.0.order'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-51: 【新規作成】 バリデーションエラー（memo が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'memo' => 123
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['memo']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('memoは文字列を指定してください。', $responseData['errors']['memo']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'memo'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-52: 【新規作成】 バリデーションエラー（memo が 255 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'memo' => str_repeat('a', 256)
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['memo']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('memoは、255文字以内で指定してください。', $responseData['errors']['memo']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'memo'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-53: 【新規作成】 serving_count が null でも正常に作成できる', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => null
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $responseData = $response->json();
    $this->assertNull($responseData['data']['servingCount']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-54: 【新規作成】 バリデーションエラー（serving_count が整数でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 'abc'
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['servingCount']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('servingCountは整数で指定してください。', $responseData['errors']['servingCount']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'servingCount'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-55: 【新規作成】 バリデーションエラー（serving_count が 1 未満）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 0
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['servingCount']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('servingCountには、1以上の数字を指定してください。', $responseData['errors']['servingCount']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'servingCount'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-56: 【新規作成】 存在しない食材単位 ID 指定', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => '00000000-0000-0000-0000-000000000000',
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 100
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-57: 【新規作成】 他グループの食材単位 ID 指定', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    // 他グループの食材単位を作成
    $otherUnit = IngredientUnit::create([
        'group_id' => $otherGroup->id,
        'name' => '他のグループの単位',
        'position' => 'suffix',
        'requires_quantity' => true,
        'order' => 0
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $otherUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 100
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-58: 【新規作成】 存在しない食材カテゴリ ID 指定', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => '00000000-0000-0000-0000-000000000000',
                'quantity' => 100
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-59: 【新規作成】 他グループの食材カテゴリ ID 指定', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    // 他グループの食材カテゴリを作成
    $otherCategory = IngredientCategory::create([
        'group_id' => $otherGroup->id,
        'name' => '他のグループのカテゴリ',
        'order' => 0
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $otherCategory->id,
                'quantity' => 100
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-60: 【新規作成】 存在しない料理カテゴリ ID 指定', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-61: 【新規作成】 他グループの料理カテゴリ ID 指定', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    // 他グループの料理カテゴリを作成
    $otherCategory = RecipeCategory::create([
        'group_id' => $otherGroup->id,
        'name' => '他のグループのカテゴリ',
        'order' => 0
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => [$otherCategory->id]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-62: 【新規作成】 存在しない画像 ID 指定（thumbnailId）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => '00000000-0000-0000-0000-000000000000'
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-63: 【新規作成】 他グループの画像 ID 指定（thumbnailId）', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    // 他グループの画像を作成
    $otherImage = Image::create([
        'src' => "/storage/images/{$otherGroup->id}/other_test.jpg",
        'width' => 800,
        'height' => 600
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $otherImage->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-64: 【新規作成】 存在しない画像 ID 指定（steps.*.imageId）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'imageId' => '00000000-0000-0000-0000-000000000000',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-65: 【新規作成】 他グループの画像 ID 指定（steps.*.imageId）', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    // 他グループの画像を作成
    $otherImage = Image::create([
        'src' => "/storage/images/{$otherGroup->id}/other_test.jpg",
        'width' => 800,
        'height' => 600
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'imageId' => $otherImage->id,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-66: 【新規作成】 未認証ユーザー', function () {
    $data = [
        'name' => 'カレーライス'
    ];

    $response = $this->post('/recipes', $data);

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

test('3-8-67: 【新規作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $data = [
        'name' => 'カレーライス'
    ];

    $response = $this->actingAs($user)->post('/recipes', $data);

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

test('3-8-68: 【新規作成】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-69: 【新規作成】 料理作成失敗', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Create failed'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-70: 【新規作成】 食材紐づけ失敗', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Ingredient sync failed'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'quantity' => 100,
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-71: 【新規作成】 手順紐づけ失敗', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Step sync failed'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-72: 【新規作成】 画像紐づけ失敗', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Image attachment failed'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

test('3-8-73: 【新規作成】 ImageService 例外', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('ImageService exception'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

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

// ===== show() メソッドのテストケース =====

test('3-8-74: 【詳細取得】 正常な料理詳細取得', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->get("/recipes/{$recipeId}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name',
            'categories',
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-75: 【詳細取得】 すべての項目を含む料理詳細取得', function () {
    // 追加の画像を作成（手順用）
    $stepImage = Image::create([
        'src' => "/storage/images/{$this->group->id}/step.jpg",
        'width' => 800,
        'height' => 600
    ]);

    // テスト用の料理をAPIで作成（すべての項目を含む）
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'スパイスカレー',
        'servingCount' => 4,
        'url' => 'https://example.com/recipe',
        'memo' => 'これはテスト用のメモです',
        'thumbnailId' => $this->image->id,
        'categoryIds' => [$this->recipeCategory->id],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 200,
                'order' => 0
            ],
            [
                'name' => 'にんじん',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 100,
                'order' => 1
            ]
        ],
        'steps' => [
            [
                'instruction' => '野菜を切る',
                'imageId' => $stepImage->id,
                'order' => 0
            ],
            [
                'instruction' => '野菜を炒める',
                'order' => 1
            ]
        ]
    ]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->get("/recipes/{$recipeId}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name',
            'thumbnail',
            'url',
            'steps',
            'memo',
            'servingCount',
            'categories',
            'ingredients',
            'ownerUserId',
            'status',
            'publishedRecipeId',
        ]
    ]);

    // すべての項目が正しく取得されていることを確認
    $responseData = $response->json('data');
    expect($responseData['name'])->toBe('スパイスカレー');
    expect($responseData['servingCount'])->toBe(4);
    expect($responseData['url'])->toBe('https://example.com/recipe');
    expect($responseData['memo'])->toBe('これはテスト用のメモです');
    expect($responseData['categories'])->toBeArray();
    expect($responseData['categories'])->toHaveCount(1);
    expect($responseData['ingredients'])->toBeArray();
    expect($responseData['ingredients'])->toHaveCount(2);
    expect($responseData['steps'])->toBeArray();
    expect($responseData['steps'])->toHaveCount(2);

    // 食材の詳細確認
    $onionIngredient = collect($responseData['ingredients'])->firstWhere('name', '玉ねぎ');
    expect($onionIngredient)->not->toBeNull();
    expect($onionIngredient['quantity'])->toBe(200);

    // 手順の詳細確認
    $firstStep = $responseData['steps'][0];
    expect($firstStep['instruction'])->toBe('野菜を切る');
    expect($firstStep['image'])->not->toBeNull();
    expect($firstStep['image']['id'])->toBe($stepImage->id);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-76: 【詳細取得】 存在しない料理詳細取得', function () {
    $response = $this->actingAs($this->user)->get('/recipes/00000000-0000-0000-0000-000000000000');

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

test('3-8-77: 【詳細取得】 他グループの料理詳細取得', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    // 他グループの料理を作成
    $otherRecipe = Recipe::create([
        'group_id' => $otherGroup->id,
        'owner_user_id' => $otherUser->id,
        'name' => '他のグループの料理'
    ]);

    $response = $this->actingAs($this->user)->get("/recipes/{$otherRecipe->id}");

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

test('3-8-78: 【詳細取得】 未認証ユーザー', function () {
    $response = $this->get('/recipes/00000000-0000-0000-0000-000000000000');

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

test('3-8-79: 【詳細取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/recipes/00000000-0000-0000-0000-000000000000');

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

test('3-8-80: 【詳細取得】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('show')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->get('/recipes/00000000-0000-0000-0000-000000000000');

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

// ===== update() メソッドのテストケース =====

test('3-8-81: 【更新】 正常な料理更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'スパイスカレー',
        'servingCount' => 6
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(スパイスカレー)を更新しました。'
    ]);

    // データベースが更新されていることを確認
    $this->assertDatabaseHas('recipes', [
        'id' => $recipeId,
        'name' => 'スパイスカレー'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-82: 【更新】 最小限のデータで料理更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'スパイスカレー',
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(スパイスカレー)を更新しました。'
    ]);
});

test('3-8-83: 【更新】 料理のカテゴリ更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => [$this->recipeCategory->id]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // カテゴリが正しく更新されていることを確認
    $recipe = Recipe::with('categories')->find($recipeId);
    expect($recipe->categories)->toHaveCount(1);
    expect($recipe->categories[0]->id)->toBe($this->recipeCategory->id);
});

test('3-8-84: 【更新】 料理の食材更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 200
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 食材が正しく更新されていることを確認
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
});

test('3-8-85: 【更新】 最小限の必須フィールドのみで食材を更新', function () {
    // requires_quantity=falseの単位を作成
    $unitWithoutQuantityRequired = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '個',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false
    ]);

    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $unitWithoutQuantityRequired->id,
                'categoryId' => $this->ingredientCategory->id
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 食材が正しく更新されていることを確認
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
});

test('3-8-86: 【更新】 料理の手順更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '野菜を炒める',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 手順が正しく更新されていることを確認
    $recipe = Recipe::with('steps')->find($recipeId);
    expect($recipe->steps)->toHaveCount(1);
    expect($recipe->steps[0]->order)->toBe(0);
});

test('3-8-87: 【更新】 最小限の必須フィールドのみで手順を更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '野菜を炒める',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
});

test('3-8-88: 【更新】 手順の画像を削除（imageIdがnull）', function () {
    // テスト用の料理をAPIで作成し、手順に画像を紐づけ
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '野菜を炒める',
                'imageId' => $this->image->id,
                'order' => 0
            ]
        ]
    ]);
    $recipeId = $createResponse->json('data.id');

    // 手順IDを取得
    $recipe = Recipe::with('steps')->find($recipeId);
    $stepId = $recipe->steps->first()->id;

    // 画像を削除（imageIdをnullに指定）
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'id' => $stepId,
                'instruction' => '野菜を炒める',
                'imageId' => null,
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 画像が削除されていることを確認
    $recipe = Recipe::with('steps.images')->find($recipeId);
    expect($recipe->steps->first()->images)->toHaveCount(0);
});

test('3-8-89: 【更新】 手順の画像を削除（imageIdキーが存在しない）', function () {
    // テスト用の料理をAPIで作成し、手順に画像を紐づけ
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '野菜を炒める',
                'imageId' => $this->image->id,
                'order' => 0
            ]
        ]
    ]);
    $recipeId = $createResponse->json('data.id');

    // 手順IDを取得
    $recipe = Recipe::with('steps')->find($recipeId);
    $stepId = $recipe->steps->first()->id;

    // 画像を削除（imageIdキーを省略）
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'id' => $stepId,
                'instruction' => '野菜を炒める',
                'order' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 画像が削除されていることを確認
    $recipe = Recipe::with('steps.images')->find($recipeId);
    expect($recipe->steps->first()->images)->toHaveCount(0);
});

test('3-8-90: 【更新】 料理の画像更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 画像が正しく更新されていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);
});

test('3-8-91: 【更新】 サムネイルを削除（thumbnailIdがnull）', function () {
    // テスト用の料理をAPIで作成し、サムネイルを紐づけ
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id
    ]);
    $recipeId = $createResponse->json('data.id');

    // サムネイルが紐づいていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);

    // サムネイルを削除（thumbnailIdをnullに指定）
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => null
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // サムネイルが削除されていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(0);
});

test('3-8-92: 【更新】 サムネイルを削除（thumbnailIdキーが存在しない）', function () {
    // テスト用の料理をAPIで作成し、サムネイルを紐づけ
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id
    ]);
    $recipeId = $createResponse->json('data.id');

    // サムネイルが紐づいていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);

    // サムネイルを削除（thumbnailIdキーを省略）
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // サムネイルが削除されていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(0);
});

test('3-8-93: 【更新】 更新成功メッセージの確認', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'スパイスカレー',
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('料理/レシピ(スパイスカレー)を更新しました。');
});

test('3-8-94: 【更新】 requires_quantity=true の食材単位で数量指定', function () {
    // requires_quantity=true の食材単位を作成
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true
    ]);

    // 料理を作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            'categoryId' => $this->ingredientCategory->id,
            'quantity' => 2.5
        ]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'name' => 'カレーライス',
            'ingredients' => [
                [
                    'name' => '玉ねぎ',
                    'unit' => [
                        'id' => $unitWithQuantity->id,
                        'name' => 'kg',
                        'position' => 'suffix',
                        'requiresQuantity' => true,
                        'order' => 1,
                    ],
                    'quantity' => 2.5,
                    'categoryId' => $this->ingredientCategory->id
                ]
            ]
        ]
    ]);
});

test('3-8-95: 【更新】 requires_quantity=false の食材単位で数量指定', function () {
    // requires_quantity=false の食材単位を作成
    $unitWithoutQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '適量',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false
    ]);

    // 料理を作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithoutQuantity->id,
            'categoryId' => $this->ingredientCategory->id,
            'quantity' => 2.5
        ]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'name' => 'カレーライス',
            'ingredients' => [
                [
                    'name' => '玉ねぎ',
                    'unit' => [
                        'id' => $unitWithoutQuantity->id,
                        'name' => '適量',
                        'position' => 'suffix',
                        'requiresQuantity' => false,
                        'order' => 1,
                    ],
                    'quantity' => 2.5,
                    'categoryId' => $this->ingredientCategory->id
                ]
            ]
        ]
    ]);
});

test('3-8-96: 【更新】 requires_quantity=false の食材単位で数量省略', function () {
    // requires_quantity=false の食材単位を作成
    $unitWithoutQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '適量',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false
    ]);

    // 料理を作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithoutQuantity->id,
            'categoryId' => $this->ingredientCategory->id
            // quantity を省略
        ]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'name' => 'カレーライス',
            'ingredients' => [
                [
                    'name' => '玉ねぎ',
                    'unit' => [
                        'id' => $unitWithoutQuantity->id,
                        'name' => '適量',
                        'position' => 'suffix',
                        'requiresQuantity' => false,
                        'order' => 1,
                    ],
                    'quantity' => null,
                    'categoryId' => $this->ingredientCategory->id
                ]
            ]
        ]
    ]);
});

test('3-8-97: 【更新】 すべての項目を含む料理更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4
    ]);
    $recipeId = $createResponse->json('data.id');

    // 追加の画像を作成（手順用）
    $stepImage = Image::create([
        'src' => "/storage/images/{$this->group->id}/step.jpg",
        'width' => 800,
        'height' => 600
    ]);

    // 追加の料理カテゴリを作成
    $recipeCategory2 = RecipeCategory::create([
        'group_id' => $this->group->id,
        'name' => 'テスト料理カテゴリ2',
        'order' => 1
    ]);

    $data = [
        'name' => 'スパイスカレー',
        'servingCount' => 6,
        'url' => 'https://example.com/updated-recipe',
        'memo' => '更新されたメモです',
        'thumbnailId' => $this->image->id,
        'categoryIds' => [$this->recipeCategory->id, $recipeCategory2->id],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 300,
                'order' => 0
            ],
            [
                'name' => 'にんじん',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 150,
                'order' => 1
            ]
        ],
        'steps' => [
            [
                'instruction' => '野菜を切る',
                'imageId' => $stepImage->id,
                'order' => 0
            ],
            [
                'instruction' => '野菜を炒める',
                'order' => 1
            ],
            [
                'instruction' => 'カレー粉を加える',
                'order' => 2
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(スパイスカレー)を更新しました。'
    ]);

    // データベースにすべての項目が正しく更新されていることを確認
    $this->assertDatabaseHas('recipes', [
        'id' => $recipeId,
        'name' => 'スパイスカレー',
        'serving_count' => 6,
        'url' => 'https://example.com/updated-recipe',
        'memo' => '更新されたメモです'
    ]);

    // サムネイルが紐づけられていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);
    expect($recipe->thumbnails[0]->id)->toBe($this->image->id);

    // カテゴリが正しく更新されていることを確認
    $recipe = Recipe::with('categories')->find($recipeId);
    expect($recipe->categories)->toHaveCount(2);
    $categoryIds = $recipe->categories->pluck('id')->toArray();
    expect($categoryIds)->toContain($this->recipeCategory->id);
    expect($categoryIds)->toContain($recipeCategory2->id);

    // 食材が正しく更新されていることを確認
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(2);

    // 食材IDを取得
    $onionIngredient = $recipe->ingredients->firstWhere('name', '玉ねぎ');
    $carrotIngredient = $recipe->ingredients->firstWhere('name', 'にんじん');

    expect($onionIngredient)->not->toBeNull();
    expect($onionIngredient->name)->toBe('玉ねぎ');
    expect($onionIngredient->pivot->quantity)->toBe(300.0);
    expect($onionIngredient->pivot->order)->toBe(0);

    expect($carrotIngredient)->not->toBeNull();
    expect($carrotIngredient->name)->toBe('にんじん');
    expect($carrotIngredient->pivot->quantity)->toBe(150.0);
    expect($carrotIngredient->pivot->order)->toBe(1);

    // 手順が正しく更新されていることを確認
    $recipe = Recipe::with('steps.images')->find($recipeId);
    expect($recipe->steps)->toHaveCount(3);
    expect($recipe->steps[0]->instruction)->toBe('野菜を切る');
    expect($recipe->steps[0]->order)->toBe(0);
    expect($recipe->steps[0]->images->first()?->id)->toBe($stepImage->id);
    expect($recipe->steps[1]->instruction)->toBe('野菜を炒める');
    expect($recipe->steps[1]->order)->toBe(1);
    expect($recipe->steps[1]->images->first())->toBeNull();
    expect($recipe->steps[2]->instruction)->toBe('カレー粉を加える');
    expect($recipe->steps[2]->order)->toBe(2);
    expect($recipe->steps[2]->images->first())->toBeNull();

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-98: 【更新】 バリデーションエラー（requires_quantity=true の単位で数量省略）', function () {
    // requires_quantity=true の食材単位を作成
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true
    ]);

    // 料理を作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            'categoryId' => $this->ingredientCategory->id
            // quantity を省略
        ]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.0.quantity']);
});

// Update validation tests
test('3-8-99: 【更新】 バリデーションエラー（name 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('nameは必ず指定してください。', $responseData['errors']['name']);
});

test('3-8-100: 【更新】 バリデーションエラー（name が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 123];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('nameは文字列を指定してください。', $responseData['errors']['name']);
});

test('3-8-101: 【更新】 バリデーションエラー（name が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => str_repeat('あ', 256)];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('nameは、255文字以内で指定してください。', $responseData['errors']['name']);
});

test('3-8-102: 【更新】 バリデーションエラー（url が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'url' => 123];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);
});

test('3-8-103: 【更新】 バリデーションエラー（url が 2048 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'url' => str_repeat('a', 2049)];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);
});

test('3-8-104: 【更新】 バリデーションエラー（thumbnailId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'thumbnailId' => 'invalid-uuid'];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['thumbnailId']);
});

test('3-8-105: 【更新】 バリデーションエラー（categoryIds.* が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'categoryIds' => ['invalid-uuid']];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryIds.0']);
});

test('3-8-106: 【更新】 バリデーションエラー（categoryIds が配列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'categoryIds' => 'not-an-array'];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryIds']);
});

test('3-8-107: 【更新】 バリデーションエラー（ingredients.*.id が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['id' => 'invalid-uuid', 'name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.id']);
});

test('3-8-108: 【更新】 バリデーションエラー（ingredients が配列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'ingredients' => 'not-an-array'];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients']);
});

test('3-8-109: 【更新】 バリデーションエラー（ingredients.*.name 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

test('3-8-110: 【更新】 バリデーションエラー（ingredients.*.name が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => 123, 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

test('3-8-111: 【更新】 バリデーションエラー（ingredients.*.name が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => str_repeat('あ', 256), 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

test('3-8-112: 【更新】 バリデーションエラー（ingredients.*.unitId 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'categoryId' => $this->ingredientCategory->id]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.unitId']);
});

test('3-8-113: 【更新】 バリデーションエラー（ingredients.*.unitId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => 'invalid-uuid', 'categoryId' => $this->ingredientCategory->id]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.unitId']);
});

test('3-8-114: 【更新】 バリデーションエラー（ingredients.*.categoryId 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryId']);
});

test('3-8-115: 【更新】 バリデーションエラー（ingredients.*.categoryId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => 'invalid-uuid']]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryId']);
});

test('3-8-116: 【更新】 バリデーションエラー（ingredients.*.quantity が数値でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'quantity' => 'not-a-number']]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantity']);
});

test('3-8-117: 【更新】 バリデーションエラー（ingredients.*.order が整数でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'order' => 'not-an-integer']]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.order']);
});

test('3-8-118: 【更新】 バリデーションエラー（ingredients.*.order が負の値）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'order' => -1]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.order']);
});

test('3-8-119: 【更新】 バリデーションエラー（steps が配列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'steps' => 'not-an-array'];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps']);
});

test('3-8-120: 【更新】 バリデーションエラー（steps.*.id が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'steps' => [['id' => 'invalid-uuid', 'instruction' => '切る', 'order' => 0]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.id']);
});

test('3-8-121: 【更新】 バリデーションエラー（steps.*.instruction 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'steps' => [['order' => 0]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.instruction']);
});

test('3-8-122: 【更新】 バリデーションエラー（steps.*.instruction が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => 123, 'order' => 0]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.instruction']);
});

test('3-8-123: 【更新】 バリデーションエラー（steps.*.instruction が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => str_repeat('あ', 256), 'order' => 0]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.instruction']);
});

test('3-8-124: 【更新】 バリデーションエラー（steps.*.imageId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => '切る', 'imageId' => 'invalid-uuid', 'order' => 0]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.imageId']);
});

test('3-8-125: 【更新】 バリデーションエラー（steps.*.order 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => '切る']]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.order']);
});

test('3-8-126: 【更新】 バリデーションエラー（steps.*.order が整数でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => '切る', 'order' => 'not-an-integer']]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.order']);
});

test('3-8-127: 【更新】 バリデーションエラー（steps.*.order が負の値）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => '切る', 'order' => -1]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.order']);
});

test('3-8-128: 【更新】 バリデーションエラー（memo が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'memo' => 123];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['memo']);
});

test('3-8-129: 【更新】 バリデーションエラー（memo が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'memo' => str_repeat('あ', 256)];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['memo']);
});

test('3-8-130: 【更新】 serving_count が null でも正常に更新できる', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'servingCount' => null];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $responseData = $response->json();
    $this->assertNull($responseData['data']['servingCount']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-131: 【更新】 バリデーションエラー（serving_count が整数でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'servingCount' => 'abc'];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['servingCount']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'servingCount'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-132: 【更新】 バリデーションエラー（serving_count が 1 未満）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = ['name' => 'カレーライス', 'servingCount' => 0];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['servingCount']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'servingCount'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-133: 【更新】 存在しない食材単位 ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => '00000000-0000-0000-0000-000000000000',
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 100
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-8-134: 【更新】 他グループの食材単位 ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherUnit = IngredientUnit::create(['group_id' => $otherGroup->id, 'name' => '他', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 0]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $otherUnit->id, 'categoryId' => $this->ingredientCategory->id, 'quantity' => 100]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-8-135: 【更新】 存在しない食材カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => '00000000-0000-0000-0000-000000000000', 'quantity' => 100]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-8-136: 【更新】 他グループの食材カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherCategory = IngredientCategory::create(['group_id' => $otherGroup->id, 'name' => '他', 'order' => 0]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $otherCategory->id, 'quantity' => 100]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-8-137: 【更新】 存在しない料理カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => ['00000000-0000-0000-0000-000000000000']
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-8-138: 【更新】 他グループの料理カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherCategory = RecipeCategory::create(['group_id' => $otherGroup->id, 'name' => '他', 'order' => 0]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => [$otherCategory->id]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-8-139: 【更新】 存在しない画像 ID 指定（thumbnailId）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => '00000000-0000-0000-0000-000000000000'
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-8-140: 【更新】 他グループの画像 ID 指定（thumbnailId）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherImage = Image::create([
        'src' => "/storage/images/{$otherGroup->id}/other.jpg",
        'width' => 800,
        'height' => 600
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $otherImage->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-8-141: 【更新】 存在しない画像 ID 指定（steps.*.imageId）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [['instruction' => '切る', 'imageId' => '00000000-0000-0000-0000-000000000000', 'order' => 0]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-8-142: 【更新】 他グループの画像 ID 指定（steps.*.imageId）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherImage = Image::create([
        'src' => "/storage/images/{$otherGroup->id}/other.jpg",
        'width' => 800,
        'height' => 600
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [['instruction' => '切る', 'imageId' => $otherImage->id, 'order' => 0]]
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-8-143: 【更新】 存在しない料理更新', function () {
    $data = ['name' => 'カレーライス', 'servingCount' => 4];

    $response = $this->actingAs($this->user)->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(404);
    $response->assertJson(['success' => false]);
});

test('3-8-144: 【更新】 他グループの料理更新', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherRecipe = Recipe::create(['group_id' => $otherGroup->id, 'owner_user_id' => $otherUser->id, 'name' => '他の料理']);

    $data = ['name' => 'カレーライス', 'servingCount' => 4];

    $response = $this->actingAs($this->user)->put("/recipes/{$otherRecipe->id}", $data);

    $response->assertStatus(404);
});

test('3-8-145: 【更新】 同一グループの他ユーザーの料理更新', function () {
    // 同一グループの別のユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $this->group->users()->attach($otherUser->id);

    // そのユーザーが作成したレシピ
    $otherRecipe = Recipe::create([
        'group_id' => $this->group->id,
        'owner_user_id' => $otherUser->id,
        'name' => '他人のレシピ',
        'serving_count' => 2
    ]);

    $data = ['name' => '勝手に更新', 'servingCount' => 4];

    // 別のユーザー（自分）で更新を試みる
    $response = $this->actingAs($this->user)->put("/recipes/{$otherRecipe->id}", $data);

    $response->assertStatus(403);
});

test('3-8-146: 【更新】 未認証ユーザー', function () {
    $data = ['name' => 'カレーライス', 'servingCount' => 4];

    $response = $this->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(401);
    $response->assertJson(['success' => false, 'message' => '認証が必要です。']);
});

test('3-8-147: 【更新】 グループが存在しない', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $data = ['name' => 'カレーライス', 'servingCount' => 4];

    $response = $this->actingAs($user)->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false, 'message' => 'ユーザーはグループに所属していません。']);
});

test('3-8-148: 【更新】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('update')->once()->andThrow(new \Exception('Database connection failed'));
    });

    $data = ['name' => 'カレーライス', 'servingCount' => 4];

    $response = $this->actingAs($this->user)->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);
});

// ===== destroy() メソッドのテストケース =====

test('3-8-149: 【削除】 正常な料理削除', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->delete("/recipes/{$recipeId}");

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    // データベースから削除されていることを確認
    $this->assertDatabaseMissing('recipes', ['id' => $recipeId]);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-150: 【削除】 削除成功メッセージの確認', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->delete("/recipes/{$recipeId}");

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toContain('カレーライス');
});

test('3-8-151: 【削除】 存在しない料理削除', function () {
    $response = $this->actingAs($this->user)->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404);
    $response->assertJson(['success' => false]);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-152: 【削除】 他グループの料理削除', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherRecipe = Recipe::create(['group_id' => $otherGroup->id, 'owner_user_id' => $otherUser->id, 'name' => '他の料理']);

    $response = $this->actingAs($this->user)->delete("/recipes/{$otherRecipe->id}");

    $response->assertStatus(404);
    $response->assertJson(['success' => false]);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-153: 【削除】 同一グループの他ユーザーの料理削除', function () {
    // 同一グループの別のユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $this->group->users()->attach($otherUser->id);

    // そのユーザーが作成したレシピ
    $otherRecipe = Recipe::create([
        'group_id' => $this->group->id,
        'owner_user_id' => $otherUser->id,
        'name' => '他人のレシピ',
        'serving_count' => 2
    ]);

    // 別のユーザー（自分）で削除を試みる
    $response = $this->actingAs($this->user)->delete("/recipes/{$otherRecipe->id}");

    $response->assertStatus(403);
});

test('3-8-154: 【削除】 未認証ユーザー', function () {
    $response = $this->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(401);
    $response->assertJson(['success' => false, 'message' => '認証が必要です。']);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-155: 【削除】 グループが存在しない', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(422);
    $response->assertJson(['success' => false, 'message' => 'ユーザーはグループに所属していません。']);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-8-156: 【削除】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('delete')->once()->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);
});
