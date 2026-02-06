<?php

use App\Models\User;
use App\Models\Group;
use App\Models\MealPlan;
use App\Models\Meal;
use App\Models\IngredientUnit;
use App\Models\Color;
use App\Services\MealPlanService;
use Carbon\Carbon;
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

    // クエリあり（year, month 指定）で指定月の献立一覧を取得
    $response = $this->actingAs($user)->get('/meal-plans?year=2024&month=1');

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

    $response = $this->actingAs($user)->get('/meal-plans?year=2024&month=1');

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

    $response = $this->actingAs($user)->get('/meal-plans?year=2024&month=1');

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

test('3-5-4: 【一覧取得】 year・month クエリで指定月の献立一覧取得', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    // 1月に2件・2月に1件の献立を作成
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], '2024-01-15');
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], '2024-01-31');
    $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id'], '2024-02-01');

    // year=2024, month=1 のときは 1 月の献立のみ返る
    $responseJan = $this->actingAs($user)->get('/meal-plans?year=2024&month=1');
    $responseJan->assertStatus(200);
    $responseJan->assertJson(['success' => true, 'total' => 2]);
    $dataJan = $responseJan->json('data');
    expect($dataJan)->toHaveCount(2);
    $datesJan = collect($dataJan)->pluck('date')->toArray();
    expect($datesJan)->toContain('2024-01-15');
    expect($datesJan)->toContain('2024-01-31');
    expect($datesJan)->not->toContain('2024-02-01');
    foreach ($datesJan as $d) {
        expect(Carbon::parse($d)->format('Y-n'))->toBe('2024-1');
    }

    // year=2024, month=2 のときは 2 月の献立のみ返る
    $responseFeb = $this->actingAs($user)->get('/meal-plans?year=2024&month=2');
    $responseFeb->assertStatus(200);
    $responseFeb->assertJson(['success' => true, 'total' => 1]);
    $dataFeb = $responseFeb->json('data');
    expect($dataFeb)->toHaveCount(1);
    $datesFeb = collect($dataFeb)->pluck('date')->toArray();
    expect($datesFeb)->toContain('2024-02-01');
    expect($datesFeb)->not->toContain('2024-01-15');
    foreach ($datesFeb as $d) {
        expect(Carbon::parse($d)->format('Y-n'))->toBe('2024-2');
    }
});

test('3-5-5: 【一覧取得】 未認証ユーザー', function () {
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

test('3-5-6: 【一覧取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/meal-plans?year=2024&month=1');

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

test('3-5-7: 【一覧取得】 データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();
    // MealPlanServiceをモックして例外を発生させる
    $this->mock(MealPlanService::class, function ($mock) {
        $mock->shouldReceive('indexForMonth')
            ->once()->andThrow(new \Exception('Database connection error'));
    });

    $response = $this->actingAs($user)->get('/meal-plans?year=2024&month=1');

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

test('3-5-8: 【一覧取得】 MealPlanService 例外', function () {
    $user = $this->testData->createUserWithGroup();

    // MealPlanServiceをモックして例外を発生させる
    $this->mock(MealPlanService::class, function ($mock) {
        $mock->shouldReceive('indexForMonth')
            ->once()->andThrow(new \Exception('MealPlanService error'));
    });

    $response = $this->actingAs($user)->get('/meal-plans?year=2024&month=1');

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

// ==================== index() year/month クエリ・バリデーション テストケース ====================

test('3-5-9: 【一覧取得】 クエリなしでバリデーションエラー（year, month 必須）', function () {
    $user = $this->testData->createUserWithGroup();

    // クエリなし（year も month も付けない）→ 422
    $response = $this->actingAs($user)->get('/meal-plans');

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['year', 'month']);
});

test('3-5-10: 【一覧取得】 バリデーションエラー（year 必須）', function () {
    $user = $this->testData->createUserWithGroup();

    // クエリなし（month のみ）→ year 必須で 422
    $response = $this->actingAs($user)->get('/meal-plans?month=1');

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['year']);
});

test('3-5-11: 【一覧取得】 バリデーションエラー（month 必須）', function () {
    $user = $this->testData->createUserWithGroup();

    $response = $this->actingAs($user)->get('/meal-plans?year=2024');

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['month']);
});

test('3-5-12: 【一覧取得】 バリデーションエラー（year 整数・範囲）', function () {
    $user = $this->testData->createUserWithGroup();

    // 1900未満
    $responseMin = $this->actingAs($user)->get('/meal-plans?year=1899&month=1');
    $responseMin->assertStatus(422);
    $responseMin->assertJson(['success' => false]);
    $responseMin->assertJsonValidationErrors(['year']);

    // 2100超
    $responseMax = $this->actingAs($user)->get('/meal-plans?year=2101&month=1');
    $responseMax->assertStatus(422);
    $responseMax->assertJson(['success' => false]);
    $responseMax->assertJsonValidationErrors(['year']);

    // 整数でない（小数）
    $responseInvalid = $this->actingAs($user)->get('/meal-plans?year=2024.5&month=1');
    $responseInvalid->assertStatus(422);
    $responseInvalid->assertJson(['success' => false]);
    $responseInvalid->assertJsonValidationErrors(['year']);

    // 無効な値（文字列）
    $responseStr = $this->actingAs($user)->get('/meal-plans?year=abc&month=1');
    $responseStr->assertStatus(422);
    $responseStr->assertJson(['success' => false]);
    $responseStr->assertJsonValidationErrors(['year']);

    // 負の数（範囲外）
    $responseNeg = $this->actingAs($user)->get('/meal-plans?year=-1&month=1');
    $responseNeg->assertStatus(422);
    $responseNeg->assertJson(['success' => false]);
    $responseNeg->assertJsonValidationErrors(['year']);
});

test('3-5-13: 【一覧取得】 バリデーションエラー（month 整数・範囲）', function () {
    $user = $this->testData->createUserWithGroup();

    // 1未満
    $responseMin = $this->actingAs($user)->get('/meal-plans?year=2024&month=0');
    $responseMin->assertStatus(422);
    $responseMin->assertJson(['success' => false]);
    $responseMin->assertJsonValidationErrors(['month']);

    // 12超
    $responseMax = $this->actingAs($user)->get('/meal-plans?year=2024&month=13');
    $responseMax->assertStatus(422);
    $responseMax->assertJson(['success' => false]);
    $responseMax->assertJsonValidationErrors(['month']);

    // 整数でない（小数）
    $responseFloat = $this->actingAs($user)->get('/meal-plans?year=2024&month=6.5');
    $responseFloat->assertStatus(422);
    $responseFloat->assertJson(['success' => false]);
    $responseFloat->assertJsonValidationErrors(['month']);

    // 無効な値（文字列）
    $responseStr = $this->actingAs($user)->get('/meal-plans?year=2024&month=abc');
    $responseStr->assertStatus(422);
    $responseStr->assertJson(['success' => false]);
    $responseStr->assertJsonValidationErrors(['month']);

    // 負の数（範囲外）
    $responseNeg = $this->actingAs($user)->get('/meal-plans?year=2024&month=-1');
    $responseNeg->assertStatus(422);
    $responseNeg->assertJson(['success' => false]);
    $responseNeg->assertJsonValidationErrors(['month']);
});

// ==================== store() テストケース ====================

test('3-5-14: 【新規作成】 正常な献立作成', function () {
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

test('3-5-14: 【新規作成】 献立に料理を紐づけ', function () {
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

test('3-5-16: 【新規作成】 未認証ユーザー', function () {
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

test('3-5-17: 【新規作成】 グループが存在しない', function () {
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

test('3-5-18: 【新規作成】 データベース接続エラー', function () {
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


test('3-5-18: 【新規作成】 料理紐づけ失敗', function () {
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

test('3-5-20: 【新規作成】 バリデーションエラー（date 必須）', function () {
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

test('3-5-20: 【新規作成】 バリデーションエラー（date 形式）', function () {
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

test('3-5-22: 【新規作成】 バリデーションエラー（meals 必須）', function () {
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

test('3-5-22: 【新規作成】 バリデーションエラー（meals 配列形式）', function () {
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

test('3-5-24: 【新規作成】 バリデーションエラー（meals 最小要素数）', function () {
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

test('3-5-24: 【新規作成】 バリデーションエラー（meals.*.categoryId 必須）', function () {
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

test('3-5-26: 【新規作成】 バリデーションエラー（meals.*.categoryId 形式）', function () {
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

test('3-5-27: 【新規作成】 バリデーションエラー（categoryId 存在チェック）', function () {
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

test('3-5-27: 【新規作成】 バリデーションエラー（meals.*.recipeIds 必須）', function () {
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

test('3-5-29: 【新規作成】 バリデーションエラー（meals.*.recipeIds 配列形式）', function () {
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

test('3-5-29: 【新規作成】 バリデーションエラー（meals.*.recipeIds 最小要素数）', function () {
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

test('3-5-31: 【新規作成】 バリデーションエラー（meals.*.recipeIds.* 必須）', function () {
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

test('3-5-32: 【新規作成】 バリデーションエラー（meals.*.recipeIds.* UUID形式）', function () {
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

test('3-5-33: 【新規作成】 バリデーションエラー（recipeIds 存在チェック）', function () {
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

test('3-5-34: 【詳細取得】 正常な献立詳細取得', function () {
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
test('3-5-35: 【詳細取得】 未認証ユーザー', function () {
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

test('3-5-36: 【詳細取得】 グループが存在しない', function () {
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

test('3-5-37: 【詳細取得】 データベース接続エラー', function () {
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

test('3-5-37: 【詳細取得】 存在しない献立詳細取得', function () {
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

test('3-5-39: 【詳細取得】 他グループの献立詳細取得', function () {
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

test('3-5-40: 【更新】 正常な献立更新', function () {
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

test('3-5-40: 【更新】 献立の料理更新', function () {
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

test('3-5-46: 【更新】 更新成功メッセージの確認', function () {
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

test('3-5-46: 【更新】 未認証ユーザー', function () {
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

test('3-5-48: 【更新】 グループが存在しない', function () {
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

test('3-5-49: 【更新】 データベース接続エラー', function () {
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

test('3-5-50: 【更新】 存在しない献立更新', function () {
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

test('3-5-50: 【更新】 他グループの献立更新', function () {
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

test('3-5-52: 【更新】 バリデーションエラー（meals 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは必ず指定してください。', $responseData['errors']['meals']);
});

test('3-5-53: 【更新】 バリデーションエラー（meals 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", ['meals' => 'not_an_array']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは配列でなくてはなりません。', $responseData['errors']['meals']);
});

test('3-5-54: 【更新】 バリデーションエラー（meals 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", ['meals' => []]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは1個以上指定してください。', $responseData['errors']['meals']);
});

test('3-5-55: 【更新】 バリデーションエラー（meals 最小要素数・空配列）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", ['meals' => []]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
});

test('3-5-56: 【更新】 バリデーションエラー（meals.*.id 形式）', function () {
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

test('3-5-56: 【更新】 バリデーションエラー（meals.*.categoryId 必須）', function () {
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

test('3-5-58: 【更新】 バリデーションエラー（meals.*.categoryId 形式）', function () {
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

test('3-5-59: 【更新】 バリデーションエラー（categoryId 存在チェック）', function () {
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

test('3-5-60: 【更新】 バリデーションエラー（meals.*.recipeIds 必須）', function () {
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

test('3-5-61: 【更新】 バリデーションエラー（meals.*.recipeIds 配列形式）', function () {
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

test('3-5-62: 【更新】 バリデーションエラー（meals.*.recipeIds 最小要素数）', function () {
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

test('3-5-62: 【更新】 バリデーションエラー（meals.*.recipeIds.* 必須）', function () {
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

test('3-5-64: 【更新】 バリデーションエラー（meals.*.recipeIds.* UUID形式）', function () {
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

test('3-5-65: 【更新】 バリデーションエラー（recipeIds 存在チェック）', function () {
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

test('3-5-66: 【削除】 正常な献立削除', function () {
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

test('3-5-66: 【削除】 削除成功メッセージの確認', function () {
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

test('3-5-68: 【削除】 未認証ユーザー', function () {
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

test('3-5-68: 【削除】 グループが存在しない', function () {
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

test('3-5-70: 【削除】 データベース接続エラー', function () {
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

test('3-5-71: 【削除】 存在しない献立削除', function () {
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

test('3-5-72: 【削除】 他グループの献立削除', function () {
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

test('3-5-72: 【1食削除】 正常に献立の1食を削除', function () {
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

test('3-5-74: 【1食削除】 複数食のうち1食のみ削除', function () {
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

test('3-5-74: 【1食削除】 未認証ユーザー', function () {
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

test('3-5-76: 【1食削除】 グループが存在しない', function () {
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

test('3-5-77: 【1食削除】 存在しない献立ID', function () {
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

test('3-5-78: 【1食削除】 存在しない食事ID', function () {
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

test('3-5-79: 【1食削除】 他グループの献立に属する食事を削除', function () {
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
