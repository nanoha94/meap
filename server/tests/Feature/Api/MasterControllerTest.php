<?php

use App\Models\User;
use App\Models\Group;
use App\Models\RecipeCategory;
use App\Models\IngredientCategory;
use App\Models\IngredientUnit;
use App\Models\MenuCategory;
use App\Models\ShoppingCategory;
use App\Models\ShoppingTag;
use App\Models\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト用のデータを準備するクラスを作成
    $this->testData = new class {
        public $defaultGroup;
        public $user;

        public function createDefaultGroup()
        {
            $this->defaultGroup = Group::create([
                'group_size' => 1
            ]);

            return $this->defaultGroup;
        }

        public function createIngredientUnits($groupId)
        {
            $units = [
                [
                    'name' => 'グラム',
                    'position' => 'suffix',
                    'order' => 0,
                    'requires_quantity' => true,
                    'group_id' => $groupId
                ],
                [
                    'name' => '個',
                    'position' => 'suffix',
                    'order' => 1,
                    'requires_quantity' => true,
                    'group_id' => $groupId
                ],
                [
                    'name' => '少々',
                    'position' => 'prefix',
                    'order' => 2,
                    'requires_quantity' => false,
                    'group_id' => $groupId
                ]
            ];

            foreach ($units as $unit) {
                $unit = new IngredientUnit($unit);
                $unit->group_id = $groupId;
                $unit->save();
            }
        }

        public function createUser()
        {
            $this->user = User::factory()->create([
                'email_verified_at' => now()
            ]);

            return $this->user;
        }

        public function createUserWithGroup()
        {
            // Colorマスターデータをシード
            $colors = [
                ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
                ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
                ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
            ];

            foreach ($colors as $color) {
                Color::create($color);
            }

            $this->createDefaultGroup();
            $this->createUser();
            $this->defaultGroup->users()->attach($this->user->id);
            $this->createIngredientUnits($this->defaultGroup->id);

            return $this->user;
        }

        public function createRecipeCategories($groupId, $categories)
        {
            foreach ($categories as $category) {
                RecipeCategory::create([
                    'group_id' => $groupId,
                    'name' => $category['name'],
                    'order' => $category['order']
                ]);
            }
        }

        public function createIngredientCategories($groupId, $categories)
        {
            foreach ($categories as $category) {
                IngredientCategory::create([
                    'group_id' => $groupId,
                    'name' => $category['name'],
                    'order' => $category['order']
                ]);
            }
        }

        public function createMenuCategories($groupId, $types)
        {
            foreach ($types as $type) {
                MenuCategory::create([
                    'group_id' => $groupId,
                    'name' => $type['name'],
                    'order' => $type['order']
                ]);
            }
        }

        public function createShoppingCategories($groupId, $categories)
        {
            foreach ($categories as $category) {
                ShoppingCategory::create([
                    'group_id' => $groupId,
                    'name' => $category['name'],
                    'order' => $category['order'],
                    'is_default' => $category['is_default'] ?? false
                ]);
            }
        }

        public function createShoppingTags($groupId, $tags)
        {
            foreach ($tags as $tag) {
                ShoppingTag::create([
                    'group_id' => $groupId,
                    'name' => $tag['name']
                ]);
            }
        }
    };
});

test('3-4-1: 正常なマスターデータ取得', function () {
    $user = $this->testData->createUserWithGroup();

    // テスト用のマスターデータをAPIで作成
    $recipeCategoryResponse = $this->actingAs($user)->post('/recipe-categories', [
        'name' => '和食',
        'order' => 0
    ]);
    $recipeCategoryId = $recipeCategoryResponse->json('data.id');

    $ingredientCategoryResponse = $this->actingAs($user)->post('/ingredient-categories', [
        'name' => '野菜',
        'order' => 0
    ]);
    $ingredientCategoryId = $ingredientCategoryResponse->json('data.id');

    // MenuCategory とShoppingTag はAPIがないので直接作成
    $menuCategory = MenuCategory::create([
        'group_id' => $this->testData->defaultGroup->id,
        'name' => '主菜',
        'order' => 0
    ]);

    // 買い物カテゴリをAPIで作成（is_defaultは常にfalseになる）
    $shoppingCategoryResponse = $this->actingAs($user)->post('/shopping-categories', [
        'name' => '食材',
        'order' => 0
    ]);
    $shoppingCategoryId = $shoppingCategoryResponse->json('data.id');

    $shoppingTag = ShoppingTag::create([
        'group_id' => $this->testData->defaultGroup->id,
        'name' => '必須'
    ]);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'マスターデータを取得しました。',
        'data' => [
            'recipeCategories' => [
                [
                    'id' => $recipeCategoryId,
                    'name' => '和食',
                    'order' => 0
                ]
            ],
            'ingredientCategories' => [
                [
                    'id' => $ingredientCategoryId,
                    'name' => '野菜',
                    'order' => 0
                ]
            ],
            'ingredientUnits' => [
                [
                    'name' => 'グラム',
                    'position' => 'suffix',
                    'order' => 0,
                    'requires_quantity' => true
                ],
                [
                    'name' => '個',
                    'position' => 'suffix',
                    'order' => 1,
                    'requires_quantity' => true
                ],
                [
                    'name' => '少々',
                    'position' => 'prefix',
                    'order' => 2,
                    'requires_quantity' => false
                ]
            ],
            'menuCategories' => [
                [
                    'id' => $menuCategory->id,
                    'name' => '主菜',
                    'order' => 0
                ]
            ],
            'shoppingCategories' => [
                [
                    'id' => $shoppingCategoryId,
                    'name' => '食材',
                    'order' => 0,
                    'is_default' => false // APIで作成時はfalseになる
                ]
            ],
            'shoppingTags' => [
                [
                    'id' => $shoppingTag->id,
                    'name' => '必須'
                ]
            ]
        ]
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'recipeCategories' => [
                '*' => ['id', 'name', 'order']
            ],
            'ingredientCategories' => [
                '*' => ['id', 'name', 'order']
            ],
            'ingredientUnits' => [
                '*' => ['id', 'name', 'position', 'order', 'requires_quantity']
            ],
            'menuCategories' => [
                '*' => ['id', 'name', 'order']
            ],
            'shoppingCategories' => [
                '*' => ['id', 'name', 'order', 'is_default']
            ],
            'shoppingTags' => [
                '*' => ['id', 'name']
            ]
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-4-2: レシピカテゴリデータ取得確認', function () {
    $user = $this->testData->createUserWithGroup();

    // レシピカテゴリをAPIで作成
    $this->actingAs($user)->post('/recipe-categories', ['name' => '和食', 'order' => 0]);
    $this->actingAs($user)->post('/recipe-categories', ['name' => '洋食', 'order' => 1]);
    $this->actingAs($user)->post('/recipe-categories', ['name' => '中華', 'order' => 2]);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $responseData = $response->json('data.recipeCategories');

    expect($responseData)->toHaveCount(3);
    expect($responseData[0]['name'])->toBe('和食');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('洋食');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('中華');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-4-3: 食材カテゴリデータ取得確認', function () {
    $user = $this->testData->createUserWithGroup();

    // 食材カテゴリをAPIで作成
    $this->actingAs($user)->post('/ingredient-categories', ['name' => '野菜', 'order' => 0]);
    $this->actingAs($user)->post('/ingredient-categories', ['name' => '肉類', 'order' => 1]);
    $this->actingAs($user)->post('/ingredient-categories', ['name' => '魚介類', 'order' => 2]);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $responseData = $response->json('data.ingredientCategories');

    expect($responseData)->toHaveCount(3);
    expect($responseData[0]['name'])->toBe('野菜');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('肉類');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('魚介類');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-4-4: 食材単位データ取得確認', function () {
    $user = $this->testData->createUserWithGroup();

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $responseData = $response->json('data.ingredientUnits');

    expect($responseData)->toHaveCount(3);
    expect($responseData[0]['name'])->toBe('グラム');
    expect($responseData[0]['position'])->toBe('suffix');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[0]['requires_quantity'])->toBeTrue();

    expect($responseData[1]['name'])->toBe('個');
    expect($responseData[1]['position'])->toBe('suffix');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[1]['requires_quantity'])->toBeTrue();

    expect($responseData[2]['name'])->toBe('少々');
    expect($responseData[2]['position'])->toBe('prefix');
    expect($responseData[2]['order'])->toBe(2);
    expect($responseData[2]['requires_quantity'])->toBeFalse();
});

test('3-4-5: コース種別データ取得確認', function () {
    $user = $this->testData->createUserWithGroup();

    $types = [
        ['name' => '主菜', 'order' => 0],
        ['name' => '副菜', 'order' => 1],
        ['name' => 'デザート', 'order' => 2]
    ];

    $this->testData->createMenuCategories($this->testData->defaultGroup->id, $types);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $responseData = $response->json('data.menuCategories');

    expect($responseData)->toHaveCount(3);
    expect($responseData[0]['name'])->toBe('主菜');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('副菜');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('デザート');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-4-6: 買い物カテゴリデータ取得確認', function () {
    $user = $this->testData->createUserWithGroup();

    // 買い物カテゴリをAPIで作成（is_defaultは常にfalseになる）
    $this->actingAs($user)->post('/shopping-categories', ['name' => '食材', 'order' => 0]);
    $this->actingAs($user)->post('/shopping-categories', ['name' => '日用品', 'order' => 1]);
    $this->actingAs($user)->post('/shopping-categories', ['name' => '調味料', 'order' => 2]);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $responseData = $response->json('data.shoppingCategories');

    expect($responseData)->toHaveCount(3);
    expect($responseData[0]['name'])->toBe('食材');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[0]['is_default'])->toBeFalse(); // APIで作成時は常にfalse
    expect($responseData[1]['name'])->toBe('日用品');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[1]['is_default'])->toBeFalse();
    expect($responseData[2]['name'])->toBe('調味料');
    expect($responseData[2]['order'])->toBe(2);
    expect($responseData[2]['is_default'])->toBeFalse();
});

test('3-4-7: 買い物タグデータ取得確認', function () {
    $user = $this->testData->createUserWithGroup();

    $tags = [
        ['name' => '必須'],
        ['name' => 'セール'],
        ['name' => 'ストック']
    ];

    $this->testData->createShoppingTags($this->testData->defaultGroup->id, $tags);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $responseData = $response->json('data.shoppingTags');

    expect($responseData)->toHaveCount(3);
    expect($responseData[0]['name'])->toBe('必須');
    expect($responseData[1]['name'])->toBe('セール');
    expect($responseData[2]['name'])->toBe('ストック');
});

test('3-4-8: データの並び順確認', function () {
    $user = $this->testData->createUserWithGroup();

    // レシピカテゴリをAPIで作成（異なるorder順）
    $this->actingAs($user)->post('/recipe-categories', ['name' => '和食', 'order' => 2]);
    $this->actingAs($user)->post('/recipe-categories', ['name' => '洋食', 'order' => 0]);
    $this->actingAs($user)->post('/recipe-categories', ['name' => '中華', 'order' => 1]);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $responseData = $response->json('data.recipeCategories');

    expect($responseData[0]['name'])->toBe('洋食');
    expect($responseData[0]['order'])->toBe(0);
    expect($responseData[1]['name'])->toBe('中華');
    expect($responseData[1]['order'])->toBe(1);
    expect($responseData[2]['name'])->toBe('和食');
    expect($responseData[2]['order'])->toBe(2);
});

test('3-4-9: 買い物カテゴリのフォーマット確認', function () {
    $user = $this->testData->createUserWithGroup();

    // 買い物カテゴリをAPIで作成（is_defaultは常にfalseになる）
    $this->actingAs($user)->post('/shopping-categories', ['name' => '食材', 'order' => 0]);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $responseData = $response->json('data.shoppingCategories.0');

    expect($responseData['is_default'])->toBe(false); // APIで作成時は常にfalse
    expect(is_bool($responseData['is_default']))->toBeTrue();
});

test('3-4-10: レスポンス構造確認', function () {
    $user = $this->testData->createUserWithGroup();

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'recipeCategories',
            'ingredientCategories',
            'ingredientUnits',
            'menuCategories',
            'shoppingCategories',
            'shoppingTags'
        ]
    ]);
});

test('3-4-11: 空のマスターデータ', function () {
    $user = $this->testData->createUserWithGroup();

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'マスターデータを取得しました。',
        'data' => [
            'recipeCategories' => [],
            'ingredientCategories' => [],
            'ingredientUnits' => [
                [
                    'name' => 'グラム',
                    'position' => 'suffix',
                    'order' => 0,
                    'requires_quantity' => true
                ],
                [
                    'name' => '個',
                    'position' => 'suffix',
                    'order' => 1,
                    'requires_quantity' => true
                ],
                [
                    'name' => '少々',
                    'position' => 'prefix',
                    'order' => 2,
                    'requires_quantity' => false
                ]
            ],
            'menuCategories' => [],
            'shoppingCategories' => [],
            'shoppingTags' => []
        ]
    ]);
});

test('3-4-12: 未認証ユーザー', function () {
    $response = $this->get('/master');

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);
});

test('3-4-13: グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。'
    ]);
});

test('3-4-14: データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();

    // MasterServiceをモックして例外を発生させる（MealCategoryControllerTestの3-6-8と同じパターン）
    $this->mock(\App\Services\MasterService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Database connection error'));
    });

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'マスターデータの取得に失敗しました。'
    ]);
});

test('3-4-15: レシピカテゴリ取得失敗', function () {
    $user = $this->testData->createUserWithGroup();

    // MasterServiceをモックして、レシピカテゴリ取得時にエラーを発生させる
    $this->mock(\App\Services\MasterService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Recipe category query error'));
    });

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'マスターデータの取得に失敗しました。'
    ]);
});

test('3-4-16: 食材カテゴリ取得失敗', function () {
    $user = $this->testData->createUserWithGroup();

    // MasterServiceをモックして、食材カテゴリ取得時にエラーを発生させる
    $this->mock(\App\Services\MasterService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Ingredient category query error'));
    });

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'マスターデータの取得に失敗しました。'
    ]);
});

test('3-4-17: 食材単位取得失敗', function () {
    $user = $this->testData->createUserWithGroup();

    // MasterServiceをモックして、食材単位取得時にエラーを発生させる
    $this->mock(\App\Services\MasterService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Ingredient unit query error'));
    });

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'マスターデータの取得に失敗しました。'
    ]);
});

test('3-4-18: コース種別取得失敗', function () {
    $user = $this->testData->createUserWithGroup();

    // MasterServiceをモックして、コース種別取得時にエラーを発生させる
    $this->mock(\App\Services\MasterService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Menu type query error'));
    });

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'マスターデータの取得に失敗しました。'
    ]);
});

test('3-4-19: 買い物カテゴリ取得失敗', function () {
    $user = $this->testData->createUserWithGroup();

    // MasterServiceをモックして、買い物カテゴリ取得時にエラーを発生させる
    $this->mock(\App\Services\MasterService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Shopping category query error'));
    });

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'マスターデータの取得に失敗しました。'
    ]);
});

test('3-4-20: 買い物タグ取得失敗', function () {
    $user = $this->testData->createUserWithGroup();

    // MasterServiceをモックして、買い物タグ取得時にエラーを発生させる
    $this->mock(\App\Services\MasterService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Shopping tag query error'));
    });

    $response = $this->actingAs($user)->get('/master');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'マスターデータの取得に失敗しました。'
    ]);
});
