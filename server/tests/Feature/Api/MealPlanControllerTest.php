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

            $response = $testInstance->actingAs($user)->postJson('/meal-categories/bulk', ['data' => [$requestData]]);

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
                        'recipes' => [['id' => $recipeId, 'order' => 0]],
                        'order' => 0,
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
                $showResponse = $testInstance->actingAs($user)->get('/meal-plans/' . $mealPlan->date);
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
                'order' => 0,
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

    // レスポンス構造の確認（meals は MealPlanItem のフラット配列: id, recipeId, name, thumbnail, categoryId, order, recipeOrder）
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
                        'recipeId',
                        'name',
                        'thumbnail',
                        'categoryId',
                        'order',
                        'recipeOrder',
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

    // 2024-01-15 には2件の献立（meal plan）があり、フラット配列ではその日の全 MealPlanItem が2件（id, recipeId, name, thumbnail, categoryId, order）
    $date20240115 = collect($responseData)->firstWhere('date', '2024-01-15');
    expect($date20240115['meals'])->toHaveCount(2);
    foreach ($date20240115['meals'] as $item) {
        expect($item)->toHaveKeys(['id', 'recipeId', 'name', 'thumbnail', 'categoryId', 'order']);
    }
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

test('3-5-5: 【一覧取得】 同一日の meals が order 昇順で返ること', function () {
    $user = $this->testData->createUserWithGroup();
    $category1 = $this->testData->createmealCategoryViaApi($this, $user, '朝食');
    $category2 = $this->testData->createmealCategoryViaApi($this, $user, '昼食');
    $category3 = $this->testData->createmealCategoryViaApi($this, $user, '夕食');
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            ['categoryId' => $category1['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 2],
            ['categoryId' => $category2['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 0],
            ['categoryId' => $category3['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 1],
        ],
    ];
    $this->actingAs($user)->post('/meal-plans', $requestData)->assertStatus(201);

    $response = $this->actingAs($user)->get('/meal-plans?year=2024&month=1');
    $response->assertStatus(200);
    $data = $response->json('data');
    $day = collect($data)->firstWhere('date', '2024-01-15');
    expect($day)->not->toBeNull();
    $meals = $day['meals'];
    expect($meals)->toHaveCount(3);
    // フラット配列は meal の order 昇順→レシピ順（pivot の order＝recipeOrder 昇順）。order 0=昼食, 1=夕食, 2=朝食
    expect($meals[0]['order'])->toBe(0);
    expect($meals[1]['order'])->toBe(1);
    expect($meals[2]['order'])->toBe(2);
    expect($meals[0]['categoryId'])->toBe($category2['id']);
    expect($meals[1]['categoryId'])->toBe($category3['id']);
    expect($meals[2]['categoryId'])->toBe($category1['id']);
    // 1食1レシピのため recipeOrder はすべて 0
    expect($meals[0]['recipeOrder'])->toBe(0);
    expect($meals[1]['recipeOrder'])->toBe(0);
    expect($meals[2]['recipeOrder'])->toBe(0);
});

test('3-5-6: 【一覧取得】 未認証ユーザー', function () {
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

test('3-5-7: 【一覧取得】 グループが存在しない', function () {
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

test('3-5-8: 【一覧取得】 データベース接続エラー', function () {
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

test('3-5-9: 【一覧取得】 MealPlanService 例外', function () {
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

test('3-5-10: 【一覧取得】 クエリなしでバリデーションエラー（year, month 必須）', function () {
    $user = $this->testData->createUserWithGroup();

    // クエリなし（year も month も付けない）→ 422
    $response = $this->actingAs($user)->get('/meal-plans');

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['year', 'month']);
});

test('3-5-11: 【一覧取得】 バリデーションエラー（year 必須）', function () {
    $user = $this->testData->createUserWithGroup();

    // クエリなし（month のみ）→ year 必須で 422
    $response = $this->actingAs($user)->get('/meal-plans?month=1');

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['year']);
});

test('3-5-12: 【一覧取得】 バリデーションエラー（month 必須）', function () {
    $user = $this->testData->createUserWithGroup();

    $response = $this->actingAs($user)->get('/meal-plans?year=2024');

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['month']);
});

test('3-5-13: 【一覧取得】 バリデーションエラー（year 整数・範囲）', function () {
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

test('3-5-14: 【一覧取得】 バリデーションエラー（month 整数・範囲）', function () {
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

test('3-5-15: 【新規作成】 正常な献立作成', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-16: 【新規作成】 献立に料理を紐づけ', function () {
    $user = $this->testData->createUserWithGroup();

    // エンドポイントを使用してデータを作成
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-17: 【新規作成】 order が保存され show で order 昇順で返ること', function () {
    $user = $this->testData->createUserWithGroup();
    $category1 = $this->testData->createmealCategoryViaApi($this, $user, '朝食');
    $category2 = $this->testData->createmealCategoryViaApi($this, $user, '昼食');
    $category3 = $this->testData->createmealCategoryViaApi($this, $user, '夕食');
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            ['categoryId' => $category1['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 2],
            ['categoryId' => $category2['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 0],
            ['categoryId' => $category3['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 1],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);
    $response->assertStatus(201);

    $mealPlan = MealPlan::where('group_id', $this->testData->defaultGroup->id)->where('date', '2024-01-15')->first();
    $showResponse = $this->actingAs($user)->get("/meal-plans/{$mealPlan->date}");
    $showResponse->assertStatus(200);

    $meals = $showResponse->json('data.meals');
    expect($meals)->toHaveCount(3);
    // フラット配列は meal の order 昇順→レシピ順。order 0=昼食, 1=夕食, 2=朝食
    expect($meals[0]['order'])->toBe(0);
    expect($meals[1]['order'])->toBe(1);
    expect($meals[2]['order'])->toBe(2);
    expect($meals[0]['categoryId'])->toBe($category2['id']);
    expect($meals[1]['categoryId'])->toBe($category3['id']);
    expect($meals[2]['categoryId'])->toBe($category1['id']);
});

test('3-5-18: 【新規作成】 1食内のレシピ順が保存され show で recipeOrder 順で返ること', function () {
    $user = $this->testData->createUserWithGroup();
    $category = $this->testData->createmealCategoryViaApi($this, $user, '朝食');
    $recipeA = $this->testData->createRecipeViaApi($this, $user, 'レシピA');
    $recipeB = $this->testData->createRecipeViaApi($this, $user, 'レシピB');
    $recipeC = $this->testData->createRecipeViaApi($this, $user, 'レシピC');

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $category['id'],
                'recipes' => [
                    ['id' => $recipeA['id'], 'order' => 0],
                    ['id' => $recipeB['id'], 'order' => 1],
                    ['id' => $recipeC['id'], 'order' => 2],
                ],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);
    $response->assertStatus(201);

    $mealPlan = MealPlan::where('group_id', $this->testData->defaultGroup->id)->where('date', '2024-01-15')->first();
    expect($mealPlan)->not->toBeNull();
    $meal = $mealPlan->meals->first();
    expect($meal)->not->toBeNull();

    // DB の meal_recipe_mappings に order 0, 1, 2 が入っていることを確認
    $mappings = \Illuminate\Support\Facades\DB::table('meal_recipe_mappings')
        ->where('meal_id', $meal->id)
        ->orderBy('order')
        ->get();
    expect($mappings)->toHaveCount(3);
    expect($mappings[0]->recipe_id)->toBe($recipeA['id']);
    expect($mappings[0]->order)->toBe(0);
    expect($mappings[1]->recipe_id)->toBe($recipeB['id']);
    expect($mappings[1]->order)->toBe(1);
    expect($mappings[2]->recipe_id)->toBe($recipeC['id']);
    expect($mappings[2]->order)->toBe(2);

    // show で meal の order → recipeOrder 昇順で返ることを確認
    $showResponse = $this->actingAs($user)->get("/meal-plans/{$mealPlan->date}");
    $showResponse->assertStatus(200);
    $meals = $showResponse->json('data.meals');
    expect($meals)->toHaveCount(3);
    expect($meals[0]['recipeId'])->toBe($recipeA['id']);
    expect($meals[0]['recipeOrder'])->toBe(0);
    expect($meals[1]['recipeId'])->toBe($recipeB['id']);
    expect($meals[1]['recipeOrder'])->toBe(1);
    expect($meals[2]['recipeId'])->toBe($recipeC['id']);
    expect($meals[2]['recipeOrder'])->toBe(2);
});

test('3-5-18: 【新規作成】 未認証ユーザー', function () {
    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => \Illuminate\Support\Str::uuid(),
                'recipes' => [['id' => \Illuminate\Support\Str::uuid(), 'order' => 0]],
                'order' => 0,
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

test('3-5-19: 【新規作成】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => \Illuminate\Support\Str::uuid(),
                'recipes' => [['id' => \Illuminate\Support\Str::uuid(), 'order' => 0]],
                'order' => 0,
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

test('3-5-20: 【新規作成】 データベース接続エラー', function () {
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
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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


test('3-5-21: 【新規作成】 料理紐づけ失敗', function () {
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
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-23: 【新規作成】 バリデーションエラー（date 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-22: 【新規作成】 バリデーションエラー（date 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => 'invalid-date-format',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-26: 【新規作成】 バリデーションエラー（meals 必須）', function () {
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

test('3-5-24: 【新規作成】 バリデーションエラー（meals 配列形式）', function () {
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

test('3-5-25: 【新規作成】 バリデーションエラー（meals 最小要素数）', function () {
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

test('3-5-28: 【新規作成】 バリデーションエラー（meals.*.categoryId 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-27: 【新規作成】 バリデーションエラー（meals.*.categoryId 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => 'invalid-uuid-format',
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-37: 【新規作成】 バリデーションエラー（categoryId 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $nonExistentCategoryId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $nonExistentCategoryId,
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-33: 【新規作成】 バリデーションエラー（meals.*.recipes 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipesは必ず指定してください。', $responseData['errors']['meals.0.recipes']);
});

test('3-5-32: 【新規作成】 バリデーションエラー（meals.*.recipes 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => 'not_an_array',
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipesは配列でなくてはなりません。', $responseData['errors']['meals.0.recipes']);
});

test('3-5-32: 【新規作成】 バリデーションエラー（meals.*.recipes 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipesは1個以上指定してください。', $responseData['errors']['meals.0.recipes']);
});

test('3-5-34: 【新規作成】 バリデーションエラー（meals.*.recipes.*.id 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['order' => 0]],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes.0.id']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipes.*.idは必ず指定してください。', $responseData['errors']['meals.0.recipes.0.id']);
});

test('3-5-34: 【新規作成】 バリデーションエラー（meals.*.recipes.*.id UUID形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => 'invalid-uuid-format', 'order' => 0]],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes.0.id']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipes.*.idに有効なUUIDを指定してください。', $responseData['errors']['meals.0.recipes.0.id']);
});

test('3-5-36: 【新規作成】 バリデーションエラー（meals.*.recipes.*.id 重複）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0], ['id' => $recipe['id'], 'order' => 1]],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['meals.0.recipes.1.id']);
    $responseData = $response->json();
    $this->assertNotEmpty($responseData['errors']['meals.0.recipes.1.id']);
    $this->assertStringContainsString('異なった値を指定', implode(' ', $responseData['errors']['meals.0.recipes.1.id']));
});

test('3-5-30: 【新規作成】 バリデーションエラー（meals.*.order 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.order']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.orderは必ず指定してください。', $responseData['errors']['meals.0.order']);
});

test('3-5-29: 【新規作成】 バリデーションエラー（meals.*.order 整数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 1.5,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/meal-plans', $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.order']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.orderは整数で指定してください。', $responseData['errors']['meals.0.order']);
});

test('3-5-38: 【新規作成】 バリデーションエラー（recipes 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $nonExistentRecipeId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $nonExistentRecipeId, 'order' => 0]],
                'order' => 0,
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

test('3-5-39: 【詳細取得】 正常な献立詳細取得', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);

    $response = $this->actingAs($user)->get("/meal-plans/{$mealPlanData['date']}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '献立を取得しました。'
    ]);

    // data.meals は MealPlanItem のフラット配列（id, recipeId, name, thumbnail, categoryId, order, recipeOrder）
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'date',
            'meals' => [
                '*' => [
                    'id',
                    'recipeId',
                    'name',
                    'thumbnail',
                    'categoryId',
                    'order',
                    'recipeOrder',
                ]
            ]
        ]
    ]);
});
test('3-5-40: 【詳細取得】 未認証ユーザー', function () {
    $response = $this->get('/meal-plans/2024-01-15');

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

test('3-5-41: 【詳細取得】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $response = $this->actingAs($user)->get('/meal-plans/2024-01-15');

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

test('3-5-42: 【詳細取得】 データベース接続エラー', function () {
    $user = $this->testData->createUserWithGroup();
    // MealPlanServiceをモックして例外を発生させる
    $this->mock(MealPlanService::class, function ($mock) {
        $mock->shouldReceive('showByDate')
            ->once()->andThrow(new \Exception('Database connection error'));
    });

    $response = $this->actingAs($user)->get('/meal-plans/2024-01-15');

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

test('3-5-43: 【詳細取得】 日付形式不正で 422', function () {
    $user = $this->testData->createUserWithGroup();

    $response = $this->actingAs($user)->get('/meal-plans/invalid-date');

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['date']);
});

test('3-5-44: 【詳細取得】 存在しない献立詳細取得', function () {
    $user = $this->testData->createUserWithGroup();

    $response = $this->actingAs($user)->get('/meal-plans/2099-12-31');

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

test('3-5-44: 【詳細取得】 他グループの献立詳細取得', function () {
    $user = $this->testData->createUserWithGroup();
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);
    $this->testData->createIngredientUnits($otherGroup->id);
    $otherMealCategory = $this->testData->createmealCategoryViaApi($this, $otherUser, '朝食', null, 0);
    $otherRecipe = $this->testData->createRecipeViaApi($this, $otherUser);
    $this->testData->createMealPlanViaApi($this, $otherUser, $otherMealCategory['id'], $otherRecipe['id'], '2024-01-20');

    // 自グループには 2024-01-20 の献立がないため 404
    $response = $this->actingAs($user)->get('/meal-plans/2024-01-20');

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

test('3-5-46: 【更新】 正常な献立更新', function () {
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
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-47: 【更新】 献立の料理更新', function () {
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
                'recipes' => [['id' => $recipe2['id'], 'order' => 0]],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(200);

    $updatedMeal = Meal::find($meal->id);
    expect($updatedMeal->recipes)->toHaveCount(1);
    expect($updatedMeal->recipes->first()->id)->toBe($recipe2['id']);
});

test('3-5-48: 【更新】 既存の meal が存在する場合の更新', function () {
    $user = $this->testData->createUserWithGroup();
    $category1 = $this->testData->createmealCategoryViaApi($this, $user, '朝食');
    $category2 = $this->testData->createmealCategoryViaApi($this, $user, '昼食');
    $recipe1 = $this->testData->createRecipeViaApi($this, $user, '人参の煮物');
    $recipe2 = $this->testData->createRecipeViaApi($this, $user, '肉じゃが');

    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $category1['id'], $recipe1['id'], '2024-01-15');
    $mealPlan = MealPlan::find($mealPlanData['id']);
    $meal = $mealPlan->meals->first();

    // 既存 meal の id を含めてカテゴリ・レシピを変更して更新
    $requestData = [
        'meals' => [
            [
                'id' => $meal->id,
                'categoryId' => $category2['id'],
                'recipes' => [['id' => $recipe2['id'], 'order' => 0]],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $updatedMeal = Meal::find($meal->id);
    expect($updatedMeal->category_id)->toBe($category2['id']);
    expect($updatedMeal->recipes->pluck('id')->toArray())->toContain($recipe2['id']);
});

test('3-5-49: 【更新】 新規 meal を追加する場合', function () {
    $user = $this->testData->createUserWithGroup();
    $category1 = $this->testData->createmealCategoryViaApi($this, $user, '朝食');
    $category2 = $this->testData->createmealCategoryViaApi($this, $user, '昼食');
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $category1['id'], $recipe['id'], '2024-01-15');
    $mealPlan = MealPlan::find($mealPlanData['id']);
    $existingMeal = $mealPlan->meals->first();

    // 既存 meal（id あり）＋ 新規 meal（id なし）を送信
    $requestData = [
        'meals' => [
            [
                'id' => $existingMeal->id,
                'categoryId' => $category1['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
            ],
            [
                'categoryId' => $category2['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 1,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $mealPlan->refresh();
    expect($mealPlan->meals)->toHaveCount(2);
    $newMeal = $mealPlan->meals->where('id', '!=', $existingMeal->id)->first();
    expect($newMeal)->not->toBeNull();
    expect($newMeal->category_id)->toBe($category2['id']);
    expect($newMeal->order)->toBe(1);
});

test('3-5-50: 【更新】 既存の meal を削除する場合', function () {
    $user = $this->testData->createUserWithGroup();
    $category1 = $this->testData->createmealCategoryViaApi($this, $user, '朝食');
    $category2 = $this->testData->createmealCategoryViaApi($this, $user, '昼食');
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            ['categoryId' => $category1['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 0],
            ['categoryId' => $category2['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 1],
        ],
    ];
    $this->actingAs($user)->post('/meal-plans', $requestData)->assertStatus(201);

    $mealPlan = MealPlan::where('group_id', $this->testData->defaultGroup->id)->where('date', '2024-01-15')->first();
    $meals = $mealPlan->meals()->orderBy('order')->get();
    expect($meals)->toHaveCount(2);

    // 1件目の meal のみ送信 → 2件目は削除される
    $updateData = [
        'meals' => [
            [
                'id' => $meals[0]->id,
                'categoryId' => $category1['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
            ],
        ],
    ];
    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $updateData);
    $response->assertStatus(200);

    $mealPlan->refresh();
    expect($mealPlan->meals)->toHaveCount(1);
    expect($mealPlan->meals->first()->id)->toBe($meals[0]->id);
    $this->assertDatabaseMissing('meals', ['id' => $meals[1]->id]);
});

test('3-5-51: 【更新】 既存 meal の更新・新規 meal の追加・既存 meal の削除を同時に行う場合', function () {
    $user = $this->testData->createUserWithGroup();
    $category1 = $this->testData->createmealCategoryViaApi($this, $user, '朝食');
    $category2 = $this->testData->createmealCategoryViaApi($this, $user, '昼食');
    $category3 = $this->testData->createmealCategoryViaApi($this, $user, '夕食');
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            ['categoryId' => $category1['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 0],
            ['categoryId' => $category2['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 1],
        ],
    ];
    $this->actingAs($user)->post('/meal-plans', $requestData)->assertStatus(201);

    $mealPlan = MealPlan::where('group_id', $this->testData->defaultGroup->id)->where('date', '2024-01-15')->first();
    $meals = $mealPlan->meals()->orderBy('order')->get();
    expect($meals)->toHaveCount(2);

    // 1件目は更新（カテゴリ変更）、2件目は削除（含めない）、3件目は新規追加
    $updateData = [
        'meals' => [
            [
                'id' => $meals[0]->id,
                'categoryId' => $category2['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
            ],
            [
                'categoryId' => $category3['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 1,
            ],
        ],
    ];
    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $updateData);
    $response->assertStatus(200);

    $showResponse = $this->actingAs($user)->get("/meal-plans/{$mealPlan->date}");
    $showResponse->assertStatus(200);
    $returnedMeals = $showResponse->json('data.meals');
    expect($returnedMeals)->toHaveCount(2);
    // フラット形式では id はレシピ id のため、categoryId で検証（更新後: category2 と category3 が1件ずつ）
    $categoryIds = collect($returnedMeals)->pluck('categoryId')->toArray();
    expect($categoryIds)->toContain($category2['id']);
    expect($categoryIds)->toContain($category3['id']);

    $this->assertDatabaseMissing('meals', ['id' => $meals[1]->id]);
});

test('3-5-52: 【更新】 更新成功メッセージの確認', function () {
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
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $requestData);

    $response->assertStatus(200);

    $message = $response->json('message');
    expect($message)->toBe('献立(2024-01-16)を更新しました。');
});

test('3-5-53: 【更新】 order が反映され show で order 昇順で返ること', function () {
    $user = $this->testData->createUserWithGroup();
    $category1 = $this->testData->createmealCategoryViaApi($this, $user, '朝食');
    $category2 = $this->testData->createmealCategoryViaApi($this, $user, '昼食');
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'date' => '2024-01-15',
        'meals' => [
            ['categoryId' => $category1['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 0],
            ['categoryId' => $category2['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 1],
        ],
    ];
    $this->actingAs($user)->post('/meal-plans', $requestData)->assertStatus(201);

    $mealPlan = MealPlan::where('group_id', $this->testData->defaultGroup->id)->where('date', '2024-01-15')->first();
    $meals = $mealPlan->meals()->orderBy('order')->get();
    expect($meals)->toHaveCount(2);

    // order を入れ替えて PUT
    $updateData = [
        'meals' => [
            ['id' => $meals[1]->id, 'categoryId' => $category2['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 0],
            ['id' => $meals[0]->id, 'categoryId' => $category1['id'], 'recipes' => [['id' => $recipe['id'], 'order' => 0]], 'order' => 1],
        ],
    ];
    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $updateData);
    $response->assertStatus(200);

    $showResponse = $this->actingAs($user)->get("/meal-plans/{$mealPlan->date}");
    $showResponse->assertStatus(200);
    $returnedMeals = $showResponse->json('data.meals');
    expect($returnedMeals)->toHaveCount(2);
    // フラット配列は meal の order 昇順→レシピ順（pivot の order＝recipeOrder 昇順）。入れ替え後は order 0=昼食, 1=朝食
    expect($returnedMeals[0]['order'])->toBe(0);
    expect($returnedMeals[1]['order'])->toBe(1);
    expect($returnedMeals[0]['categoryId'])->toBe($category2['id']);
    expect($returnedMeals[1]['categoryId'])->toBe($category1['id']);
    // 1食1レシピのため recipeOrder はすべて 0
    expect($returnedMeals[0]['recipeOrder'])->toBe(0);
    expect($returnedMeals[1]['recipeOrder'])->toBe(0);
});

test('3-5-55: 【更新】 1食内のレシピ順を変更すると recipeOrder が更新され show で反映されること', function () {
    $user = $this->testData->createUserWithGroup();
    $category = $this->testData->createmealCategoryViaApi($this, $user, '朝食');
    $recipeA = $this->testData->createRecipeViaApi($this, $user, 'レシピA');
    $recipeB = $this->testData->createRecipeViaApi($this, $user, 'レシピB');
    $recipeC = $this->testData->createRecipeViaApi($this, $user, 'レシピC');

    // 初期: A=0, B=1, C=2 で献立作成
    $requestData = [
        'date' => '2024-01-16',
        'meals' => [
            [
                'categoryId' => $category['id'],
                'recipes' => [
                    ['id' => $recipeA['id'], 'order' => 0],
                    ['id' => $recipeB['id'], 'order' => 1],
                    ['id' => $recipeC['id'], 'order' => 2],
                ],
                'order' => 0,
            ],
        ],
    ];
    $this->actingAs($user)->post('/meal-plans', $requestData)->assertStatus(201);

    $mealPlan = MealPlan::where('group_id', $this->testData->defaultGroup->id)->where('date', '2024-01-16')->first();
    $meal = $mealPlan->meals->first();

    // 同じ meal の recipes の order を変えて PUT: B=0, A=1, C=2
    $updateData = [
        'meals' => [
            [
                'id' => $meal->id,
                'categoryId' => $category['id'],
                'recipes' => [
                    ['id' => $recipeB['id'], 'order' => 0],
                    ['id' => $recipeA['id'], 'order' => 1],
                    ['id' => $recipeC['id'], 'order' => 2],
                ],
                'order' => 0,
            ],
        ],
    ];
    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlan->id}", $updateData);
    $response->assertStatus(200);

    // meal_recipe_mappings の order が更新されていることを確認
    $mappings = \Illuminate\Support\Facades\DB::table('meal_recipe_mappings')
        ->where('meal_id', $meal->id)
        ->orderBy('order')
        ->get();
    expect($mappings)->toHaveCount(3);
    expect($mappings[0]->recipe_id)->toBe($recipeB['id']);
    expect($mappings[0]->order)->toBe(0);
    expect($mappings[1]->recipe_id)->toBe($recipeA['id']);
    expect($mappings[1]->order)->toBe(1);
    expect($mappings[2]->recipe_id)->toBe($recipeC['id']);
    expect($mappings[2]->order)->toBe(2);

    // show で recipeOrder が期待どおりであることを確認（B=0, A=1, C=2）
    $showResponse = $this->actingAs($user)->get("/meal-plans/{$mealPlan->date}");
    $showResponse->assertStatus(200);
    $meals = $showResponse->json('data.meals');
    expect($meals)->toHaveCount(3);
    expect($meals[0]['recipeId'])->toBe($recipeB['id']);
    expect($meals[0]['recipeOrder'])->toBe(0);
    expect($meals[1]['recipeId'])->toBe($recipeA['id']);
    expect($meals[1]['recipeOrder'])->toBe(1);
    expect($meals[2]['recipeId'])->toBe($recipeC['id']);
    expect($meals[2]['recipeOrder'])->toBe(2);
});

test('3-5-54: 【更新】 未認証ユーザー', function () {
    $requestData = [
        'meals' => [
            [
                'categoryId' => \Illuminate\Support\Str::uuid(),
                'recipes' => [['id' => \Illuminate\Support\Str::uuid(), 'order' => 0]],
                'order' => 0,
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

test('3-5-55: 【更新】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $requestData = [
        'meals' => [
            [
                'categoryId' => \Illuminate\Support\Str::uuid(),
                'recipes' => [['id' => \Illuminate\Support\Str::uuid(), 'order' => 0]],
                'order' => 0,
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

test('3-5-55: 【更新】 データベース接続エラー', function () {
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
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-57: 【更新】 存在しない献立更新', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-58: 【更新】 他グループの献立更新', function () {
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
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-59: 【更新】 バリデーションエラー（meals 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは必ず指定してください。', $responseData['errors']['meals']);
});

test('3-5-60: 【更新】 バリデーションエラー（meals 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", ['meals' => 'not_an_array']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは配列でなくてはなりません。', $responseData['errors']['meals']);
});

test('3-5-61: 【更新】 バリデーションエラー（meals 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", ['meals' => []]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('mealsは1個以上指定してください。', $responseData['errors']['meals']);
});

test('3-5-62: 【更新】 バリデーションエラー（meals.*.id 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);

    $requestData = [
        'meals' => [
            [
                'id' => 'invalid-uuid-format',
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-64: 【更新】 バリデーションエラー（meals.*.categoryId 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $recipe['id']);

    $requestData = [
        'meals' => [
            [
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-63: 【更新】 バリデーションエラー（meals.*.categoryId 形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $recipe['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => 'invalid-uuid-format',
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-73: 【更新】 バリデーションエラー（categoryId 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $this->testData->createmealCategoryViaApi($this, $user)['id'], $recipe['id']);
    $nonExistentCategoryId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'meals' => [
            [
                'categoryId' => $nonExistentCategoryId,
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 0,
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

test('3-5-69: 【更新】 バリデーションエラー（meals.*.recipes 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipesは必ず指定してください。', $responseData['errors']['meals.0.recipes']);
});

test('3-5-67: 【更新】 バリデーションエラー（meals.*.recipes 配列形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => 'not_an_array',
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipesは配列でなくてはなりません。', $responseData['errors']['meals.0.recipes']);
});

test('3-5-68: 【更新】 バリデーションエラー（meals.*.recipes 最小要素数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipesは1個以上指定してください。', $responseData['errors']['meals.0.recipes']);
});

test('3-5-71: 【更新】 バリデーションエラー（meals.*.recipes.*.id 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['order' => 0]],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes.0.id']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipes.*.idは必ず指定してください。', $responseData['errors']['meals.0.recipes.0.id']);
});

test('3-5-70: 【更新】 バリデーションエラー（meals.*.recipes.*.id UUID形式）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => 'invalid-uuid-format', 'order' => 0]],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.recipes.0.id']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.recipes.*.idに有効なUUIDを指定してください。', $responseData['errors']['meals.0.recipes.0.id']);
});

test('3-5-72: 【更新】 バリデーションエラー（meals.*.recipes.*.id 重複）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);
    $mealPlan = MealPlan::find($mealPlanData['id']);
    $meal = $mealPlan->meals->first();

    $requestData = [
        'meals' => [
            [
                'id' => $meal->id,
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0], ['id' => $recipe['id'], 'order' => 1]],
                'order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['meals.0.recipes.1.id']);
    $responseData = $response->json();
    $this->assertNotEmpty($responseData['errors']['meals.0.recipes.1.id']);
    $this->assertStringContainsString('異なった値を指定', implode(' ', $responseData['errors']['meals.0.recipes.1.id']));
});

test('3-5-74: 【更新】 バリデーションエラー（recipes 存在チェック）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $this->testData->createRecipeViaApi($this, $user)['id']);
    $nonExistentRecipeId = '12345678-1234-1234-1234-123456789012';

    $requestData = [
        'meals' => [
            [
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $nonExistentRecipeId, 'order' => 0]],
                'order' => 0,
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

test('3-5-66: 【更新】 バリデーションエラー（meals.*.order 必須）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);
    $mealPlan = MealPlan::find($mealPlanData['id']);
    $meal = $mealPlan->meals->first();

    $requestData = [
        'meals' => [
            [
                'id' => $meal->id,
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.order']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.orderは必ず指定してください。', $responseData['errors']['meals.0.order']);
});

test('3-5-65: 【更新】 バリデーションエラー（meals.*.order 整数）', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);
    $recipe = $this->testData->createRecipeViaApi($this, $user);
    $mealPlanData = $this->testData->createMealPlanViaApi($this, $user, $mealCategory['id'], $recipe['id']);
    $mealPlan = MealPlan::find($mealPlanData['id']);
    $meal = $mealPlan->meals->first();

    $requestData = [
        'meals' => [
            [
                'id' => $meal->id,
                'categoryId' => $mealCategory['id'],
                'recipes' => [['id' => $recipe['id'], 'order' => 0]],
                'order' => 'one',
            ],
        ],
    ];

    $response = $this->actingAs($user)->put("/meal-plans/{$mealPlanData['id']}", $requestData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meals.0.order']);
    $response->assertJson(['success' => false]);
    $responseData = $response->json();
    $this->assertContains('meals.*.orderは整数で指定してください。', $responseData['errors']['meals.0.order']);
});

// ==================== destroy() テストケース ====================

test('3-5-75: 【削除】 正常な献立削除', function () {
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

test('3-5-76: 【削除】 削除成功メッセージの確認', function () {
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

test('3-5-77: 【削除】 未認証ユーザー', function () {
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

test('3-5-78: 【削除】 グループが存在しない', function () {
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

test('3-5-79: 【削除】 データベース接続エラー', function () {
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

test('3-5-80: 【削除】 存在しない献立削除', function () {
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

test('3-5-81: 【削除】 他グループの献立削除', function () {
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

test('3-5-82: 【1食削除】 正常に献立の1食を削除', function () {
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

test('3-5-83: 【1食削除】 複数食のうち1食のみ削除', function () {
    $user = $this->testData->createUserWithGroup();
    $mealCategory = $this->testData->createmealCategoryViaApi($this, $user);

    $mealPlan = $this->testData->createMealPlan($this->testData->defaultGroup->id, $mealCategory['id']);
    $mealToDelete = $mealPlan->meals->first();

    // 2食目を追加
    $meal2 = Meal::create([
        'meal_plan_id' => $mealPlan->id,
        'category_id' => $mealCategory['id'],
        'order' => 1,
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

test('3-5-84: 【1食削除】 未認証ユーザー', function () {
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

test('3-5-85: 【1食削除】 グループが存在しない', function () {
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

test('3-5-86: 【1食削除】 存在しない献立ID', function () {
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

test('3-5-87: 【1食削除】 存在しない食事ID', function () {
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

test('3-5-88: 【1食削除】 他グループの献立に属する食事を削除', function () {
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
