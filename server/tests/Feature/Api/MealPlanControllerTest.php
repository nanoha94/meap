<?php

use App\Models\User;
use App\Models\Group;
use App\Models\MealPlan;
use App\Models\MenuCategory;
use App\Models\IngredientUnit;
use App\Models\Color;
use App\Services\MealPlanService;
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

    // テスト用のデータを準備するクラスを作成
    $this->testData = new class {
        public $defaultGroup;
        public $user;
        public $mealCategory;
        public $recipe;
        public $menuCategory;

        public function createDefaultGroup()
        {
            $this->defaultGroup = Group::create([
                'group_size' => 1
            ]);

            return $this->defaultGroup;
        }

        public function createUser()
        {
            $this->user = User::factory()->create([
                'email_verified_at' => now()
            ]);

            return $this->user;
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

        public function createUserWithGroup()
        {
            $this->createDefaultGroup();
            $this->createUser();
            $this->defaultGroup->users()->attach($this->user->id);
            $this->createIngredientUnits($this->defaultGroup->id);

            return $this->user;
        }

        public function createmealCategoryViaApi($testInstance, $user, $name = '朝食', $colorId = null, $order = 0)
        {
            if (!$colorId) {
                $colorId = Color::first()->id;
            }

            $requestData = [
                'name' => $name,
                'colorId' => $colorId,
                'order' => $order
            ];

            $response = $testInstance->actingAs($user)->post('/meal-categories', $requestData);

            if ($response->status() === 201) {
                return $response->json('data');
            }

            throw new \Exception('Failed to create meal type via API: ' . $response->json('message'));
        }

        public function createRecipeViaApi($testInstance, $user, $name = '人参の煮物', $url = null, $memo = '美味しい人参の煮物です', $thumbnailId = null, $categoryIds = [])
        {
            $requestData = [
                'name' => $name,
                'url' => $url,
                'memo' => $memo,
                'thumbnailId' => $thumbnailId,
                'categoryIds' => $categoryIds
            ];

            $response = $testInstance->actingAs($user)->post('/recipes', $requestData);

            if ($response->status() === 201) {
                return $response->json('data');
            }

            throw new \Exception('Failed to create recipe via API: ' . $response->json('message') . ' ' . $response->status());
        }

        public function createMenuCategoryViaApi($user, $name = '主菜', $order = 0)
        {
            // MenuCategoryはMasterControllerで管理されているため、直接作成
            // defaultGroupが設定されている場合はそれを使用、そうでない場合はユーザーから取得
            $groupId = null;
            if (isset($this->defaultGroup)) {
                $groupId = $this->defaultGroup->id;
            } else {
                // ユーザーのグループを取得（リレーションがロードされていない場合はリフレッシュ）
                $user->refresh();
                $group = $user->groups()->first();
                if (!$group) {
                    throw new \Exception('User does not have a group assigned');
                }
                $groupId = $group->id;
            }

            $menuCategory = MenuCategory::create([
                'group_id' => $groupId,
                'name' => $name,
                'order' => $order
            ]);

            return [
                'id' => $menuCategory->id,
                'name' => $menuCategory->name,
                'order' => $menuCategory->order
            ];
        }

        public function createMealPlanViaApi($testInstance, $user, $mealCategoryId, $recipeId, $menuCategoryId, $date = '2024-01-15')
        {
            $requestData = [
                'date' => $date,
                'mealCategoryId' => $mealCategoryId,
                'menu' => [
                    [
                        'recipeIds' => [$recipeId],
                        'categoryId' => $menuCategoryId
                    ]
                ]
            ];

            $response = $testInstance->actingAs($user)->post('/meal-plans', $requestData);

            if ($response->status() === 201) {
                return $response->json('data');
            }

            throw new \Exception('Failed to create meal plan via API: ' . $response->json('message'));
        }

        public function createMealPlan($groupId, $mealCategoryId, $date = '2024-01-15')
        {
            $mealPlan = MealPlan::create([
                'group_id' => $groupId,
                'meal_category_id' => $mealCategoryId,
                'date' => $date
            ]);

            return $mealPlan;
        }
    };
});

// ==================== index() テストケース ====================

test('3-6-1: 【一覧取得】 正常な献立一覧取得', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    // エンドポイントを使用して献立を作成
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], $menuCategory['id']);

    $response = $this->actingAs($user)->get('/meal-plans');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立を1件取得しました。',
        'total' => 1
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'date',
                'mealPlans' => [
                    '*' => [
                        'id',
                        'date',
                        'category' => [
                            'id',
                            'name',
                            'colorId'
                        ],
                        'menu' => [
                            '*' => [
                                'category' => [
                                    'id',
                                    'name'
                                ],
                                'recipes' => [
                                    '*' => [
                                        'id',
                                        'name',
                                        'thumbnail',
                                        'url',
                                        'memo',
                                        'categories',
                                        'ingredients',
                                        'steps'
                                    ]
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        ],
        'total'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-2: 【一覧取得】 献立データの日付別グループ化確認', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    // エンドポイントを使用して異なる日付の献立を作成
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], $menuCategory['id'], '2024-01-15');
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], $menuCategory['id'], '2024-01-16');
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], $menuCategory['id'], '2024-01-15');

    $response = $this->actingAs($user)->get('/meal-plans');

    $response->assertStatus(200);
    $responseData = $response->json('data');

    // 日付別にグループ化されていることを確認
    expect($responseData)->toHaveCount(2); // 2つの日付グループ

    $dates = collect($responseData)->pluck('date')->toArray();
    expect($dates)->toContain('2024-01-15');
    expect($dates)->toContain('2024-01-16');

    // 2024-01-15には2つの献立があることを確認
    $date20240115 = collect($responseData)->firstWhere('date', '2024-01-15');
    expect($date20240115['mealPlans'])->toHaveCount(2);
});


test('3-6-3: 【一覧取得】 レスポンス形式確認', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    // エンドポイントを使用して献立を作成
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], $menuCategory['id']);

    $response = $this->actingAs($user)->get('/meal-plans');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data',
        'total'
    ]);

    // 正しいJSON形式でレスポンスが返されることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-4: 【一覧取得】 未認証ユーザー', function () {
    $response = $this->get('/meal-plans');

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

test('3-6-5: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/meal-plans');

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

test('3-6-6: 【一覧取得】 データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();
    // MealPlanServiceをモックして例外を発生させる
    $this->mock(MealPlanService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()->andThrow(new \Exception('Database connection error'));
    });

    $response = $this->actingAs($user)->get('/meal-plans');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立の取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-7: 【一覧取得】 MealPlanService 例外', function () {
    $user = $this->testData->createUserWithGroup();

    // MealPlanServiceをモックして例外を発生させる
    $this->mock(MealPlanService::class, function ($mock) {
        $mock->shouldReceive('index')
            ->once()->andThrow(new \Exception('MealPlanService error'));
    });

    $response = $this->actingAs($user)->get('/meal-plans');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立の取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ==================== store() テストケース ====================

test('3-6-8: 【新規作成】 正常な献立作成', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    // dd($response->json());

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '献立(2024-01-15)を作成しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'date',
            'category' => [
                'id',
                'name',
                'colorId'
            ],
            'menu' => [
                '*' => [
                    'category' => [
                        'id',
                        'name'
                    ],
                    'recipes' => [
                        '*' => [
                            'id',
                            'name',
                            'thumbnail',
                            'url',
                            'memo',
                            'categories',
                            'ingredients',
                            'steps'
                        ]
                    ]
                ]
            ]
        ]
    ]);

    // データベースに献立が保存されていることを確認
    $this->assertDatabaseHas('meal_plans', [
        'group_id' => $this->testData->defaultGroup->id,
        'meal_category_id' => $mealCategory['id'],
        'date' => '2024-01-15'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-9: 【新規作成】 献立に料理を紐づけ', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(201);

    // 献立に料理が正しく紐づけられていることを確認
    $mealPlan = MealPlan::where('group_id', $this->testData->defaultGroup->id)
        ->where('date', '2024-01-15')
        ->first();

    expect($mealPlan)->not->toBeNull();
    expect($mealPlan->recipes)->toHaveCount(1);
    expect($mealPlan->recipes->first()->id)->toBe($recipe['id']);
});

test('3-6-10: 【新規作成】 未認証ユーザー', function () {
    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => \Illuminate\Support\Str::uuid(),
        'menu' => [
            [
                'recipeIds' => [\Illuminate\Support\Str::uuid()],
                'categoryId' => \Illuminate\Support\Str::uuid()
            ]
        ]
    ];

    $response = $this->post('/meal-plans', $requestData);

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

test('3-6-11: 【新規作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => \Illuminate\Support\Str::uuid(),
        'menu' => [
            [
                'recipeIds' => [\Illuminate\Support\Str::uuid()],
                'categoryId' => \Illuminate\Support\Str::uuid()
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

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

test('3-6-12: 【新規作成】 データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    // データベース接続をモックしてエラーを発生させる
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection error'));

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立の作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});


test('3-6-13: 【新規作成】 料理紐づけ失敗', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    // データベース接続をモックしてエラーを発生させる
    DB::shouldReceive('transaction')
        ->andThrow(new \Exception('Database transaction failed'));

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立の作成に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ==================== バリデーションテストケース ====================

test('3-6-14: 【新規作成】 バリデーションエラー（date 必須）', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        // date パラメータを未入力
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['date']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('dateは必ず指定してください。', $responseData['errors']['date']);
});

test('3-6-15: 【新規作成】 バリデーションエラー（date 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => 'invalid-date-format', // 無効な日付形式
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['date']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('dateはY-m-d形式で指定してください。', $responseData['errors']['date']);
});

test('3-6-16: 【新規作成】 バリデーションエラー（mealCategoryId 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-15',
        // mealCategoryId パラメータを未入力
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['mealCategoryId']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('mealCategoryIdは必ず指定してください。', $responseData['errors']['mealCategoryId']);
});

test('3-6-17: 【新規作成】 バリデーションエラー（mealCategoryId 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => 'invalid-uuid-format', // 無効なUUID形式
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['mealCategoryId']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('mealCategoryIdに有効なUUIDを指定してください。', $responseData['errors']['mealCategoryId']);
});

test('3-6-18: 【新規作成】 バリデーションエラー（mealCategoryId 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    // 有効なUUID形式だが存在しないmealCategoryIdを使用
    $nonExistentmealCategoryId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $nonExistentmealCategoryId, // 存在しないmealCategoryId
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立カテゴリが見つかりませんでした。'
    ]);
});

test('3-6-19: 【新規作成】 バリデーションエラー（menu 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        // menu パラメータを未入力
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menuは必ず指定してください。', $responseData['errors']['menu']);
});

test('3-6-20: 【新規作成】 バリデーションエラー（menu 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => 'not_an_array' // 配列でない
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menuは配列でなくてはなりません。', $responseData['errors']['menu']);
});

test('3-6-21: 【新規作成】 バリデーションエラー（menu 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [] // 空配列
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);

    // エラーメッセージに「メニューは1個以上指定してください。」が含まれているかチェック
    $responseData = $response->json();
    $this->assertContains('menuは1個以上指定してください。', $responseData['errors']['menu']);
});

test('3-6-22: 【新規作成】 バリデーションエラー（recipeIds 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                // recipeIds パラメータを未入力
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIdsは必ず指定してください。', $responseData['errors']['menu.0.recipeIds']);
});

test('3-6-23: 【新規作成】 バリデーションエラー（recipeIds 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => 'not_an_array', // 配列でない
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIdsは配列でなくてはなりません。', $responseData['errors']['menu.0.recipeIds']);
});

test('3-6-24: 【新規作成】 バリデーションエラー（recipeIds 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [], // 空配列
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIdsは1個以上指定してください。', $responseData['errors']['menu.0.recipeIds']);
});

test('3-6-25: 【新規作成】 バリデーションエラー（recipeIds 個別要素必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [null], // 個別要素が未入力
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds.0']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIds.*は必ず指定してください。', $responseData['errors']['menu.0.recipeIds.0']);
});

test('3-6-26: 【新規作成】 バリデーションエラー（recipeIds 個別要素形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => ['invalid-uuid-format'], // 無効なUUID形式
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds.0']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIds.*に有効なUUIDを指定してください。', $responseData['errors']['menu.0.recipeIds.0']);
});

test('3-6-27: 【新規作成】 バリデーションエラー（recipeIds 個別要素存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    // 有効なUUID形式だが存在しないrecipeIdを使用
    $nonExistentRecipeId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$nonExistentRecipeId], // 存在しないrecipeId
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された料理/レシピが見つかりませんでした。'
    ]);
});

test('3-6-28: 【新規作成】 バリデーションエラー（categoryId 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                // categoryId パラメータを未入力
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.categoryId']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.categoryIdは必ず指定してください。', $responseData['errors']['menu.0.categoryId']);
});

test('3-6-29: 【新規作成】 バリデーションエラー（categoryId 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => 'invalid-uuid-format' // 無効なUUID形式
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.categoryId']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.categoryIdに有効なUUIDを指定してください。', $responseData['errors']['menu.0.categoryId']);
});

test('3-6-30: 【新規作成】 バリデーションエラー（categoryId 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    // 有効なUUID形式だが存在しないcategoryIdを使用
    $nonExistentMenuCategoryId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'date' => '2024-01-15',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $nonExistentMenuCategoryId // 存在しないcategoryId
            ]
        ]
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定されたapi.attributes.menu_categoryが見つかりませんでした。'
    ]);
});

// ==================== show() テストケース ====================

test('3-6-31: 【詳細取得】 正常な献立詳細取得', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    // エンドポイントを使用して献立を作成
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], $menuCategory['id']);

    $response = $this->actingAs($user)->get("/meal-plans/{$mealPlanData['id']}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立を取得しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'date',
            'category' => [
                'id',
                'name',
                'colorId'
            ],
            'menu' => [
                [
                    'category' => [
                        'id',
                        'name'
                    ],
                    'recipes' => [
                        '*' => [
                            'id',
                            'name',
                            'categories' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'order'
                                ]
                            ],
                            'ingredients' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'categoryId',
                                    'quantity',
                                    'unitId',
                                    'order'
                                ]
                            ],
                            'thumbnail',
                            'url',
                            'steps' => [
                                '*' => [
                                    'id',
                                    'instruction',
                                    'image' => [
                                        'id',
                                        'src',
                                        'width',
                                        'height'
                                    ],
                                    'order'
                                ]
                            ],
                            'memo'
                        ]
                    ]
                ]
            ]
        ]
    ]);
});
test('3-6-32: 【詳細取得】 未認証ユーザー', function () {
    $response = $this->get('/meal-plans/' . \Illuminate\Support\Str::uuid());

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

test('3-6-33: 【詳細取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/meal-plans/' . \Illuminate\Support\Str::uuid());

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

test('3-6-34: 【詳細取得】 データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();
    // MealPlanServiceをモックして例外を発生させる
    $this->mock(MealPlanService::class, function ($mock) {
        $mock->shouldReceive('show')
            ->once()->andThrow(new \Exception('Database connection error'));
    });

    $response = $this->actingAs($user)->get("/meal-plans/" . \Illuminate\Support\Str::uuid());

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立の取得に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-35: 【詳細取得】 存在しない献立詳細取得', function () {
    $user = $this->testData->createUserWithGroup();

    $response = $this->actingAs($user)->get('/meal-plans/' . \Illuminate\Support\Str::uuid());

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-36: 【詳細取得】 他グループの献立詳細取得', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    // 別のグループを作成
    $otherGroup = Group::create(['group_size' => 1]);
    $otherMealPlan = $this->testData->createMealPlan($otherGroup->id, $mealCategory['id']);

    $response = $this->actingAs($user)->get("/meal-plans/{$otherMealPlan->id}");

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ==================== update() テストケース ====================

test('3-6-37: 【更新】 正常な献立更新', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);
    $mealPlan->recipes()->attach($recipe['id'], ['menu_category_id' => $menuCategory['id']]);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立(2024-01-16)を更新しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'date',
            'category',
            'menu' => [
                '*' => [
                    'category',
                    'recipes'
                ]
            ],
        ]
    ]);

    // データベースの献立が更新されていることを確認
    $this->assertDatabaseHas('meal_plans', [
        'id' => $mealPlan->id,
        'date' => '2024-01-16'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-38: 【更新】 献立の料理更新', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe1 = $this->testData->createRecipeViaApi($this, $user);
    $recipe2 = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);
    $mealPlan->recipes()->attach($recipe1['id'], ['menu_category_id' => $menuCategory['id']]);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe2['id']], // 別のレシピに変更
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(200);

    // 料理の紐づけが正しく更新されていることを確認
    $updatedMealPlan = MealPlan::find($mealPlan->id);
    expect($updatedMealPlan->recipes)->toHaveCount(1);
    expect($updatedMealPlan->recipes->first()->id)->toBe($recipe2['id']);
});

test('3-6-39: 【更新】 更新成功メッセージの確認', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);
    $mealPlan->recipes()->attach($recipe['id'], ['menu_category_id' => $menuCategory['id']]);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('献立(2024-01-16)を更新しました。');
});

test('3-6-40: 【更新】 未認証ユーザー', function () {
    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => \Illuminate\Support\Str::uuid(),
        'menu' => [
            [
                'recipeIds' => [\Illuminate\Support\Str::uuid()],
                'categoryId' => \Illuminate\Support\Str::uuid()
            ]
        ]
    ];

    $response = $this->put('/meal-plans/' . \Illuminate\Support\Str::uuid(), $requestData);

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

test('3-6-41: 【更新】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => \Illuminate\Support\Str::uuid(),
        'menu' => [
            [
                'recipeIds' => [\Illuminate\Support\Str::uuid()],
                'categoryId' => \Illuminate\Support\Str::uuid()
            ]
        ]
    ];

    $response = $this->actingAs($user)->put('/meal-plans/' . \Illuminate\Support\Str::uuid(), $requestData);

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

test('3-6-42: 【更新】 データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], $menuCategory['id']);

    // MealPlanServiceをモックして例外を発生させる
    $this->mock(MealPlanService::class, function ($mock) {
        $mock->shouldReceive('update')
            ->once()->andThrow(new \Exception('Database connection error'));
    });

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan['id']}", $requestData);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立の更新に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-43: 【更新】 存在しない献立更新', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put('/meal-plans/' . \Illuminate\Support\Str::uuid(), $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-44: 【更新】 他グループの献立更新', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    // 別のグループを作成
    $otherGroup = Group::create(['group_size' => 1]);
    $otherMealPlan = $this->testData->createMealPlan($otherGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$otherMealPlan->id}", $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

// ==================== 更新バリデーションテストケース ====================

test('3-6-45: 【更新】 バリデーションエラー（date 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        // date パラメータを未入力
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['date']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('dateは必ず指定してください。', $responseData['errors']['date']);
});

test('3-6-46: 【更新】 バリデーションエラー（date 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => 'invalid-date-format', // 無効な日付形式
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['date']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('dateはY-m-d形式で指定してください。', $responseData['errors']['date']);
});

test('3-6-47: 【更新】 バリデーションエラー（mealCategoryId 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $this->testData->createmealCategoryViaApi($this, $user)['id']);

    $requestData = [
        'date' => '2024-01-16',
        // mealCategoryId パラメータを未入力
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['mealCategoryId']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('mealCategoryIdは必ず指定してください。', $responseData['errors']['mealCategoryId']);
});

test('3-6-48: 【更新】 バリデーションエラー（mealCategoryId 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $this->testData->createmealCategoryViaApi($this, $user)['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => 'invalid-uuid-format', // 無効なUUID形式
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['mealCategoryId']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('mealCategoryIdに有効なUUIDを指定してください。', $responseData['errors']['mealCategoryId']);
});

test('3-6-49: 【更新】 バリデーションエラー（mealCategoryId 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $this->testData->createmealCategoryViaApi($this, $user)['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => '12345678-1234-1234-1234-123456789012', // 存在しないmealCategoryId
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立カテゴリが見つかりませんでした。'
    ]);
});

test('3-6-50: 【更新】 バリデーションエラー（menu 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        // menu パラメータを未入力
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menuは必ず指定してください。', $responseData['errors']['menu']);
});

test('3-6-51: 【更新】 バリデーションエラー（menu 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => 'not_an_array' // 配列でない
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menuは配列でなくてはなりません。', $responseData['errors']['menu']);
});

test('3-6-52: 【更新】 バリデーションエラー（menu 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [] // 空配列
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);

    // エラーメッセージに「メニューは1個以上指定してください。」が含まれているかチェック
    $responseData = $response->json();
    $this->assertContains('menuは1個以上指定してください。', $responseData['errors']['menu']);
});

test('3-6-53: 【更新】 バリデーションエラー（recipeIds 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                // recipeIds パラメータを未入力
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIdsは必ず指定してください。', $responseData['errors']['menu.0.recipeIds']);
});

test('3-6-54: 【更新】 バリデーションエラー（recipeIds 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => 'not_an_array', // 配列でない
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIdsは配列でなくてはなりません。', $responseData['errors']['menu.0.recipeIds']);
});

test('3-6-55: 【更新】 バリデーションエラー（recipeIds 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [], // 空配列
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);

    // エラーメッセージに「レシピIDは1個以上指定してください。」が含まれているかチェック
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIdsは1個以上指定してください。', $responseData['errors']['menu.0.recipeIds']);
});

test('3-6-56: 【更新】 バリデーションエラー（recipeIds 個別要素必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [null], // 個別要素が未入力
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds.0']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIds.*は必ず指定してください。', $responseData['errors']['menu.0.recipeIds.0']);
});

test('3-6-57: 【更新】 バリデーションエラー（recipeIds 個別要素形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => ['invalid-uuid-format'], // 無効なUUID形式
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.recipeIds.0']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.recipeIds.*に有効なUUIDを指定してください。', $responseData['errors']['menu.0.recipeIds.0']);
});

test('3-6-58: 【更新】 バリデーションエラー（recipeIds 個別要素存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => ['12345678-1234-1234-1234-123456789012'], // 存在しないrecipeId
                'categoryId' => $menuCategory['id']
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された料理/レシピが見つかりませんでした。'
    ]);
});

test('3-6-59: 【更新】 バリデーションエラー（categoryId 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                // categoryId パラメータを未入力
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.categoryId']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.categoryIdは必ず指定してください。', $responseData['errors']['menu.0.categoryId']);
});

test('3-6-60: 【更新】 バリデーションエラー（categoryId 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => 'invalid-uuid-format' // 無効なUUID形式
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['menu.0.categoryId']);

    // エラーメッセージの確認
    $response->assertJson([
        'success' => false
    ]);
    $responseData = $response->json();
    $this->assertContains('menu.*.categoryIdに有効なUUIDを指定してください。', $responseData['errors']['menu.0.categoryId']);
});

test('3-6-61: 【更新】 バリデーションエラー（categoryId 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $requestData = [
        'date' => '2024-01-16',
        'mealCategoryId' => $mealCategory['id'],
        'menu' => [
            [
                'recipeIds' => [$recipe['id']],
                'categoryId' => '12345678-1234-1234-1234-123456789012' // 存在しないcategoryId
            ]
        ]
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定されたapi.attributes.menu_categoryが見つかりませんでした。'
    ]);
});

// ==================== destroy() テストケース ====================

test('3-6-62: 【削除】 正常な献立削除', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $menuCategory = $this->testData->createMenuCategoryViaApi($user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);
    $mealPlan->recipes()->attach($recipe['id'], ['menu_category_id' => $menuCategory['id']]);

    $response = $this->actingAs($user)->delete("/meal-plans/{$mealPlan->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立(2024-01-15)を削除しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // データベースから献立が削除されていることを確認
    $this->assertDatabaseMissing('meal_plans', [
        'id' => $mealPlan->id
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-63: 【削除】 削除成功メッセージの確認', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $response = $this->actingAs($user)->delete("/meal-plans/{$mealPlan->id}");

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('献立(2024-01-15)を削除しました。');
});

test('3-6-64: 【削除】 未認証ユーザー', function () {
    $response = $this->delete('/meal-plans/' . \Illuminate\Support\Str::uuid());

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

test('3-6-65: 【削除】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->delete('/meal-plans/' . \Illuminate\Support\Str::uuid());

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

test('3-6-66: 【削除】 データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    // MealPlanServiceをモックして例外を発生させる
    $this->mock(MealPlanService::class, function ($mock) {
        $mock->shouldReceive('delete')
            ->once()->andThrow(new \Exception('Database connection error'));
    });

    $response = $this->actingAs($user)->delete("/meal-plans/{$mealPlan->id}");

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => '献立の削除に失敗しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-67: 【削除】 存在しない献立削除', function () {
    $user = $this->testData->createUserWithGroup();

    $response = $this->actingAs($user)->delete('/meal-plans/' . \Illuminate\Support\Str::uuid());

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-6-68: 【削除】 他グループの献立削除', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    // 別のグループを作成
    $otherGroup = Group::create(['group_size' => 1]);
    $otherMealPlan = $this->testData->createMealPlan($otherGroup->id, $mealCategory['id']);

    $response = $this->actingAs($user)->delete("/meal-plans/{$otherMealPlan->id}");

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立が見つかりませんでした。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});
