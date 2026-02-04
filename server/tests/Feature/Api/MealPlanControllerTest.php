<?php

use App\Models\User;
use App\Models\Group;
use App\Models\MealPlan;
use App\Models\Meal;
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
                $groupId = $user->groups()->first()->id;
                $mealCategory = \App\Models\MealCategory::with('color')
                    ->where('group_id', $groupId)
                    ->where('name', $name)
                    ->latest()
                    ->first();
                if (!$mealCategory) {
                    throw new \Exception('Created meal category not found in DB');
                }
                return [
                    'id' => $mealCategory->id,
                    'name' => $mealCategory->name,
                    'colorCodeHex' => $mealCategory->color->color_code_hex,
                    'order' => $mealCategory->order,
                ];
            }

            throw new \Exception('Failed to create meal type via API: ' . $response->json('message'));
        }

        public function createRecipeViaApi($testInstance, $user, $name = '人参の煮物', $url = null, $memo = '美味しい人参の煮物です', $thumbnailId = null, $categoryIds = [], $servingCount = 4)
        {
            $requestData = [
                'name' => $name,
                'url' => $url,
                'memo' => $memo,
                'servingCount' => $servingCount,
                'thumbnailId' => $thumbnailId,
                'categoryIds' => $categoryIds,
                'ownerUserId' => $user->id
            ];

            $response = $testInstance->actingAs($user)->post('/recipes', $requestData);

            if ($response->status() === 201) {
                $groupId = $user->groups()->first()->id;
                $recipe = \App\Models\Recipe::where('group_id', $groupId)
                    ->where('name', $name)
                    ->latest()
                    ->first();
                if (!$recipe) {
                    throw new \Exception('Created recipe not found in DB');
                }
                $showResponse = $testInstance->actingAs($user)->get('/recipes/' . $recipe->id);
                if ($showResponse->status() !== 200) {
                    throw new \Exception('Failed to get created recipe via show API');
                }
                return $showResponse->json('data');
            }

            throw new \Exception('Failed to create recipe via API: ' . $response->json('message') . ' ' . $response->status());
        }

        public function createMealPlanViaApi($testInstance, $user, $mealCategoryId, $recipeId, $date = '2024-01-15')
        {
            $requestData = [
                'date' => $date,
                'meals' => [
                    [
                        'categoryId' => $mealCategoryId,
                        'recipeIds' => [$recipeId],
                    ],
                ],
            ];

            $response = $testInstance->actingAs($user)->post('/meal-plans', $requestData);

            if ($response->status() === 201) {
                $groupId = $user->groups()->first()->id;
                $mealPlan = MealPlan::where('group_id', $groupId)
                    ->where('date', $date)
                    ->latest()
                    ->first();
                if (!$mealPlan) {
                    throw new \Exception('Created meal plan not found in DB');
                }
                $showResponse = $testInstance->actingAs($user)->get('/meal-plans/' . $mealPlan->id);
                if ($showResponse->status() !== 200) {
                    throw new \Exception('Failed to get created meal plan via show API');
                }
                return $showResponse->json('data');
            }

            throw new \Exception('Failed to create meal plan via API: ' . $response->json('message'));
        }

        public function createMealPlan($groupId, $mealCategoryId, $date = '2024-01-15')
        {
            $mealPlan = MealPlan::create([
                'group_id' => $groupId,
                'date' => $date,
            ]);
            Meal::create([
                'meal_plan_id' => $mealPlan->id,
                'category_id' => $mealCategoryId,
            ]);

            return $mealPlan;
        }
    };
});

// ==================== index() テストケース ====================

test('3-5-1: 【一覧取得】 正常な献立一覧取得', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    // エンドポイントを使用して献立を作成
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);

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
                'id',
                'date',
                'meals' => [
                    '*' => [
                        'id',
                        'category' => [
                            'id',
                            'name',
                            'colorCodeHex',
                            'order'
                        ],
                        'recipes' => [
                            '*' => [
                                'id',
                                'name',
                                'categories',
                                'thumbnail'
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

test('3-5-2: 【一覧取得】 献立データの日付別グループ化確認', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    // エンドポイントを使用して異なる日付の献立を作成
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], '2024-01-15');
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], '2024-01-16');
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], '2024-01-15');

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
    expect($date20240115['meals'])->toHaveCount(2);
});


test('3-5-3: 【一覧取得】 レスポンス形式確認', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    // エンドポイントを使用して献立を作成
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);

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

test('3-5-4: 【一覧取得】 未認証ユーザー', function () {
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

test('3-5-5: 【一覧取得】 グループが存在しない', function () {
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

test('3-5-6: 【一覧取得】 データベース接続エラー', function () {
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

test('3-5-7: 【一覧取得】 MealPlanService 例外', function () {
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

test('3-5-8: 【新規作成】 正常な献立作成', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '献立(2024-01-15)を作成しました。'
    ]);

    // レスポンス構造の確認（store は data を返さない）
    $response->assertJsonStructure(['success', 'message']);
    $response->assertJsonPath('data', null);

    // データベースに献立が保存されていることを確認
    $this->assertDatabaseHas('meal_plans', [
        'group_id' => $this->testData->defaultGroup->id,
        'date' => '2024-01-15'
    ]);
    $mealPlan = MealPlan::where('group_id', $this->testData->defaultGroup->id)->where('date', '2024-01-15')->first();
    $this->assertDatabaseHas('meals', [
        'meal_plan_id' => $mealPlan->id,
        'category_id' => $mealCategory['id'],
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-9: 【新規作成】 献立に料理を紐づけ', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(201);

    // 献立に料理が正しく紐づけられていることを確認
    $mealPlan = MealPlan::where('group_id', $this->testData->defaultGroup->id)
        ->where('date', '2024-01-15')
        ->first();

    expect($mealPlan)->not->toBeNull();
    $meal = $mealPlan->meals->first();
    expect($meal->recipes)->toHaveCount(1);
    expect($meal->recipes->first()->id)->toBe($recipe['id']);
});

test('3-5-10: 【新規作成】 未認証ユーザー', function () {
    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => \Illuminate\Support\Str::uuid(),
                'recipeIds' => [\Illuminate\Support\Str::uuid()],
            ],
        ],
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

test('3-5-11: 【新規作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => \Illuminate\Support\Str::uuid(),
                'recipeIds' => [\Illuminate\Support\Str::uuid()],
            ],
        ],
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

test('3-5-12: 【新規作成】 データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    // データベース接続をモックしてエラーを発生させる
    DB::shouldReceive('connection')->andThrow(new \Exception('Database connection error'));

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
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


test('3-5-13: 【新規作成】 料理紐づけ失敗', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    // データベース接続をモックしてエラーを発生させる
    DB::shouldReceive('transaction')
        ->andThrow(new \Exception('Database transaction failed'));

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
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

// ==================== バリデーションテストケース（FormRequest rules 順） ====================

test('3-5-14: 【新規作成】 バリデーションエラー（date 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['date']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('dateは必ず指定してください。', $responseData['errors']['date']);
});

test('3-5-15: 【新規作成】 バリデーションエラー（date 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => 'invalid-date-format',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['date']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('dateはY-m-d形式で指定してください。', $responseData['errors']['date']);
});

test('3-5-19: 【新規作成】 バリデーションエラー（meals 必須）', function () {
    $user = $this->testData->createUserWithGroup();

    $requestData = [
        'date' => '2024-01-15',
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは必ず指定してください。', $responseData['errors']['meals']);
});

test('3-5-20: 【新規作成】 バリデーションエラー（meals 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => 'not_an_array',
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは配列でなくてはなりません。', $responseData['errors']['meals']);
});

test('3-5-21: 【新規作成】 バリデーションエラー（meals 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは1個以上指定してください。', $responseData['errors']['meals']);
});

test('3-5-16: 【新規作成】 バリデーションエラー（meals.*.categoryId 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.categoryId']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.categoryIdは必ず指定してください。', $responseData['errors']['meals.0.categoryId']);
});

test('3-5-17: 【新規作成】 バリデーションエラー（meals.*.categoryId 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => 'invalid-uuid-format',
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.categoryId']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.categoryIdに有効なUUIDを指定してください。', $responseData['errors']['meals.0.categoryId']);
});

test('3-5-18: 【新規作成】 バリデーションエラー（categoryId 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $nonExistentCategoryId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $nonExistentCategoryId,
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立カテゴリが見つかりませんでした。'
    ]);
});

test('3-5-22: 【新規作成】 バリデーションエラー（meals.*.recipeIds 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIdsは必ず指定してください。', $responseData['errors']['meals.0.recipeIds']);
});

test('3-5-23: 【新規作成】 バリデーションエラー（meals.*.recipeIds 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => 'not_an_array',
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIdsは配列でなくてはなりません。', $responseData['errors']['meals.0.recipeIds']);
});

test('3-5-24: 【新規作成】 バリデーションエラー（meals.*.recipeIds 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIdsは1個以上指定してください。', $responseData['errors']['meals.0.recipeIds']);
});

test('3-5-25: 【新規作成】 バリデーションエラー（meals.*.recipeIds.* 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [null],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds.0']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIds.*は必ず指定してください。', $responseData['errors']['meals.0.recipeIds.0']);
});

test('3-5-26: 【新規作成】 バリデーションエラー（meals.*.recipeIds.* UUID形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => ['invalid-uuid-format'],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds.0']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIds.*に有効なUUIDを指定してください。', $responseData['errors']['meals.0.recipeIds.0']);
});

test('3-5-27: 【新規作成】 バリデーションエラー（recipeIds 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $nonExistentRecipeId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$nonExistentRecipeId],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された料理/レシピが見つかりませんでした。'
    ]);
});

// ==================== show() テストケース ====================

test('3-5-28: 【詳細取得】 正常な献立詳細取得', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);

    $response = $this->actingAs($user)->get("/meal-plans/{$mealPlanData['id']}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立を取得しました。'
    ]);

    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'date',
            'meals' => [
                '*' => [
                    'id',
                    'category' => [
                        'id',
                        'name',
                        'colorCodeHex',
                        'order'
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
                            'thumbnail',
                        ]
                    ],
                ]
            ]
        ]
    ]);
});
test('3-5-29: 【詳細取得】 未認証ユーザー', function () {
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

test('3-5-30: 【詳細取得】 グループが存在しない', function () {
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

test('3-5-31: 【詳細取得】 データベース接続エラー', function () {
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

test('3-5-32: 【詳細取得】 存在しない献立詳細取得', function () {
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

test('3-5-33: 【詳細取得】 他グループの献立詳細取得', function () {
    $user = $this->testData->createUserWithGroup();
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);
    $this->testData->createIngredientUnits($otherGroup->id);
    $otherMealCategory = $this->testData->createmealCategoryViaApi($this, $otherUser, '朝食', null, 0);
    $otherRecipe = $this->testData->createRecipeViaApi($this, $otherUser);
    $otherMealPlanData = $this->testData->createMealPlanViaApi($this, $otherUser, $otherMealCategory['id'], $otherRecipe['id'], '2024-01-20');
    $otherMealPlanId = $otherMealPlanData['id'];

    $response = $this->actingAs($user)->get("/meal-plans/{$otherMealPlanId}");

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

test('3-5-34: 【更新】 正常な献立更新', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], '2024-01-15');
    $mealPlan = MealPlan::find($mealPlanData['id']);
    $meal = $mealPlan->meals->first();

    $requestData = [
        'meals' => [
            [
                'id' => $meal->id,
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立(2024-01-15)を更新しました。'
    ]);

    $response->assertJsonStructure(['success', 'message']);
    $response->assertJsonPath('data', null);

    $this->assertDatabaseHas('meal_plans', [
        'id' => $mealPlan->id,
        'date' => '2024-01-15'
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-35: 【更新】 献立の料理更新', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe1 = $this->testData->createRecipeViaApi($this, $user);
    $recipe2 = $this->testData->createRecipeViaApi($this, $user);

    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe1['id'], '2024-01-15');
    $mealPlan = MealPlan::find($mealPlanData['id']);
    $meal = $mealPlan->meals->first();

    $requestData = [
        'meals' => [
            [
                'id' => $meal->id,
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe2['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(200);

    $updatedMeal = Meal::find($meal->id);
    expect($updatedMeal->recipes)->toHaveCount(1);
    expect($updatedMeal->recipes->first()->id)->toBe($recipe2['id']);
});

test('3-5-36: 【更新】 更新成功メッセージの確認', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], '2024-01-16');
    $mealPlan = MealPlan::find($mealPlanData['id']);
    $meal = $mealPlan->meals->first();

    $requestData = [
        'meals' => [
            [
                'id' => $meal->id,
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(200);

    $message = $response->json('message');
    expect($message)->toBe('献立(2024-01-16)を更新しました。');
});

test('3-5-37: 【更新】 未認証ユーザー', function () {
    $requestData = [
        'meals' => [
            [
                'categoryId' => \Illuminate\Support\Str::uuid(),
                'recipeIds' => [\Illuminate\Support\Str::uuid()],
            ],
        ],
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

test('3-5-38: 【更新】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $requestData = [
        'meals' => [
            [
                'categoryId' => \Illuminate\Support\Str::uuid(),
                'recipeIds' => [\Illuminate\Support\Str::uuid()],
            ],
        ],
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

test('3-5-39: 【更新】 データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);
    $mealPlan = MealPlan::find($mealPlanData['id']);
    $meal = $mealPlan->meals->first();

    $this->mock(MealPlanService::class, function ($mock) {
        $mock->shouldReceive('update')
            ->once()->andThrow(new \Exception('Database connection error'));
    });

    $requestData = [
        'meals' => [
            [
                'id' => $meal->id,
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

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

test('3-5-40: 【更新】 存在しない献立更新', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
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

test('3-5-41: 【更新】 他グループの献立更新', function () {
    $user = $this->testData->createUserWithGroup();
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);
    $this->testData->createIngredientUnits($otherGroup->id);
    $otherMealCategory = $this->testData->createmealCategoryViaApi($this, $otherUser, '朝食', null, 0);
    $otherRecipe = $this->testData->createRecipeViaApi($this, $otherUser);
    $otherMealPlanData = $this->testData->createMealPlanViaApi($this, $otherUser, $otherMealCategory['id'], $otherRecipe['id'], '2024-01-20');
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$otherMealPlanData['id']}", $requestData);

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

// ==================== 更新バリデーションテストケース（FormRequest rules 順） ====================

test('3-5-42: 【更新】 バリデーションエラー（meals 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは必ず指定してください。', $responseData['errors']['meals']);
});

test('3-5-43: 【更新】 バリデーションエラー（meals 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", ['meals' => 'not_an_array']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは配列でなくてはなりません。', $responseData['errors']['meals']);
});

test('3-5-47: 【更新】 バリデーションエラー（meals 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", ['meals' => []]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは1個以上指定してください。', $responseData['errors']['meals']);
});

test('3-5-49: 【更新】 バリデーションエラー（meals 最小要素数・空配列）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", ['meals' => []]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
});

test('3-5-48: 【更新】 バリデーションエラー（meals.*.id 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);

    $requestData = [
        'meals' => [
            [
                'id' => 'invalid-uuid-format',
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.id']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.idに有効なUUIDを指定してください。', $responseData['errors']['meals.0.id']);
});

test('3-5-44: 【更新】 バリデーションエラー（meals.*.categoryId 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $recipe['id']);

    $requestData = [
        'meals' => [
            [
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.categoryId']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.categoryIdは必ず指定してください。', $responseData['errors']['meals.0.categoryId']);
});

test('3-5-45: 【更新】 バリデーションエラー（meals.*.categoryId 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $recipe['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => 'invalid-uuid-format',
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.categoryId']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.categoryIdに有効なUUIDを指定してください。', $responseData['errors']['meals.0.categoryId']);
});

test('3-5-46: 【更新】 バリデーションエラー（categoryId 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $recipe['id']);
    $nonExistentCategoryId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'meals' => [
            [
                'categoryId' => $nonExistentCategoryId,
                'recipeIds' => [$recipe['id']],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立カテゴリが見つかりませんでした。'
    ]);
});

test('3-5-50: 【更新】 バリデーションエラー（meals.*.recipeIds 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIdsは必ず指定してください。', $responseData['errors']['meals.0.recipeIds']);
});

test('3-5-51: 【更新】 バリデーションエラー（meals.*.recipeIds 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => 'not_an_array',
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIdsは配列でなくてはなりません。', $responseData['errors']['meals.0.recipeIds']);
});

test('3-5-52: 【更新】 バリデーションエラー（meals.*.recipeIds 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIdsは1個以上指定してください。', $responseData['errors']['meals.0.recipeIds']);
});

test('3-5-53: 【更新】 バリデーションエラー（meals.*.recipeIds.* 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [null],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds.0']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIds.*は必ず指定してください。', $responseData['errors']['meals.0.recipeIds.0']);
});

test('3-5-54: 【更新】 バリデーションエラー（meals.*.recipeIds.* UUID形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => ['invalid-uuid-format'],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipeIds.0']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipeIds.*に有効なUUIDを指定してください。', $responseData['errors']['meals.0.recipeIds.0']);
});

test('3-5-55: 【更新】 バリデーションエラー（recipeIds 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);
    $nonExistentRecipeId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipeIds' => [$nonExistentRecipeId],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された料理/レシピが見つかりませんでした。'
    ]);
});

// ==================== destroy() テストケース ====================

test('3-5-56: 【削除】 正常な献立削除', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);
    $meal = $mealPlan->meals->first();
    $meal->recipes()->attach($recipe['id']);

    $mealIds = $mealPlan->meals()->pluck('id');

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

    // 紐づいていたmealsレコードも削除されていることを確認
    foreach ($mealIds as $mealId) {
        $this->assertDatabaseMissing('meals', [
            'id' => $mealId
        ]);
    }

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-57: 【削除】 削除成功メッセージの確認', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $mealIds = $mealPlan->meals()->pluck('id');

    $response = $this->actingAs($user)->delete("/meal-plans/{$mealPlan->id}");

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認（献立削除: 献立(日付)を削除しました。）
    $message = $response->json('message');
    expect($message)->toBe('献立(2024-01-15)を削除しました。');

    // 紐づいていたmealsレコードも削除されていることを確認
    foreach ($mealIds as $mealId) {
        $this->assertDatabaseMissing('meals', [
            'id' => $mealId
        ]);
    }
});

test('3-5-58: 【削除】 未認証ユーザー', function () {
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

test('3-5-59: 【削除】 グループが存在しない', function () {
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

test('3-5-60: 【削除】 データベース接続エラー', function () {
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

test('3-5-61: 【削除】 存在しない献立削除', function () {
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

test('3-5-62: 【削除】 他グループの献立削除', function () {
    $user = $this->testData->createUserWithGroup();
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);
    $this->testData->createIngredientUnits($otherGroup->id);
    $otherMealCategory = $this->testData->createmealCategoryViaApi($this, $otherUser, '朝食', null, 0);
    $otherRecipe = $this->testData->createRecipeViaApi($this, $otherUser);
    $otherMealPlanData = $this->testData->createMealPlanViaApi($this, $otherUser, $otherMealCategory['id'], $otherRecipe['id'], '2024-01-20');

    $response = $this->actingAs($user)->delete("/meal-plans/{$otherMealPlanData['id']}");

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

// ==================== destroyMeal() テストケース ====================

test('3-5-67: 【1食削除】 正常に献立の1食を削除', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);
    $meal = $mealPlan->meals->first();
    $meal->recipes()->attach($recipe['id']);

    $response = $this->actingAs($user)->delete("/meal-plans/{$mealPlan->id}/meals/{$meal->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立(2024-01-15 / 朝食)を削除しました。'
    ]);

    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // 削除したmealのみDBから消えていることを確認
    $this->assertDatabaseMissing('meals', [
        'id' => $meal->id
    ]);

    // 献立（meal_plan）は削除されていないことを確認
    $this->assertDatabaseHas('meal_plans', [
        'id' => $mealPlan->id
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-68: 【1食削除】 複数食のうち1食のみ削除', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);
    $mealToDelete = $mealPlan->meals->first();

    // 2食目を追加
    $meal2 = Meal::create([
        'meal_plan_id' => $mealPlan->id,
        'category_id' => $mealCategory['id'],
    ]);

    $response = $this->actingAs($user)->delete("/meal-plans/{$mealPlan->id}/meals/{$mealToDelete->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立(2024-01-15 / 朝食)を削除しました。'
    ]);

    // 削除したmealのみDBから消えていることを確認
    $this->assertDatabaseMissing('meals', [
        'id' => $mealToDelete->id
    ]);

    // 残りのmealは存在することを確認
    $this->assertDatabaseHas('meals', [
        'id' => $meal2->id
    ]);

    // 献立は削除されていないことを確認
    $this->assertDatabaseHas('meal_plans', [
        'id' => $mealPlan->id
    ]);
});

test('3-5-69: 【1食削除】 未認証ユーザー', function () {
    $response = $this->delete('/meal-plans/' . \Illuminate\Support\Str::uuid() . '/meals/' . \Illuminate\Support\Str::uuid());

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);

    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-70: 【1食削除】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $response = $this->actingAs($user)->delete('/meal-plans/' . \Illuminate\Support\Str::uuid() . '/meals/' . \Illuminate\Support\Str::uuid());

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。'
    ]);

    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-71: 【1食削除】 存在しない献立ID', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);
    $meal = $mealPlan->meals->first();

    $response = $this->actingAs($user)->delete('/meal-plans/' . \Illuminate\Support\Str::uuid() . "/meals/{$meal->id}");

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立が見つかりませんでした。'
    ]);

    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-72: 【1食削除】 存在しない食事ID', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);

    $response = $this->actingAs($user)->delete("/meal-plans/{$mealPlan->id}/meals/" . \Illuminate\Support\Str::uuid());

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立の1食が見つかりませんでした。'
    ]);

    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-5-73: 【1食削除】 他グループの献立に属する食事を削除', function () {
    $user = $this->testData->createUserWithGroup();
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);
    $this->testData->createIngredientUnits($otherGroup->id);
    $otherMealCategory = $this->testData->createmealCategoryViaApi($this, $otherUser, '朝食', null, 0);
    $otherRecipe = $this->testData->createRecipeViaApi($this, $otherUser);
    $otherMealPlanData = $this->testData->createMealPlanViaApi($this, $otherUser, $otherMealCategory['id'], $otherRecipe['id'], '2024-01-20');

    $otherMealPlan = MealPlan::where('id', $otherMealPlanData['id'])->first();
    $otherMeal = $otherMealPlan->meals->first();

    $response = $this->actingAs($user)->delete("/meal-plans/{$otherMealPlan->id}/meals/{$otherMeal->id}");

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された献立が見つかりませんでした。'
    ]);

    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});
