<?php

use App\Models\User;
use App\Models\Group;
use App\Models\RecipeCategory;
use Illuminate\Support\Facades\DB;
use App\Models\Ingredient;
use App\Models\IngredientUnit;
use App\Models\Image;
use App\Models\Recipe;
use App\Support\ValidationLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function getRecipeIdAfterStore(\App\Models\Group $group, string $name = 'カレーライス'): string
{
    $recipe = Recipe::where('group_id', $group->id)->where('name', $name)->latest()->first();
    if (!$recipe) {
        throw new \RuntimeException("Recipe not found in DB: group_id={$group->id}, name={$name}");
    }
    return $recipe->id;
}

function getDefaultIngredientCategoryId($testInstance, User $user, string $recipeId): string
{
    $showResponse = $testInstance->actingAs($user)->get("/recipes/{$recipeId}");
    $defaultCategory = collect($showResponse->json('data.ingredientCategories'))
        ->firstWhere('isDefault', true);

    if (!$defaultCategory) {
        throw new \RuntimeException("Default ingredient category not found for recipe: {$recipeId}");
    }

    return $defaultCategory['id'];
}

function getOtherRecipeIngredientCategoryId($testInstance, User $otherUser, Group $otherGroup): string
{
    $testInstance->actingAs($otherUser)->post('/recipes', [
        'name' => '他レシピ',
        'servingCount' => 4,
        'ownerUserId' => $otherUser->id,
    ]);

    $otherRecipeId = getRecipeIdAfterStore($otherGroup, '他レシピ');

    return getDefaultIngredientCategoryId($testInstance, $otherUser, $otherRecipeId);
}

/**
 * @var User $user
 * @var Group $group
 * @var RecipeCategory $recipeCategory
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

    // テスト用の食材単位を作成
    $this->ingredientUnit = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'g',
        'position' => 'suffix',
        'order' => 0,
        'requires_quantity' => true,
        'is_default' => false
    ]);

    // テスト用の画像を作成（グループスコープ検証は images/groups/{group_id}/ 形式のため合わせる）
    $this->image = Image::create([
        'src' => "/storage/images/groups/{$this->group->id}/test.jpg",
        'width' => 800,
        'height' => 600
    ]);
});


// ===== index() メソッドのテストケース =====

test('3-7-1: 【一覧取得】 正常な料理一覧取得', function () {
    // テスト用の料理をAPIで作成
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '肉じゃが',
        'servingCount' => 2,
        'ownerUserId' => $this->user->id
    ]);

    $response = $this->actingAs($this->user)->get('/recipes');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true
    ]);

    // レスポンス構造の確認（limit/offset 含む）
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'name',
                'lastPlannedDate',
                'cookingTime',
            ]
        ],
        'total',
        'limit',
        'offset'
    ]);
    $response->assertJson(['limit' => 15, 'offset' => 0]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-2: 【一覧取得】 レスポンス形式確認', function () {
    // テスト用の料理をAPIで作成
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
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
                'lastPlannedDate',
                'cookingTime',
                'categories' => [
                    '*' => [
                        'id',
                        'name',
                        'order'
                    ]
                ]
            ]
        ],
        'total',
        'limit',
        'offset'
    ]);
    $response->assertJson(['limit' => 15, 'offset' => 0]);

    // 正しいJSON形式でレスポンスが返されることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-3: 【一覧取得】 sort=name&order=asc で名前昇順にソートされていること', function () {
    // 名前をわかりやすく指定して作成
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'Zzzレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'Aaaレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?sort=name&order=asc');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data[0]['name'])->toBe('Aaaレシピ');
    expect($data[1]['name'])->toBe('Zzzレシピ');
});

test('3-7-4: 【一覧取得】 sort=name&order=desc で名前降順にソートされていること', function () {
    // 名前をわかりやすく指定して作成
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'AaaDESCレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'ZzzDESCレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?sort=name&order=desc');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data[0]['name'])->toBe('ZzzDESCレシピ');
    expect($data[1]['name'])->toBe('AaaDESCレシピ');
});

test('3-7-5: 【一覧取得】 sort=created_at&order=desc で作成日降順にソートされていること', function () {
    // 2 件作成して created_at を明示的に設定
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '古いレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '新しいレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    // created_at を差し替えて順序を確定させる
    DB::table('recipes')->where('name', '古いレシピ')->update(['created_at' => '2020-01-01 00:00:00']);
    DB::table('recipes')->where('name', '新しいレシピ')->update(['created_at' => '2021-01-01 00:00:00']);

    $response = $this->actingAs($this->user)->get('/recipes?sort=created_at&order=desc');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data[0]['name'])->toBe('新しいレシピ');
    expect($data[1]['name'])->toBe('古いレシピ');
});

test('3-7-6: 【一覧取得】 sort=created_at&order=asc で作成日昇順にソートされていること', function () {
    // 2 件作成して created_at を明示的に設定
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '古いレシピASC',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '新しいレシピASC',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    DB::table('recipes')->where('name', '古いレシピASC')->update(['created_at' => '2020-01-01 00:00:00']);
    DB::table('recipes')->where('name', '新しいレシピASC')->update(['created_at' => '2021-01-01 00:00:00']);

    $response = $this->actingAs($this->user)->get('/recipes?sort=created_at&order=asc');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data[0]['name'])->toBe('古いレシピASC');
    expect($data[1]['name'])->toBe('新しいレシピASC');
});

test('3-7-7: 【一覧取得】 sort=last_planned_date&order=desc で献立日降順、NULL は末尾になること', function () {
    // 3 件作成（A: 日付大、B: 日付小、C: NULL）
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '日付大レシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '日付小レシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'NULLレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    // last_planned_date は recipes のカラムではなく、献立（meal_plans.date）の MAX から算出される
    $colorId = (string) Str::uuid();
    DB::table('colors')->insert([
        'id' => $colorId,
        'name' => 'テストカラー',
        'color_code_hex' => '#000000',
        'order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $mealCategoryId = (string) Str::uuid();
    DB::table('meal_categories')->insert([
        'id' => $mealCategoryId,
        'group_id' => $this->group->id,
        'color_id' => $colorId,
        'name' => 'テスト献立カテゴリ',
        'order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $recipeIdLarge = getRecipeIdAfterStore($this->group, '日付大レシピ');
    $recipeIdSmall = getRecipeIdAfterStore($this->group, '日付小レシピ');

    $mealPlanIdLarge = (string) Str::uuid();
    DB::table('meal_plans')->insert([
        'id' => $mealPlanIdLarge,
        'group_id' => $this->group->id,
        'date' => '2022-02-02',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $mealIdLarge = (string) Str::uuid();
    DB::table('meals')->insert([
        'id' => $mealIdLarge,
        'order' => 0,
        'meal_plan_id' => $mealPlanIdLarge,
        'category_id' => $mealCategoryId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('meal_recipe_mappings')->insert([
        'order' => 0,
        'meal_id' => $mealIdLarge,
        'recipe_id' => $recipeIdLarge,
    ]);

    $mealPlanIdSmall = (string) Str::uuid();
    DB::table('meal_plans')->insert([
        'id' => $mealPlanIdSmall,
        'group_id' => $this->group->id,
        'date' => '2021-01-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $mealIdSmall = (string) Str::uuid();
    DB::table('meals')->insert([
        'id' => $mealIdSmall,
        'order' => 0,
        'meal_plan_id' => $mealPlanIdSmall,
        'category_id' => $mealCategoryId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('meal_recipe_mappings')->insert([
        'order' => 0,
        'meal_id' => $mealIdSmall,
        'recipe_id' => $recipeIdSmall,
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?sort=last_planned_date&order=desc');

    $response->assertStatus(200);
    $data = $response->json('data');

    // 先頭は日付大、末尾に NULL が来ることを確認
    expect($data[0]['name'])->toBe('日付大レシピ');
    $last = end($data);
    expect($last['name'])->toBe('NULLレシピ');
});

test('3-7-8: 【一覧取得】 sort=last_planned_date&order=asc で献立日昇順、NULL は末尾になること', function () {
    // 3 件作成（A: 日付小、B: 日付大、C: NULL）
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '日付小ASCレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '日付大ASCレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'NULLASCレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    // last_planned_date は recipes のカラムではなく、献立（meal_plans.date）の MAX から算出される
    $colorId = (string) Str::uuid();
    DB::table('colors')->insert([
        'id' => $colorId,
        'name' => 'テストカラー',
        'color_code_hex' => '#000000',
        'order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $mealCategoryId = (string) Str::uuid();
    DB::table('meal_categories')->insert([
        'id' => $mealCategoryId,
        'group_id' => $this->group->id,
        'color_id' => $colorId,
        'name' => 'テスト献立カテゴリ',
        'order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $recipeIdSmall = getRecipeIdAfterStore($this->group, '日付小ASCレシピ');
    $recipeIdLarge = getRecipeIdAfterStore($this->group, '日付大ASCレシピ');

    $mealPlanIdSmall = (string) Str::uuid();
    DB::table('meal_plans')->insert([
        'id' => $mealPlanIdSmall,
        'group_id' => $this->group->id,
        'date' => '2021-01-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $mealIdSmall = (string) Str::uuid();
    DB::table('meals')->insert([
        'id' => $mealIdSmall,
        'order' => 0,
        'meal_plan_id' => $mealPlanIdSmall,
        'category_id' => $mealCategoryId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('meal_recipe_mappings')->insert([
        'order' => 0,
        'meal_id' => $mealIdSmall,
        'recipe_id' => $recipeIdSmall,
    ]);

    $mealPlanIdLarge = (string) Str::uuid();
    DB::table('meal_plans')->insert([
        'id' => $mealPlanIdLarge,
        'group_id' => $this->group->id,
        'date' => '2022-02-02',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $mealIdLarge = (string) Str::uuid();
    DB::table('meals')->insert([
        'id' => $mealIdLarge,
        'order' => 0,
        'meal_plan_id' => $mealPlanIdLarge,
        'category_id' => $mealCategoryId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('meal_recipe_mappings')->insert([
        'order' => 0,
        'meal_id' => $mealIdLarge,
        'recipe_id' => $recipeIdLarge,
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?sort=last_planned_date&order=asc');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data[0]['name'])->toBe('日付小ASCレシピ');
    $last = end($data);
    expect($last['name'])->toBe('NULLASCレシピ');
});

test('3-7-9: 【一覧取得】 パラメータ未指定時のデフォルト（created_at desc）を確認', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'デフォルト古い',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'デフォルト新しい',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    DB::table('recipes')->where('name', 'デフォルト古い')->update(['created_at' => '2020-01-01 00:00:00']);
    DB::table('recipes')->where('name', 'デフォルト新しい')->update(['created_at' => '2021-01-01 00:00:00']);

    $response = $this->actingAs($this->user)->get('/recipes');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data[0]['name'])->toBe('デフォルト新しい');
});

test('3-7-10: 【一覧取得】 recipe_name を指定して料理名で絞り込みできること', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '肉じゃが',
        'servingCount' => 2,
        'ownerUserId' => $this->user->id
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?recipe_name=カレー');
    $response->assertStatus(200);
    $data = $response->json('data');
    $names = collect($data)->pluck('name')->toArray();
    expect($names)->toContain('カレーライス');
    expect($names)->not->toContain('肉じゃが');
});

test('3-7-11: 【一覧取得】 ingredient_name を指定して食材名で絞り込みできること', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id,  'quantityDisplay' => '1']]
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '肉じゃが',
        'servingCount' => 2,
        'ownerUserId' => $this->user->id,
        'ingredients' => [['name' => 'じゃがいも', 'unitId' => $this->ingredientUnit->id,  'quantityDisplay' => '1']]
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?ingredient_name=玉ねぎ');
    $response->assertStatus(200);
    $data = $response->json('data');
    $names = collect($data)->pluck('name')->toArray();
    expect($names)->toContain('カレーライス');
    expect($names)->not->toContain('肉じゃが');
});

test('3-7-12: 【一覧取得】 category_ids を指定してカテゴリで絞り込みできること（指定したいずれかのカテゴリに属するレシピが返る）', function () {
    $catId1 = (string) \Illuminate\Support\Str::uuid();
    $catId2 = (string) \Illuminate\Support\Str::uuid();
    DB::table('recipe_categories')->insert(['id' => $catId1, 'group_id' => $this->group->id, 'name' => '和食', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('recipe_categories')->insert(['id' => $catId2, 'group_id' => $this->group->id, 'name' => '洋食', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId1]
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'ハンバーグ',
        'servingCount' => 2,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId2]
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?category_ids[]=' . $catId1);
    $response->assertStatus(200);
    $data = $response->json('data');
    $names = collect($data)->pluck('name')->toArray();
    expect($names)->toContain('カレーライス');
    expect($names)->not->toContain('ハンバーグ');
});

test('3-7-13: 【一覧取得】 複数の category_ids を指定して OR 条件で絞り込みできること', function () {
    $catId1 = (string) \Illuminate\Support\Str::uuid();
    $catId2 = (string) \Illuminate\Support\Str::uuid();
    DB::table('recipe_categories')->insert(['id' => $catId1, 'group_id' => $this->group->id, 'name' => '和食', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('recipe_categories')->insert(['id' => $catId2, 'group_id' => $this->group->id, 'name' => '洋食', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId1]
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'ハンバーグ',
        'servingCount' => 2,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId2]
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?category_ids[]=' . $catId1 . '&category_ids[]=' . $catId2);
    $response->assertStatus(200);
    $data = $response->json('data');
    $names = collect($data)->pluck('name')->toArray();
    expect($names)->toContain('カレーライス');
    expect($names)->toContain('ハンバーグ');
});

test('3-7-14: 【一覧取得】 last_planned_date_from / last_planned_date_to を指定して前回献立日で絞り込みできること', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '献立ありレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '献立なしレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    $recipeId = getRecipeIdAfterStore($this->group, '献立ありレシピ');
    $colorId = (string) \Illuminate\Support\Str::uuid();
    DB::table('colors')->insert(['id' => $colorId, 'name' => 'c', 'color_code_hex' => '#000', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $mealCatId = (string) \Illuminate\Support\Str::uuid();
    DB::table('meal_categories')->insert(['id' => $mealCatId, 'group_id' => $this->group->id, 'color_id' => $colorId, 'name' => 'm', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $planId = (string) \Illuminate\Support\Str::uuid();
    DB::table('meal_plans')->insert(['id' => $planId, 'group_id' => $this->group->id, 'date' => '2023-06-15', 'created_at' => now(), 'updated_at' => now()]);
    $mealId = (string) \Illuminate\Support\Str::uuid();
    DB::table('meals')->insert([
        'id' => $mealId,
        'order' => 0,
        'meal_plan_id' => $planId,
        'category_id' => $mealCatId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('meal_recipe_mappings')->insert(['order' => 0, 'meal_id' => $mealId, 'recipe_id' => $recipeId]);

    $response = $this->actingAs($this->user)->get('/recipes?last_planned_date_from=2023-06-01&last_planned_date_to=2023-06-30');
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(collect($data)->pluck('name')->toArray())->toContain('献立ありレシピ');
    expect(collect($data)->pluck('name')->toArray())->not->toContain('献立なしレシピ');
});

test('3-7-15: 【一覧取得】 last_planned_date_from のみ指定して前回献立日で絞り込みできること（その日以降）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '献立ありレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '献立なしレシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    $recipeId = getRecipeIdAfterStore($this->group, '献立ありレシピ');
    $colorId = (string) \Illuminate\Support\Str::uuid();
    DB::table('colors')->insert(['id' => $colorId, 'name' => 'c', 'color_code_hex' => '#000', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $mealCatId = (string) \Illuminate\Support\Str::uuid();
    DB::table('meal_categories')->insert(['id' => $mealCatId, 'group_id' => $this->group->id, 'color_id' => $colorId, 'name' => 'm', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $planId = (string) \Illuminate\Support\Str::uuid();
    DB::table('meal_plans')->insert(['id' => $planId, 'group_id' => $this->group->id, 'date' => '2023-06-15', 'created_at' => now(), 'updated_at' => now()]);
    $mealId = (string) \Illuminate\Support\Str::uuid();
    DB::table('meals')->insert([
        'id' => $mealId,
        'order' => 0,
        'meal_plan_id' => $planId,
        'category_id' => $mealCatId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('meal_recipe_mappings')->insert(['order' => 0, 'meal_id' => $mealId, 'recipe_id' => $recipeId]);

    $response = $this->actingAs($this->user)->get('/recipes?last_planned_date_from=2023-06-01');
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(collect($data)->pluck('name')->toArray())->toContain('献立ありレシピ');
    expect(collect($data)->pluck('name')->toArray())->not->toContain('献立なしレシピ');
});

test('3-7-16: 【一覧取得】 last_planned_date_to のみ指定して前回献立日で絞り込みできること（その日以前）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '過去献立レシピ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '献立なしレシピ2',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    $recipeId = getRecipeIdAfterStore($this->group, '過去献立レシピ');
    $colorId = (string) \Illuminate\Support\Str::uuid();
    DB::table('colors')->insert(['id' => $colorId, 'name' => 'c', 'color_code_hex' => '#000', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $mealCatId = (string) \Illuminate\Support\Str::uuid();
    DB::table('meal_categories')->insert(['id' => $mealCatId, 'group_id' => $this->group->id, 'color_id' => $colorId, 'name' => 'm', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $planId = (string) \Illuminate\Support\Str::uuid();
    DB::table('meal_plans')->insert(['id' => $planId, 'group_id' => $this->group->id, 'date' => '2023-05-10', 'created_at' => now(), 'updated_at' => now()]);
    $mealId = (string) \Illuminate\Support\Str::uuid();
    DB::table('meals')->insert([
        'id' => $mealId,
        'order' => 0,
        'meal_plan_id' => $planId,
        'category_id' => $mealCatId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('meal_recipe_mappings')->insert(['order' => 0, 'meal_id' => $mealId, 'recipe_id' => $recipeId]);

    $response = $this->actingAs($this->user)->get('/recipes?last_planned_date_to=2023-05-31');
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(collect($data)->pluck('name')->toArray())->toContain('過去献立レシピ');
});

test('3-7-17: 【一覧取得】 複数フィルタパラメータを同時に指定した場合、AND 条件で絞り込みできること', function () {
    $catId = (string) \Illuminate\Support\Str::uuid();
    DB::table('recipe_categories')->insert(['id' => $catId, 'group_id' => $this->group->id, 'name' => '和食', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $r1 = $this->actingAs($this->user)->postJson('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId]
    ]);
    $r2 = $this->actingAs($this->user)->postJson('/recipes', [
        'name' => 'カレーパン',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId]
    ]);
    $this->actingAs($this->user)->postJson('/recipes', [
        'name' => 'サラダ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId]
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?recipe_name=カレー&category_ids[]=' . $catId);
    $response->assertStatus(200);
    $data = $response->json('data');
    $names = collect($data)->pluck('name')->toArray();
    expect($names)->toContain('カレーライス');
    expect($names)->toContain('カレーパン');
    expect($names)->not->toContain('サラダ');
});

test('3-7-18: 【一覧取得】 絞り込みパラメータをすべて指定した場合、AND 条件で絞り込みできること', function () {
    $catId = (string) Str::uuid();
    DB::table('recipe_categories')->insert(['id' => $catId, 'group_id' => $this->group->id, 'name' => '和食', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);

    $colorId = (string) Str::uuid();
    DB::table('colors')->insert(['id' => $colorId, 'name' => 'c', 'color_code_hex' => '#000', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $mealCatId = (string) Str::uuid();
    DB::table('meal_categories')->insert(['id' => $mealCatId, 'group_id' => $this->group->id, 'color_id' => $colorId, 'name' => 'm', 'order' => 0, 'created_at' => now(), 'updated_at' => now()]);

    $curryWithOnion = $this->actingAs($this->user)->postJson('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId],
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id,  'quantityDisplay' => '1']],
    ]);
    $curryWithOnion->assertStatus(201);
    $this->actingAs($this->user)->postJson('/recipes', [
        'name' => 'カレーパン',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId],
        'ingredients' => [['name' => 'パン', 'unitId' => $this->ingredientUnit->id,  'quantityDisplay' => '1']],
    ]);
    $this->actingAs($this->user)->postJson('/recipes', [
        'name' => 'サラダ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId],
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id,  'quantityDisplay' => '1']],
    ]);

    $listResponse = $this->actingAs($this->user)->get('/recipes');
    $listResponse->assertStatus(200);
    $recipeId = collect($listResponse->json('data'))->firstWhere('name', 'カレーライス')['id'] ?? getRecipeIdAfterStore($this->group, 'カレーライス');
    $planId = (string) Str::uuid();
    DB::table('meal_plans')->insert(['id' => $planId, 'group_id' => $this->group->id, 'date' => '2023-06-15', 'created_at' => now(), 'updated_at' => now()]);
    $mealId = (string) Str::uuid();
    DB::table('meals')->insert([
        'id' => $mealId,
        'order' => 0,
        'meal_plan_id' => $planId,
        'category_id' => $mealCatId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('meal_recipe_mappings')->insert(['order' => 0, 'meal_id' => $mealId, 'recipe_id' => $recipeId]);

    $response = $this->actingAs($this->user)->get('/recipes?' . implode('&', [
        'recipe_name=カレー',
        'ingredient_name=玉ねぎ',
        'category_ids[]=' . $catId,
        'last_planned_date_from=2023-06-01',
        'last_planned_date_to=2023-06-30',
    ]));
    $response->assertStatus(200);
    $data = $response->json('data');
    $names = collect($data)->pluck('name')->toArray();
    expect($names)->toContain('カレーライス');
    expect($names)->not->toContain('カレーパン');
    expect($names)->not->toContain('サラダ');
});

test('3-7-19: 【一覧取得】 limit/offset 指定時に正しい件数・位置で取得できること', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->actingAs($this->user)->post('/recipes', [
            'name' => "レシピ{$i}",
            'servingCount' => 1,
            'ownerUserId' => $this->user->id
        ]);
    }

    $response = $this->actingAs($this->user)->get('/recipes?limit=2&offset=1');
    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    $response->assertJson(['total' => 5, 'limit' => 2, 'offset' => 1]);
});

test('3-7-20: 【一覧取得】 limit のみ指定時にデフォルト offset=0 で取得できること', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'レシピ1',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'レシピ2',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id
    ]);

    $response = $this->actingAs($this->user)->get('/recipes?limit=1');
    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    $response->assertJson(['limit' => 1, 'offset' => 0]);
});

test('3-7-21: 【一覧取得】 offset のみ指定時にデフォルト limit=15 で取得できること', function () {
    for ($i = 1; $i <= 20; $i++) {
        $this->actingAs($this->user)->post('/recipes', [
            'name' => "レシピ{$i}",
            'servingCount' => 1,
            'ownerUserId' => $this->user->id
        ]);
    }

    $response = $this->actingAs($this->user)->get('/recipes?offset=10');
    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(10);
    $response->assertJson(['limit' => 15, 'offset' => 10, 'total' => 20]);
});

test('3-7-22: 【一覧取得】 未認証ユーザー', function () {
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

test('3-7-23: 【一覧取得】 グループが存在しない', function () {
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

test('3-7-24: 【一覧取得】 バリデーションエラー（limit が整数でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?limit=abc');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['limit']);
});

test('3-7-25: 【一覧取得】 バリデーションエラー（limit が 1 未満）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?limit=0');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['limit']);
});

test('3-7-26: 【一覧取得】 バリデーションエラー（limit が 100 超過）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?limit=101');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['limit']);
});

test('3-7-27: 【一覧取得】 バリデーションエラー（offset が整数でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?offset=xyz');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['offset']);
});

test('3-7-28: 【一覧取得】 バリデーションエラー（offset が 0 未満）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?offset=-1');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['offset']);
});

test('3-7-29: 【一覧取得】 バリデーションエラー（sort が文字列でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?sort[]=created_at');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sort']);
});

test('3-7-30: 【一覧取得】 バリデーションエラー（sort が不正な値）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?sort=invalid_column');
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false
    ]);
    $response->assertJsonValidationErrors(['sort']);
});

test('3-7-31: 【一覧取得】 バリデーションエラー（order が文字列でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?order[]=asc');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order']);
});

test('3-7-32: 【一覧取得】 バリデーションエラー（order が不正な値）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?order=invalid');
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false
    ]);
    $response->assertJsonValidationErrors(['order']);
});

test('3-7-33: 【一覧取得】 バリデーションエラー（recipe_name が文字列でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?recipe_name[]=カレー');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['recipe_name']);
});

test('3-7-34: 【一覧取得】 バリデーションエラー（recipe_name が 255 文字超過）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?' . http_build_query([
        'recipe_name' => str_repeat('a', 256),
    ]));
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['recipe_name']);
});

test('3-7-35: 【一覧取得】 バリデーションエラー（ingredient_name が文字列でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?ingredient_name[]=玉ねぎ');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredient_name']);
});

test('3-7-36: 【一覧取得】 バリデーションエラー（ingredient_name が 255 文字超過）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?' . http_build_query([
        'ingredient_name' => str_repeat('a', 256),
    ]));
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredient_name']);
});

test('3-7-37: 【一覧取得】 バリデーションエラー（category_ids が配列でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?category_ids=not_array');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['category_ids']);
});

test('3-7-38: 【一覧取得】 バリデーションエラー（category_ids.* が UUID 形式でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?category_ids[]=not-a-uuid');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['category_ids.0']);
});

test('3-7-39: 【一覧取得】 バリデーションエラー（category_ids.* が存在しない ID）', function () {
    $nonExistentId = (string) Str::uuid();
    $response = $this->actingAs($this->user)->get('/recipes?category_ids[]=' . $nonExistentId);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['category_ids.0']);
});

test('3-7-40: 【一覧取得】 バリデーションエラー（last_planned_date_from が日付形式でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?last_planned_date_from=invalid-date');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['last_planned_date_from']);
});

test('3-7-41: 【一覧取得】 バリデーションエラー（last_planned_date_to が日付形式でない）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?last_planned_date_to=invalid-date');
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['last_planned_date_to']);
});

test('3-7-42: 【一覧取得】 バリデーションエラー（last_planned_date_to が last_planned_date_from より前）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?' . http_build_query([
        'last_planned_date_from' => '2024-06-10',
        'last_planned_date_to' => '2024-06-01',
    ]));
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['last_planned_date_to']);
});

test('3-7-43: 【一覧取得】 データベース接続エラー', function () {
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

test('3-7-44: 【一覧取得】 RecipeService 例外', function () {
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


// ===== store() メソッドのテストケース =====

test('3-7-45: 【新規作成】 正常な料理作成', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を作成しました。'
    ]);

    $recipeId = $response->json('data.id');
    expect($recipeId)->toBeString();

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('recipes', [
        'id' => $recipeId,
        'group_id' => $this->group->id,
        'owner_user_id' => $this->user->id,
        'name' => 'カレーライス',
        'status' => 'limited',
        'published_recipe_id' => null
    ]);

    // レスポンス構造の確認（store は success、message、data.id）
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-46: 【新規作成】 最小限のデータで料理作成', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を作成しました。'
    ]);

    $recipeId = $response->json('data.id');
    expect($recipeId)->toBeString();

    // データベースに保存されていることを確認
    $this->assertDatabaseHas('recipes', [
        'id' => $recipeId,
        'group_id' => $this->group->id,
        'owner_user_id' => $this->user->id,
        'name' => 'カレーライス',
        'status' => 'limited',
        'published_recipe_id' => null
    ]);

    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
        ]
    ]);
});

test('3-7-47: 【新規作成】 料理にカテゴリを紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => [$this->recipeCategory->id],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // カテゴリが正しく紐づけられていることを確認（レスポンスの data.id を使用）
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('categories')->find($recipeId);
    expect($recipe->categories)->toHaveCount(1);
    expect($recipe->categories[0]->id)->toBe($this->recipeCategory->id);
});

test('3-7-48: 【新規作成】 料理に食材を紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100'
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 食材が正しく紐づけられていることを確認（レスポンスの data.id を使用）
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
});

test('3-7-49: 【新規作成】 最小限の必須フィールドのみで食材を紐づけ', function () {
    // requires_quantity=falseの単位を作成
    $unitWithoutQuantityRequired = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '個',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false,
        'is_default' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $unitWithoutQuantityRequired->id,
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 食材が正しく紐づけられていることを確認（レスポンスの data.id を使用）
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
});

test('3-7-50: 【新規作成】 料理に手順を紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'order' => 0
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 手順が正しく紐づけられていることを確認（レスポンスの data.id を使用）
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('steps')->find($recipeId);
    expect($recipe->steps)->toHaveCount(1);
    expect($recipe->steps[0]->order)->toBe(0);
    expect($recipe->steps[0]->recipe_id)->toBe($recipeId);
});

test('3-7-51: 【新規作成】 最小限の必須フィールドのみで手順を紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'order' => 0
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 手順が正しく紐づけられていることを確認（レスポンスの data.id を使用）
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('steps')->find($recipeId);
    expect($recipe->steps)->toHaveCount(1);
    expect($recipe->steps[0]->instruction)->toBe('玉ねぎを切る');
    expect($recipe->steps[0]->order)->toBe(0);
    expect($recipe->steps[0]->recipe_id)->toBe($recipeId);
});

test('3-7-52: 【新規作成】 料理に画像を紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    // 画像が正しく紐づけられていることを確認（レスポンスの data.id を使用）
    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);
});

test('3-7-53: 【新規作成】 requires_quantity=true の食材単位で数量指定', function () {
    // requires_quantity=true の食材単位を作成
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true,
        'is_default' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            'quantityDisplay' => '2.5'
        ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を作成しました。'
    ]);

    // 作成した料理を show API で取得して内容を検証（作成レスポンスの data.id を使用）
    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);
    $responseData = $showResponse->json('data');
    expect($responseData['name'])->toBe('カレーライス');
    expect($responseData['ingredients'])->toHaveCount(1);
    expect($responseData['ingredients'][0]['name'])->toBe('玉ねぎ');
    expect($responseData['ingredients'][0]['unit']['id'])->toBe($unitWithQuantity->id);
    expect($responseData['ingredients'][0]['unit']['name'])->toBe('kg');
    expect($responseData['ingredients'][0]['quantity'])->toBe(2.5);
});

test('3-7-54: 【新規作成】 requires_quantity=false の食材単位で数量指定', function () {
    // requires_quantity=false の食材単位を作成
    $unitWithoutQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '適量',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false,
        'is_default' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithoutQuantity->id,
            'quantityDisplay' => '2.5'
        ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を作成しました。'
    ]);
    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);
    $responseData = $showResponse->json('data');
    expect($responseData['name'])->toBe('カレーライス');
    $this->assertNull($responseData['ingredients'][0]['quantity'] ?? null);

    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients[0]->pivot->quantity)->toBeNull();
});

test('3-7-55: 【新規作成】 requires_quantity=false の食材単位で数量省略', function () {
    // requires_quantity=false の食材単位を作成
    $unitWithoutQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '適量',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false,
        'is_default' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithoutQuantity->id,
            // quantityDisplay を省略
        ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を作成しました。'
    ]);
    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);
    $responseData = $showResponse->json('data');
    expect($responseData['name'])->toBe('カレーライス');
    $this->assertNull($responseData['ingredients'][0]['quantity'] ?? null);
});

test('3-7-56: 【新規作成】 すべての項目を含む料理作成', function () {
    // 追加の画像を作成（手順用）
    $stepImage = Image::create([
        'src' => "/storage/images/groups/{$this->group->id}/step.jpg",
        'width' => 800,
        'height' => 600
    ]);

    $data = [
        'name' => 'スパイスカレー',
        'servingCount' => 4,
        'url' => 'https://example.com/recipe',
        'memo' => 'これはテスト用のメモです',
        'thumbnailId' => $this->image->id,
        'cookingTime' => 30,
        'categoryIds' => [$this->recipeCategory->id],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '200',
                'order' => 0
            ],
            [
                'name' => 'にんじん',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100',
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
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(スパイスカレー)を作成しました。'
    ]);

    $recipeId = $response->json('data.id');
    expect($recipeId)->toBeString();

    // データベースにすべての項目が正しく保存されていることを確認
    $this->assertDatabaseHas('recipes', [
        'id' => $recipeId,
        'name' => 'スパイスカレー',
        'serving_count' => 4,
        'url' => 'https://example.com/recipe',
        'memo' => 'これはテスト用のメモです',
        'cooking_time' => 30,
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

    // レスポンス構造の確認（store は success、message、data.id）
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-57: 【新規作成】 servingCount が null でも正常に作成できる', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => null,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);
    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);
    $this->assertNull($showResponse->json('data.servingCount'));

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-58: 【新規作成】 同一材料名で単位が異なる行は複数登録できる', function () {
    $secondUnit = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '合',
        'position' => 'suffix',
        'order' => 2,
        'requires_quantity' => true,
        'is_default' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '米',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100',
            ],
            [
                'name' => '米',
                'unitId' => $secondUnit->id,
                'quantityDisplay' => '1',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(2);
});

test('3-7-59: 【新規作成】 quantityDisplay に分数表記（1/2）を指定して保存・取得できる', function () {
    $data = [
        'name' => '分数テスト',
        'servingCount' => 2,
        'ingredients' => [[
            'name' => '塩',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => '1/2',
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);

    $ingredient = $showResponse->json('data.ingredients.0');
    expect($ingredient['quantity'])->toBe(0.5);
    expect($ingredient['quantityDisplay'])->toBe('1/2');

    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients[0]->pivot->quantity)->toBe(0.5);
    expect($recipe->ingredients[0]->pivot->quantity_display)->toBe('1/2');
});

test('3-7-60: 【新規作成】 quantityDisplay に小数表記（0.5）を指定して保存・取得できる', function () {
    $data = [
        'name' => '小数テスト',
        'servingCount' => 2,
        'ingredients' => [[
            'name' => '塩',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => '0.5',
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);

    $ingredient = $showResponse->json('data.ingredients.0');
    expect($ingredient['quantity'])->toBe(0.5);
    expect($ingredient['quantityDisplay'])->toBe('0.5');
});

test('3-7-61: 【新規作成】 quantityDisplay のみ指定して保存・取得できる', function () {
    $data = [
        'name' => 'displayのみテスト',
        'servingCount' => 2,
        'ingredients' => [[
            'name' => '塩',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => '200',
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);

    $ingredient = $showResponse->json('data.ingredients.0');
    expect($ingredient['quantity'])->toBe(200);
    expect($ingredient['quantityDisplay'])->toBe('200');

    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients[0]->pivot->quantity)->toBe(200.0);
    expect($recipe->ingredients[0]->pivot->quantity_display)->toBe('200');
});

test('3-7-62: 【新規作成】 id 指定で既存食材を紐づけ（name 省略）', function () {
    $ingredient = Ingredient::create([
        'group_id' => $this->group->id,
        'name' => '玉ねぎ',
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'id' => $ingredient->id,
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => '100',
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    $recipeId = $response->json('data.id');
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
    expect($recipe->ingredients[0]->id)->toBe($ingredient->id);
    expect($recipe->ingredients[0]->name)->toBe('玉ねぎ');
});

test('3-7-63: 【新規作成】 categoryId/categoryName 省略時はデフォルトカテゴリーにフォールバック', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100',
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $defaultCategory = collect($showResponse->json('data.ingredientCategories'))
        ->firstWhere('isDefault', true);

    expect($showResponse->json('data.ingredients.0.categoryId'))->toBe($defaultCategory['id']);
});

test('3-7-64: 【新規作成】 ingredientCategories を新規作成し categoryName で食材を紐づけ', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => true, 'order' => 0],
        ],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => '野菜',
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $vegetableCategory = collect($showResponse->json('data.ingredientCategories'))
        ->first(fn ($category) => $category['name'] === '野菜');

    expect($showResponse->json('data.ingredients.0.categoryId'))->toBe($vegetableCategory['id']);
    expect($showResponse->json('data.ingredients.0.name'))->toBe('玉ねぎ');
});

test('3-7-65: 【新規作成】 ingredientCategories に isDefault: true が1つ含まれている場合、そのカテゴリーが is_default=true で作成される', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => true, 'order' => 0],
            ['name' => '調味料', 'isDefault' => false, 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(201);

    $recipeId = $response->json('data.id');
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $vegetableCategory = collect($showResponse->json('data.ingredientCategories'))
        ->first(fn ($category) => $category['name'] === '野菜');
    $seasoningCategory = collect($showResponse->json('data.ingredientCategories'))
        ->first(fn ($category) => $category['name'] === '調味料');

    expect($vegetableCategory['isDefault'])->toBeTrue();
    expect($seasoningCategory['isDefault'])->toBeFalse();
});

test('3-7-66: 【新規作成】 未認証ユーザー', function () {
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

test('3-7-67: 【新規作成】 グループが存在しない', function () {
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

test('3-7-68: 【新規作成】 バリデーションエラー（name 未入力）', function () {
    $data = [
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
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

test('3-7-69: 【新規作成】 バリデーションエラー（name が文字列でない）', function () {
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

test('3-7-70: 【新規作成】 バリデーションエラー（name が 255 文字超過）', function () {
    $data = [
        'name' => str_repeat('a', 256),
        'ownerUserId' => $this->user->id
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

test('3-7-71: 【新規作成】 バリデーションエラー（url が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'url' => 123,
        'ownerUserId' => $this->user->id
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

test('3-7-72: 【新規作成】 バリデーションエラー（url が 2048 文字超過）', function () {
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

test('3-7-73: 【新規作成】 バリデーションエラー（thumbnailId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'thumbnailId' => 'invalid-uuid',
        'ownerUserId' => $this->user->id
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

test('3-7-74: 【新規作成】 バリデーションエラー（categoryIds が配列でない）', function () {
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

test('3-7-75: 【新規作成】 バリデーションエラー（categoryIds.* が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'categoryIds' => ['invalid-uuid'],
        'ownerUserId' => $this->user->id
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

test('3-7-76: 【新規作成】 バリデーションエラー（categoryIds.* 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'categoryIds' => [null],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryIds.0']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('categoryIds.*は必ず指定してください。', $responseData['errors']['categoryIds.0']);

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

test('3-7-77: 【新規作成】 バリデーションエラー（ingredientCategories が配列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => 'not_array',
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategoriesは配列でなくてはなりません。', $responseData['errors']['ingredientCategories']);
});

test('3-7-78: 【新規作成】 バリデーションエラー（ingredientCategories.\*.id が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => 'invalid-uuid', 'name' => '野菜', 'order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.id']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.idに有効なUUIDを指定してください。', $responseData['errors']['ingredientCategories.0.id']);
});

test('3-7-79: 【新規作成】 バリデーションエラー（ingredientCategories.\*.name 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.name']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.nameは必ず指定してください。', $responseData['errors']['ingredientCategories.0.name']);
});

test('3-7-80: 【新規作成】 バリデーションエラー（ingredientCategories.\*.name が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => 123, 'order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.name']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.nameは文字列を指定してください。', $responseData['errors']['ingredientCategories.0.name']);
});

test('3-7-81: 【新規作成】 バリデーションエラー（ingredientCategories.\*.name が 255 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => str_repeat('a', 256), 'order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.name']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.nameは、255文字以内で指定してください。', $responseData['errors']['ingredientCategories.0.name']);
});

test('3-7-82: 【新規作成】 バリデーションエラー（ingredientCategories.\*.order 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜'],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.order']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.orderは必ず指定してください。', $responseData['errors']['ingredientCategories.0.order']);
});

test('3-7-83: 【新規作成】 バリデーションエラー（ingredientCategories.\*.order が整数でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'order' => 'not_integer'],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.order']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.orderは整数で指定してください。', $responseData['errors']['ingredientCategories.0.order']);
});

test('3-7-84: 【新規作成】 バリデーションエラー（ingredientCategories.\*.order が負の値）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'order' => -1],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.order']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.orderには、0以上の数字を指定してください。', $responseData['errors']['ingredientCategories.0.order']);
});

test('3-7-85: 【新規作成】 バリデーションエラー（ingredientCategories.\*.name が重複）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'order' => 0],
            ['name' => '野菜', 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.1.name']);
    $responseData = $response->json();
    $this->assertContains('材料カテゴリ名が重複しています。', $responseData['errors']['ingredientCategories.1.name']);
});

test('3-7-86: 【新規作成】 バリデーションエラー（ingredientCategories.\*.id が重複）', function () {
    $categoryId = (string) Str::uuid();

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => $categoryId, 'name' => '野菜', 'order' => 0],
            ['id' => $categoryId, 'name' => '肉', 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.1.id']);
    $responseData = $response->json();
    $this->assertContains('材料カテゴリIDが重複しています。', $responseData['errors']['ingredientCategories.1.id']);
});

test('3-7-87: 【新規作成】 バリデーションエラー（ingredientCategories に isDefault: true が0個）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => false, 'order' => 0],
            ['name' => '調味料', 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategoriesには isDefault が true の項目を1つだけ指定してください。', $responseData['errors']['ingredientCategories']);
});

test('3-7-88: 【新規作成】 バリデーションエラー（ingredientCategories に isDefault: true が2個以上）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => true, 'order' => 0],
            ['name' => '調味料', 'isDefault' => true, 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategoriesには isDefault が true の項目を1つだけ指定してください。', $responseData['errors']['ingredientCategories']);
});

test('3-7-89: 【新規作成】 バリデーションエラー（ingredients が配列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => 'not_array',
        'ownerUserId' => $this->user->id
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
test('3-7-90: 【新規作成】 バリデーションエラー（ingredients が件数上限超過）', function () {
    $ingredients = [];
    for ($i = 0; $i < ValidationLimits::RECIPE_INGREDIENTS_MAX + 1; $i++) {
        $ingredients[] = [
            'name' => "食材{$i}",
            'unitId' => $this->ingredientUnit->id,
        ];
    }

    $response = $this->actingAs($this->user)->postJson('/recipes', [
        'name' => 'カレーライス',
        'ownerUserId' => $this->user->id,
        'ingredients' => $ingredients,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients']);

    $responseData = $response->json();
    $this->assertContains(
        'ingredientsは' . ValidationLimits::RECIPE_INGREDIENTS_MAX . '個以下指定してください。',
        $responseData['errors']['ingredients']
    );
});


test('3-7-91: 【新規作成】 バリデーションエラー（ingredients.\*.id が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'id' => 'invalid-uuid',
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-92: 【新規作成】 バリデーションエラー（ingredients.\*.id と ingredients.\*.name が両方未指定）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'unitId' => $this->ingredientUnit->id,
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('材料名にはidまたはnameのいずれかを指定してください。', $responseData['errors']['ingredients.0.name']);

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

test('3-7-93: 【新規作成】 バリデーションエラー（ingredients.\*.name が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => 123,
                'unitId' => $this->ingredientUnit->id,
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-94: 【新規作成】 バリデーションエラー（ingredients.\*.name が 255 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => str_repeat('a', 256),
                'unitId' => $this->ingredientUnit->id,
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-95: 【新規作成】 バリデーションエラー（ingredients.\*.unitId 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-96: 【新規作成】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => 'invalid-uuid',
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-97: 【新規作成】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => 'invalid-uuid'
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-98: 【新規作成】 バリデーションエラー（ingredients.\*.categoryName が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => true, 'order' => 0],
        ],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => 123,
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryName']);
    $responseData = $response->json();
    $this->assertContains('ingredients.*.categoryNameは文字列を指定してください。', $responseData['errors']['ingredients.0.categoryName']);
});

test('3-7-99: 【新規作成】 バリデーションエラー（ingredients.\*.categoryName が 255 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => true, 'order' => 0],
        ],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => str_repeat('a', 256),
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryName']);
    $responseData = $response->json();
    $this->assertContains('ingredients.*.categoryNameは、255文字以内で指定してください。', $responseData['errors']['ingredients.0.categoryName']);
});

test('3-7-100: 【新規作成】 バリデーションエラー（ingredients.\*.categoryName が ingredientCategories に含まれない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => true, 'order' => 0],
        ],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => '存在しないカテゴリ',
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryName']);
    $responseData = $response->json();
    $this->assertContains('指定されたcategoryNameはingredientCategoriesに含まれていません。', $responseData['errors']['ingredients.0.categoryName']);
});

test('3-7-101: 【新規作成】 バリデーションエラー（ingredients.\*.quantityDisplay が parse 不可）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => 'abc',
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->postJson('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);

    $responseData = $response->json();
    $this->assertContains('数量は整数・小数・分数で入力してください（例: 2、1.5、1/2、1と1/2）', $responseData['errors']['ingredients.0.quantityDisplay']);
});

test('3-7-102: 【新規作成】 バリデーションエラー（ingredients.\*.quantityDisplay が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => ['not' => 'string'],
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->postJson('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);

    $responseData = $response->json();
    $this->assertContains('ingredients.*.quantityDisplayは文字列を指定してください。', $responseData['errors']['ingredients.0.quantityDisplay']);

    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.quantityDisplay',
        ],
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-103: 【新規作成】 バリデーションエラー（ingredients.\*.quantityDisplay が 50 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => str_repeat('a', 51),
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->postJson('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);

    $responseData = $response->json();
    $this->assertContains('ingredients.*.quantityDisplayは、50文字以内で指定してください。', $responseData['errors']['ingredients.0.quantityDisplay']);

    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.quantityDisplay',
        ],
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-104: 【新規作成】 バリデーションエラー（ingredients.\*.order が整数でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'order' => 'not_integer'
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-105: 【新規作成】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で数量省略）', function () {
    // requires_quantity=true の食材単位を作成
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true,
        'is_default' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            // quantityDisplay を省略
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);
});

test('3-7-106: 【新規作成】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で quantityDisplay が空文字）', function () {
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true,
        'is_default' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            'quantityDisplay' => '',
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);
    $responseData = $response->json();
    $this->assertContains('選択した単位ではingredients.*.quantityDisplayの指定が必須です。', $responseData['errors']['ingredients.0.quantityDisplay']);
});

test('3-7-107: 【新規作成】 バリデーションエラー（ingredients.\*.order が負の値）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'order' => -1
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-108: 【新規作成】 バリデーションエラー（steps が配列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => 'not_array',
        'ownerUserId' => $this->user->id
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

test('3-7-109: 【新規作成】 バリデーションエラー（steps.\*.id が UUID 形式でない）', function () {
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

test('3-7-110: 【新規作成】 バリデーションエラー（steps.\*.instruction 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'order' => 0
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-111: 【新規作成】 バリデーションエラー（steps.\*.instruction が文字列でない）', function () {
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

test('3-7-112: 【新規作成】 バリデーションエラー（steps.\*.instruction が 255 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'instruction' => str_repeat('a', 256),
                'order' => 0
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-113: 【新規作成】 バリデーションエラー（steps.\*.imageId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'imageId' => 'invalid-uuid',
                'order' => 0
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-114: 【新規作成】 バリデーションエラー（steps.\*.order 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'steps' => [
            [
                'instruction' => '玉ねぎを切る'
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-115: 【新規作成】 バリデーションエラー（steps.\*.order が整数でない）', function () {
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

test('3-7-116: 【新規作成】 バリデーションエラー（steps.\*.order が負の値）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'order' => -1
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-117: 【新規作成】 バリデーションエラー（memo が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'memo' => 123,
        'ownerUserId' => $this->user->id
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

test('3-7-118: 【新規作成】 バリデーションエラー（memo が 255 文字超過）', function () {
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

test('3-7-119: 【新規作成】 バリデーションエラー（servingCount が整数でない）', function () {
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

test('3-7-120: 【新規作成】 バリデーションエラー（servingCount が 1 未満）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 0,
        'ownerUserId' => $this->user->id
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

test('3-7-121: 【新規作成】 バリデーションエラー（cookingTime が整数でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'cookingTime' => 'abc',
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cookingTime']);
    $responseData = $response->json();
    $this->assertContains('cookingTimeは整数で指定してください。', $responseData['errors']['cookingTime']);
});

test('3-7-122: 【新規作成】 バリデーションエラー（cookingTime が 0 未満）', function () {
    $data = [
        'name' => 'カレーライス',
        'cookingTime' => -1,
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cookingTime']);
    $responseData = $response->json();
    $this->assertContains('cookingTimeには、0以上の数字を指定してください。', $responseData['errors']['cookingTime']);
});

test('3-7-123: 【新規作成】 バリデーションエラー（ownerUserId 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ownerUserId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ownerUserIdは必ず指定してください。', $responseData['errors']['ownerUserId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ownerUserId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-124: 【新規作成】 バリデーションエラー（ownerUserId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => 'invalid-uuid'
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ownerUserId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ownerUserIdに有効なUUIDを指定してください。', $responseData['errors']['ownerUserId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ownerUserId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-125: 【新規作成】 バリデーションエラー（ingredients 同一 name・unitId・category の組み合わせが重複）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '米',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '1',
            ],
            [
                'name' => '米',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '2',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.1.name']);

    $responseData = $response->json();
    $this->assertContains('同じ材料名・単位・カテゴリーの組み合わせが重複しています。', $responseData['errors']['ingredients.1.name']);
});

test('3-7-126: 【新規作成】 存在しない食材単位 ID 指定', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => '00000000-0000-0000-0000-000000000000',
                'quantityDisplay' => '100'
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-127: 【新規作成】 他グループの食材単位 ID 指定', function () {
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
        'order' => 0,
        'is_default' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $otherUnit->id,
                'quantityDisplay' => '100'
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-128: 【新規作成】 存在しない食材カテゴリ ID 指定', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => '00000000-0000-0000-0000-000000000000',
                'quantityDisplay' => '100'
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-129: 【新規作成】 他レシピの食材カテゴリ ID 指定', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    // 他レシピの食材カテゴリ ID を取得
    $otherCategoryId = getOtherRecipeIngredientCategoryId($this, $otherUser, $otherGroup);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $otherCategoryId,
                'quantityDisplay' => '100'
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-130: 【新規作成】 レシピ内に存在しない categoryName 指定', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => '野菜',
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された食材カテゴリーが見つかりませんでした。',
    ]);
});

test('3-7-131: 【新規作成】 存在しない料理カテゴリ ID 指定', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => ['00000000-0000-0000-0000-000000000000'],
        'ownerUserId' => $this->user->id
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

test('3-7-132: 【新規作成】 他グループの料理カテゴリ ID 指定', function () {
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
        'categoryIds' => [$otherCategory->id],
        'ownerUserId' => $this->user->id
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

test('3-7-133: 【新規作成】 存在しない画像 ID 指定（thumbnailId）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => '00000000-0000-0000-0000-000000000000',
        'ownerUserId' => $this->user->id
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

test('3-7-134: 【新規作成】 他グループの画像 ID 指定（thumbnailId）', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    // 他グループの画像を作成
    $otherImage = Image::create([
        'src' => "/storage/images/groups/{$otherGroup->id}/other_test.jpg",
        'width' => 800,
        'height' => 600
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $otherImage->id,
        'ownerUserId' => $this->user->id
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

test('3-7-135: 【新規作成】 存在しない画像 ID 指定（steps.\*.imageId）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '玉ねぎを切る',
                'imageId' => '00000000-0000-0000-0000-000000000000',
                'order' => 0
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-136: 【新規作成】 他グループの画像 ID 指定（steps.\*.imageId）', function () {
    // 他グループのユーザーを作成
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    // 他グループの画像を作成
    $otherImage = Image::create([
        'src' => "/storage/images/groups/{$otherGroup->id}/other_test.jpg",
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
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-137: 【新規作成】 存在しない食材 ID 指定', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'id' => '00000000-0000-0000-0000-000000000001',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);
});

test('3-7-138: 【新規作成】 他グループの食材 ID 指定', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    $otherIngredient = Ingredient::create([
        'group_id' => $otherGroup->id,
        'name' => '他グループの玉ねぎ',
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'id' => $otherIngredient->id,
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);
});

test('3-7-139: 【新規作成】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
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

test('3-7-140: 【新規作成】 料理作成失敗', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Create failed'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
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

test('3-7-141: 【新規作成】 食材紐づけ失敗', function () {
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
                'quantityDisplay' => '100',
                'unitId' => $this->ingredientUnit->id,
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-142: 【新規作成】 手順紐づけ失敗', function () {
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
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-143: 【新規作成】 画像紐づけ失敗', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Image attachment failed'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id,
        'ownerUserId' => $this->user->id
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

test('3-7-144: 【新規作成】 ImageService 例外', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('ImageService exception'));
    });

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id,
        'ownerUserId' => $this->user->id
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


// ===== show() メソッドのテストケース =====

test('3-7-145: 【詳細取得】 正常な料理詳細取得', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

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

test('3-7-146: 【詳細取得】 すべての項目を含む料理詳細取得', function () {
    // 追加の画像を作成（手順用）
    $stepImage = Image::create([
        'src' => "/storage/images/groups/{$this->group->id}/step.jpg",
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
                'quantityDisplay' => '200',
                'order' => 0
            ],
            [
                'name' => 'にんじん',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100',
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
        ],
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group, 'スパイスカレー');

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

test('3-7-147: 【詳細取得】 quantity のみ保存された既存データは quantityDisplay を補完して返る', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => '既存データテスト',
        'servingCount' => 2,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => '0.5',
        ]],
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = $createResponse->json('data.id');

    DB::table('recipe_ingredient_mappings')
        ->where('recipe_id', $recipeId)
        ->update(['quantity_display' => null]);

    $response = $this->actingAs($this->user)->get("/recipes/{$recipeId}");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'ingredients' => [
                ['quantityDisplay'],
            ],
        ],
    ]);
    expect($response->json('data.ingredients.0.quantity'))->toBe(0.5);
    expect($response->json('data.ingredients.0.quantityDisplay'))->toBe('1/2');

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-148: 【詳細取得】 未認証ユーザー', function () {
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

test('3-7-149: 【詳細取得】 グループが存在しない', function () {
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

test('3-7-150: 【詳細取得】 存在しない料理詳細取得', function () {
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

test('3-7-151: 【詳細取得】 他グループの料理詳細取得', function () {
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

test('3-7-152: 【詳細取得】 データベース接続エラー', function () {
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


// ===== update() メソッドのテストケース =====

test('3-7-153: 【更新】 正常な料理更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'スパイスカレー',
        'servingCount' => 6,
        'ownerUserId' => $this->user->id
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

    // レスポンス構造の確認（update は success + message のみ）
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-154: 【更新】 最小限のデータで料理更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'スパイスカレー',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(スパイスカレー)を更新しました。'
    ]);
});

test('3-7-155: 【更新】 料理のカテゴリ更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => [$this->recipeCategory->id],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // カテゴリが正しく更新されていることを確認
    $recipe = Recipe::with('categories')->find($recipeId);
    expect($recipe->categories)->toHaveCount(1);
    expect($recipe->categories[0]->id)->toBe($this->recipeCategory->id);
});

test('3-7-156: 【更新】 料理の食材更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '200'
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 食材が正しく更新されていることを確認
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
});

test('3-7-157: 【更新】 最小限の必須フィールドのみで食材を更新', function () {
    // requires_quantity=falseの単位を作成
    $unitWithoutQuantityRequired = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '個',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false,
        'is_default' => false
    ]);

    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $unitWithoutQuantityRequired->id,
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 食材が正しく更新されていることを確認
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
});

test('3-7-158: 【更新】 料理の手順更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '野菜を炒める',
                'order' => 0
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 手順が正しく更新されていることを確認
    $recipe = Recipe::with('steps')->find($recipeId);
    expect($recipe->steps)->toHaveCount(1);
    expect($recipe->steps[0]->order)->toBe(0);
});

test('3-7-159: 【更新】 最小限の必須フィールドのみで手順を更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [
            [
                'instruction' => '野菜を炒める',
                'order' => 0
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
});

test('3-7-160: 【更新】 手順の画像を削除（imageId が null）', function () {
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
        ],
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

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
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 画像が削除されていることを確認
    $recipe = Recipe::with('steps.images')->find($recipeId);
    expect($recipe->steps->first()->images)->toHaveCount(0);
});

test('3-7-161: 【更新】 手順の画像を削除（imageId キーが存在しない）', function () {
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
        ],
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

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
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 画像が削除されていることを確認
    $recipe = Recipe::with('steps.images')->find($recipeId);
    expect($recipe->steps->first()->images)->toHaveCount(0);
});

test('3-7-162: 【更新】 料理の画像更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // 画像が正しく更新されていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);
});

test('3-7-163: 【更新】 サムネイルを削除（thumbnailId が null）', function () {
    // テスト用の料理をAPIで作成し、サムネイルを紐づけ
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    // サムネイルが紐づいていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);

    // サムネイルを削除（thumbnailIdをnullに指定）
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => null,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // サムネイルが削除されていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(0);
});

test('3-7-164: 【更新】 サムネイルを削除（thumbnailId キーが存在しない）', function () {
    // テスト用の料理をAPIで作成し、サムネイルを紐づけ
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $this->image->id,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    // サムネイルが紐づいていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(1);

    // サムネイルを削除（thumbnailIdキーを省略）
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // サムネイルが削除されていることを確認
    $recipe = Recipe::with('thumbnails')->find($recipeId);
    expect($recipe->thumbnails)->toHaveCount(0);
});

test('3-7-165: 【更新】 更新成功メッセージの確認', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'スパイスカレー',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('料理/レシピ(スパイスカレー)を更新しました。');
});

test('3-7-166: 【更新】 requires_quantity=true の食材単位で数量指定', function () {
    // requires_quantity=true の食材単位を作成
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true,
        'is_default' => false
    ]);

    // 料理を作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            'quantityDisplay' => '2.5'
        ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を更新しました。'
    ]);
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);
    $responseData = $showResponse->json('data');
    expect($responseData['ingredients'][0]['quantity'])->toBe(2.5);
});

test('3-7-167: 【更新】 requires_quantity=false の食材単位で数量指定', function () {
    // requires_quantity=false の食材単位を作成
    $unitWithoutQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '適量',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false,
        'is_default' => false
    ]);

    // 料理を作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithoutQuantity->id,
            'quantityDisplay' => '2.5'
        ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    $response->assertJsonPath('data', null);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);
    $responseData = $showResponse->json('data');
    $this->assertNull($responseData['ingredients'][0]['quantity'] ?? null);

    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients[0]->pivot->quantity)->toBeNull();
});

test('3-7-168: 【更新】 requires_quantity=false の食材単位で数量省略', function () {
    // requires_quantity=false の食材単位を作成
    $unitWithoutQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '適量',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => false,
        'is_default' => false
    ]);

    // 料理を作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithoutQuantity->id,
            // quantityDisplay を省略
        ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を更新しました。'
    ]);
    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $showResponse->assertStatus(200);
    $responseData = $showResponse->json('data');
    $this->assertNull($responseData['ingredients'][0]['quantity'] ?? null);
});

test('3-7-169: 【更新】 すべての項目を含む料理更新', function () {
    // テスト用の料理をAPIで作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    // 追加の画像を作成（手順用）
    $stepImage = Image::create([
        'src' => "/storage/images/groups/{$this->group->id}/step.jpg",
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
        'cookingTime' => 45,
        'categoryIds' => [$this->recipeCategory->id, $recipeCategory2->id],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '300',
                'order' => 0
            ],
            [
                'name' => 'にんじん',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '150',
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
        ],
        'ownerUserId' => $this->user->id
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
        'memo' => '更新されたメモです',
        'cooking_time' => 45,
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

    // レスポンス構造の確認（update は success + message のみ）
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-170: 【更新】 servingCount が null でも正常に更新できる', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'servingCount' => null, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJsonPath('data', null);

    // serving_count が null で更新されたことを DB で確認（update は data を返さない）
    $recipe = Recipe::find($recipeId);
    $this->assertNull($recipe->serving_count);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-171: 【更新】 同一グループの他ユーザーの料理更新', function () {
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

    $data = ['name' => '勝手に更新', 'servingCount' => 4, 'ownerUserId' => $this->user->id];

    // 別のユーザー（自分）で更新を試みる（同一グループ内のメンバーは編集可）
    $response = $this->actingAs($this->user)->put("/recipes/{$otherRecipe->id}", $data);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    // データベースが更新されていることを確認
    $this->assertDatabaseHas('recipes', [
        'id' => $otherRecipe->id,
        'name' => '勝手に更新',
        'serving_count' => 4
    ]);
});

test('3-7-172: 【更新】 同一材料名で単位が異なる行は複数登録できる', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $secondUnit = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => '合',
        'position' => 'suffix',
        'order' => 2,
        'requires_quantity' => true,
        'is_default' => false
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '米',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100',
            ],
            [
                'name' => '米',
                'unitId' => $secondUnit->id,
                'quantityDisplay' => '1',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(2);
});

test('3-7-173: 【更新】 quantityDisplay を変更できる', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => '更新テスト',
        'servingCount' => 2,
        'ingredients' => [[
            'name' => '塩',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => '0.5',
        ]],
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => '更新テスト',
        'servingCount' => 2,
        'ingredients' => [[
            'name' => '塩',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => '1/2',
        ]],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(200);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    expect($showResponse->json('data.ingredients.0.quantityDisplay'))->toBe('1/2');
});

test('3-7-174: 【更新】 id 指定で既存食材カテゴリーを更新', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredientCategories' => [
            ['name' => '食材', 'isDefault' => true, 'order' => 0],
            ['name' => '野菜', 'isDefault' => false, 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $vegetableCategory = collect($showResponse->json('data.ingredientCategories'))
        ->first(fn ($category) => $category['name'] === '野菜');
    $defaultCategory = collect($showResponse->json('data.ingredientCategories'))
        ->firstWhere('isDefault', true);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => $defaultCategory['id'], 'name' => $defaultCategory['name'], 'order' => $defaultCategory['order']],
            ['id' => $vegetableCategory['id'], 'name' => '野菜', 'order' => $vegetableCategory['order']],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    $updatedShowResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $updatedCategory = collect($updatedShowResponse->json('data.ingredientCategories'))
        ->firstWhere('id', $vegetableCategory['id']);
    expect($updatedCategory['name'])->toBe('野菜');
});

test('3-7-175: 【更新】 categoryId でレシピ内カテゴリーに食材を紐づけ', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredientCategories' => [
            ['name' => '食材', 'isDefault' => true, 'order' => 0],
            ['name' => '野菜', 'isDefault' => false, 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $vegetableCategory = collect($showResponse->json('data.ingredientCategories'))
        ->first(fn ($category) => $category['name'] === '野菜');
    $defaultCategory = collect($showResponse->json('data.ingredientCategories'))
        ->firstWhere('isDefault', true);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => $defaultCategory['id'], 'name' => $defaultCategory['name'], 'order' => $defaultCategory['order']],
            ['id' => $vegetableCategory['id'], 'name' => '野菜', 'order' => $vegetableCategory['order']],
        ],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $vegetableCategory['id'],
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(200);

    $updatedShowResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    expect($updatedShowResponse->json('data.ingredients.0.categoryId'))->toBe($vegetableCategory['id']);
    expect($updatedShowResponse->json('data.ingredients.0.name'))->toBe('玉ねぎ');
});

test('3-7-176: 【更新】 ingredientCategories を新規追加（id 省略）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $defaultCategory = collect($showResponse->json('data.ingredientCategories'))
        ->firstWhere('isDefault', true);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => $defaultCategory['id'], 'name' => $defaultCategory['name'], 'order' => $defaultCategory['order']],
            ['name' => '野菜', 'order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(200);

    $updatedShowResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $categoryNames = collect($updatedShowResponse->json('data.ingredientCategories'))
        ->pluck('name')
        ->all();
    expect($categoryNames)->toContain('野菜');
});

test('3-7-177: 【更新】 非デフォルト食材カテゴリーを削除', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredientCategories' => [
            ['name' => '食材', 'isDefault' => true, 'order' => 0],
            ['name' => '野菜', 'isDefault' => false, 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $defaultCategory = collect($showResponse->json('data.ingredientCategories'))
        ->firstWhere('isDefault', true);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => $defaultCategory['id'], 'name' => $defaultCategory['name'], 'order' => $defaultCategory['order']],
        ],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(200);

    $updatedShowResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $categoryNames = collect($updatedShowResponse->json('data.ingredientCategories'))
        ->pluck('name')
        ->all();
    expect($categoryNames)->not->toContain('野菜');
    expect($categoryNames)->toContain($defaultCategory['name']);
});

test('3-7-178: 【更新】 categoryName で DB 上の既存カテゴリーに食材を紐づけ', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredientCategories' => [
            ['name' => '食材', 'isDefault' => true, 'order' => 0],
            ['name' => '野菜', 'isDefault' => false, 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => '野菜',
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(200);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $vegetableCategory = collect($showResponse->json('data.ingredientCategories'))
        ->first(fn ($category) => $category['name'] === '野菜');

    expect($showResponse->json('data.ingredients.0.categoryId'))->toBe($vegetableCategory['id']);
    expect($showResponse->json('data.ingredients.0.name'))->toBe('玉ねぎ');
});

test('3-7-179: 【更新】 ingredients を空配列で指定しても既存食材は削除されない', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => '100',
        ]],
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredients' => [],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(200);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    expect($showResponse->json('data.ingredients'))->toHaveCount(1);
    expect($showResponse->json('data.ingredients.0.name'))->toBe('玉ねぎ');
});

test('3-7-180: 【更新】 categoryId/categoryName 省略時はデフォルトカテゴリーにフォールバック', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'quantityDisplay' => '100']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $defaultCategory = collect($showResponse->json('data.ingredientCategories'))
        ->firstWhere('isDefault', true);

    expect($showResponse->json('data.ingredients.0.categoryId'))->toBe($defaultCategory['id']);
});

test('3-7-181: 【更新】 未認証ユーザー', function () {
    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id];

    $response = $this->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(401);
    $response->assertJson(['success' => false, 'message' => '認証が必要です。']);
});

test('3-7-182: 【更新】 グループが存在しない', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $user->id];

    $response = $this->actingAs($user)->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false, 'message' => 'ユーザーはグループに所属していません。']);
});

test('3-7-183: 【更新】 バリデーションエラー（name 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('nameは必ず指定してください。', $responseData['errors']['name']);
});

test('3-7-184: 【更新】 バリデーションエラー（name が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 123, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('nameは文字列を指定してください。', $responseData['errors']['name']);
});

test('3-7-185: 【更新】 バリデーションエラー（name が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => str_repeat('あ', 256), 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('nameは、255文字以内で指定してください。', $responseData['errors']['name']);
});

test('3-7-186: 【更新】 バリデーションエラー（url が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'url' => 123, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);
});

test('3-7-187: 【更新】 バリデーションエラー（url が 2048 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'url' => str_repeat('a', 2049),
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);
});

test('3-7-188: 【更新】 バリデーションエラー（thumbnailId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'thumbnailId' => 'invalid-uuid', 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['thumbnailId']);
});

test('3-7-189: 【更新】 バリデーションエラー（categoryIds が配列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'categoryIds' => 'not-an-array',
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryIds']);
});

test('3-7-190: 【更新】 バリデーションエラー（categoryIds.\* が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'categoryIds' => ['invalid-uuid'],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryIds.0']);
});

test('3-7-191: 【更新】 バリデーションエラー（categoryIds.* 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'categoryIds' => [null], 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['categoryIds.0']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('categoryIds.*は必ず指定してください。', $responseData['errors']['categoryIds.0']);

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

test('3-7-192: 【更新】 バリデーションエラー（ingredientCategories が配列でない）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => 'not_array',
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategoriesは配列でなくてはなりません。', $responseData['errors']['ingredientCategories']);
});

test('3-7-193: 【更新】 バリデーションエラー（ingredientCategories.\*.id が UUID 形式でない）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => 'invalid-uuid', 'name' => '野菜', 'order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.id']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.idに有効なUUIDを指定してください。', $responseData['errors']['ingredientCategories.0.id']);
});

test('3-7-194: 【更新】 バリデーションエラー（ingredientCategories.\*.name 未入力）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.name']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.nameは必ず指定してください。', $responseData['errors']['ingredientCategories.0.name']);
});

test('3-7-195: 【更新】 バリデーションエラー（ingredientCategories.\*.name が文字列でない）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => 123, 'order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.name']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.nameは文字列を指定してください。', $responseData['errors']['ingredientCategories.0.name']);
});

test('3-7-196: 【更新】 バリデーションエラー（ingredientCategories.\*.name が 255 文字超過）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => str_repeat('a', 256), 'order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.name']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.nameは、255文字以内で指定してください。', $responseData['errors']['ingredientCategories.0.name']);
});

test('3-7-197: 【更新】 バリデーションエラー（ingredientCategories.\*.order 未入力）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜'],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.order']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.orderは必ず指定してください。', $responseData['errors']['ingredientCategories.0.order']);
});

test('3-7-198: 【更新】 バリデーションエラー（ingredientCategories.\*.order が整数でない）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'order' => 'not_integer'],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.order']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.orderは整数で指定してください。', $responseData['errors']['ingredientCategories.0.order']);
});

test('3-7-199: 【更新】 バリデーションエラー（ingredientCategories.\*.order が負の値）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'order' => -1],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.0.order']);
    $responseData = $response->json();
    $this->assertContains('ingredientCategories.*.orderには、0以上の数字を指定してください。', $responseData['errors']['ingredientCategories.0.order']);
});

test('3-7-200: 【更新】 バリデーションエラー（ingredientCategories.\*.name が重複）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'order' => 0],
            ['name' => '野菜', 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.1.name']);
    $responseData = $response->json();
    $this->assertContains('材料カテゴリ名が重複しています。', $responseData['errors']['ingredientCategories.1.name']);
});

test('3-7-201: 【更新】 バリデーションエラー（ingredientCategories.\*.id が重複）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $categoryId = (string) Str::uuid();

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => $categoryId, 'name' => '野菜', 'order' => 0],
            ['id' => $categoryId, 'name' => '肉', 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredientCategories.1.id']);
    $responseData = $response->json();
    $this->assertContains('材料カテゴリIDが重複しています。', $responseData['errors']['ingredientCategories.1.id']);
});

test('3-7-202: 【更新】 バリデーションエラー（ingredients が配列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'ingredients' => 'not-an-array', 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients']);
});

test('3-7-203: 【更新】 バリデーションエラー（ingredients.\*.id が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['id' => 'invalid-uuid', 'name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.id']);
});

test('3-7-204: 【更新】 バリデーションエラー（ingredients.\*.id と ingredients.\*.name が両方未指定）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['unitId' => $this->ingredientUnit->id, ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);

    $responseData = $response->json();
    $this->assertContains('材料名にはidまたはnameのいずれかを指定してください。', $responseData['errors']['ingredients.0.name']);
});

test('3-7-205: 【更新】 バリデーションエラー（ingredients.\*.name が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => 123, 'unitId' => $this->ingredientUnit->id, ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

test('3-7-206: 【更新】 バリデーションエラー（ingredients.\*.name が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => str_repeat('あ', 256), 'unitId' => $this->ingredientUnit->id, ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

test('3-7-207: 【更新】 バリデーションエラー（ingredients.\*.unitId 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.unitId']);
});

test('3-7-208: 【更新】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => 'invalid-uuid', ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.unitId']);
});

test('3-7-209: 【更新】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => 'invalid-uuid']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryId']);
});

test('3-7-210: 【更新】 バリデーションエラー（ingredients.\*.categoryName が文字列でない）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => true, 'order' => 0],
        ],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => 123,
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryName']);
    $responseData = $response->json();
    $this->assertContains('ingredients.*.categoryNameは文字列を指定してください。', $responseData['errors']['ingredients.0.categoryName']);
});

test('3-7-211: 【更新】 バリデーションエラー（ingredients.\*.categoryName が 255 文字超過）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => true, 'order' => 0],
        ],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => str_repeat('a', 256),
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryName']);
    $responseData = $response->json();
    $this->assertContains('ingredients.*.categoryNameは、255文字以内で指定してください。', $responseData['errors']['ingredients.0.categoryName']);
});

test('3-7-212: 【更新】 バリデーションエラー（ingredients.\*.categoryName が ingredientCategories に含まれない）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['name' => '野菜', 'isDefault' => true, 'order' => 0],
        ],
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => '存在しないカテゴリ',
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryName']);
    $responseData = $response->json();
    $this->assertContains('指定されたcategoryNameはingredientCategoriesに含まれていません。', $responseData['errors']['ingredients.0.categoryName']);
});

test('3-7-213: 【更新】 バリデーションエラー（ingredients.\*.quantityDisplay が parse 不可）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'バリデーションテスト',
        'servingCount' => 2,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->putJson("/recipes/{$recipeId}", [
        'name' => 'バリデーションテスト',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => 'abc',
        ]],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);

    $responseData = $response->json();
    $this->assertContains('数量は整数・小数・分数で入力してください（例: 2、1.5、1/2、1と1/2）', $responseData['errors']['ingredients.0.quantityDisplay']);
});

test('3-7-214: 【更新】 バリデーションエラー（ingredients.\*.quantityDisplay が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'バリデーションテスト',
        'servingCount' => 2,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->putJson("/recipes/{$recipeId}", [
        'name' => 'バリデーションテスト',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => ['not' => 'string'],
        ]],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);

    $responseData = $response->json();
    $this->assertContains('ingredients.*.quantityDisplayは文字列を指定してください。', $responseData['errors']['ingredients.0.quantityDisplay']);

    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.quantityDisplay',
        ],
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-215: 【更新】 バリデーションエラー（ingredients.\*.quantityDisplay が 50 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'バリデーションテスト',
        'servingCount' => 2,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->putJson("/recipes/{$recipeId}", [
        'name' => 'バリデーションテスト',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => str_repeat('a', 51),
        ]],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);

    $responseData = $response->json();
    $this->assertContains('ingredients.*.quantityDisplayは、50文字以内で指定してください。', $responseData['errors']['ingredients.0.quantityDisplay']);

    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ingredients.0.quantityDisplay',
        ],
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-216: 【更新】 バリデーションエラー（requires_quantity=true の単位で quantityDisplay を null に指定）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'クリアテスト',
        'servingCount' => 2,
        'ingredients' => [[
            'name' => '塩',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => '1/2',
        ]],
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->putJson("/recipes/{$recipeId}", [
        'name' => 'クリアテスト',
        'servingCount' => 2,
        'ingredients' => [[
            'name' => '塩',
            'unitId' => $this->ingredientUnit->id,
            'quantityDisplay' => null,
        ]],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);
});

test('3-7-217: 【更新】 バリデーションエラー（ingredients.\*.order が整数でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id,  'order' => 'not-an-integer']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.order']);
});

test('3-7-218: 【更新】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で数量省略）', function () {
    // requires_quantity=true の食材単位を作成
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true,
        'is_default' => false
    ]);

    // 料理を作成
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            // quantityDisplay を省略
        ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);
});

test('3-7-219: 【更新】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で quantityDisplay が空文字）', function () {
    $unitWithQuantity = IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'kg',
        'position' => 'suffix',
        'order' => 1,
        'requires_quantity' => true,
        'is_default' => false
    ]);

    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [[
            'name' => '玉ねぎ',
            'unitId' => $unitWithQuantity->id,
            'quantityDisplay' => '',
        ]],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantityDisplay']);
    $responseData = $response->json();
    $this->assertContains('選択した単位ではingredients.*.quantityDisplayの指定が必須です。', $responseData['errors']['ingredients.0.quantityDisplay']);
});

test('3-7-220: 【更新】 バリデーションエラー（ingredients.\*.order が負の値）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id,  'order' => -1]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.order']);
});

test('3-7-221: 【更新】 バリデーションエラー（steps が配列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'steps' => 'not-an-array', 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps']);
});

test('3-7-222: 【更新】 バリデーションエラー（steps.\*.id が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'steps' => [['id' => 'invalid-uuid', 'instruction' => '切る', 'order' => 0]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.id']);
});

test('3-7-223: 【更新】 バリデーションエラー（steps.\*.instruction 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'steps' => [['order' => 0]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.instruction']);
});

test('3-7-224: 【更新】 バリデーションエラー（steps.\*.instruction が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => 123, 'order' => 0]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.instruction']);
});

test('3-7-225: 【更新】 バリデーションエラー（steps.\*.instruction が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => str_repeat('あ', 256), 'order' => 0]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.instruction']);
});

test('3-7-226: 【更新】 バリデーションエラー（steps.\*.imageId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => '切る', 'imageId' => 'invalid-uuid', 'order' => 0]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.imageId']);
});

test('3-7-227: 【更新】 バリデーションエラー（steps.\*.order 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => '切る']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.order']);
});

test('3-7-228: 【更新】 バリデーションエラー（steps.\*.order が整数でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => '切る', 'order' => 'not-an-integer']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.order']);
});

test('3-7-229: 【更新】 バリデーションエラー（steps.\*.order が負の値）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'steps' => [['instruction' => '切る', 'order' => -1]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps.0.order']);
});

test('3-7-230: 【更新】 バリデーションエラー（memo が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'memo' => 123, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['memo']);
});

test('3-7-231: 【更新】 バリデーションエラー（memo が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'memo' => str_repeat('あ', 256), 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['memo']);
});

test('3-7-232: 【更新】 バリデーションエラー（servingCount が整数でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'servingCount' => 'abc', 'ownerUserId' => $this->user->id];

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

test('3-7-233: 【更新】 バリデーションエラー（servingCount が 1 未満）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'servingCount' => 0, 'ownerUserId' => $this->user->id];

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

test('3-7-234: 【更新】 バリデーションエラー（cookingTime が整数でない）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'cookingTime' => 'abc',
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cookingTime']);
    $responseData = $response->json();
    $this->assertContains('cookingTimeは整数で指定してください。', $responseData['errors']['cookingTime']);
});

test('3-7-235: 【更新】 バリデーションエラー（cookingTime が 0 未満）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'cookingTime' => -1,
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cookingTime']);
    $responseData = $response->json();
    $this->assertContains('cookingTimeには、0以上の数字を指定してください。', $responseData['errors']['cookingTime']);
});

test('3-7-236: 【更新】 バリデーションエラー（ownerUserId 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    // ownerUserId を意図的に省略してバリデーションエラーを検証
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ownerUserId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ownerUserIdは必ず指定してください。', $responseData['errors']['ownerUserId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ownerUserId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-237: 【更新】 バリデーションエラー（ownerUserId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => 'invalid-uuid'
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ownerUserId']);

    // エラーメッセージの内容を検証
    $responseData = $response->json();
    $this->assertContains('ownerUserIdに有効なUUIDを指定してください。', $responseData['errors']['ownerUserId']);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => [
            'ownerUserId'
        ]
    ]);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-238: 【更新】 バリデーションエラー（ingredients 同一 name・unitId・category の組み合わせが重複）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '米',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '1',
            ],
            [
                'name' => '米',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '2',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.1.name']);

    $responseData = $response->json();
    $this->assertContains('同じ材料名・単位・カテゴリーの組み合わせが重複しています。', $responseData['errors']['ingredients.1.name']);
});

test('3-7-239: 【更新】 存在しない食材単位 ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => '00000000-0000-0000-0000-000000000000',
                'quantityDisplay' => '100'
            ]
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-7-240: 【更新】 他グループの食材単位 ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherUnit = IngredientUnit::create(['group_id' => $otherGroup->id, 'name' => '他', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 0, 'is_default' => false]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $otherUnit->id,  'quantityDisplay' => '100']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-241: 【更新】 存在しない食材カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => '00000000-0000-0000-0000-000000000000', 'quantityDisplay' => '100']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-242: 【更新】 他レシピの食材カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherCategoryId = getOtherRecipeIngredientCategoryId($this, $otherUser, $otherGroup);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $otherCategoryId, 'quantityDisplay' => '100']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-243: 【更新】 存在しない料理カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => ['00000000-0000-0000-0000-000000000000'],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-244: 【更新】 他グループの料理カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherCategory = RecipeCategory::create(['group_id' => $otherGroup->id, 'name' => '他', 'order' => 0]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'categoryIds' => [$otherCategory->id],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->putJson("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-245: 【更新】 存在しない画像 ID 指定（thumbnailId）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => '00000000-0000-0000-0000-000000000000',
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-246: 【更新】 他グループの画像 ID 指定（thumbnailId）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherImage = Image::create([
        'src' => "/storage/images/groups/{$otherGroup->id}/other.jpg",
        'width' => 800,
        'height' => 600
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'thumbnailId' => $otherImage->id,
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-247: 【更新】 存在しない画像 ID 指定（steps.\*.imageId）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [['instruction' => '切る', 'imageId' => '00000000-0000-0000-0000-000000000000', 'order' => 0]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-248: 【更新】 他グループの画像 ID 指定（steps.\*.imageId）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherImage = Image::create([
        'src' => "/storage/images/groups/{$otherGroup->id}/other.jpg",
        'width' => 800,
        'height' => 600
    ]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'steps' => [['instruction' => '切る', 'imageId' => $otherImage->id, 'order' => 0]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-249: 【更新】 存在しない料理更新', function () {
    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(404);
    $response->assertJson(['success' => false]);
});

test('3-7-250: 【更新】 他グループの料理更新', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherRecipe = Recipe::create(['group_id' => $otherGroup->id, 'owner_user_id' => $otherUser->id, 'name' => '他の料理']);

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$otherRecipe->id}", $data);

    $response->assertStatus(404);
});

test('3-7-251: 【更新】 当該レシピに存在しない ingredientCategories[].id 指定', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $defaultCategory = collect($showResponse->json('data.ingredientCategories'))
        ->firstWhere('isDefault', true);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => $defaultCategory['id'], 'name' => $defaultCategory['name'], 'order' => $defaultCategory['order']],
            ['id' => '00000000-0000-0000-0000-000000000002', 'name' => '存在しない', 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された食材カテゴリーが見つかりませんでした。',
    ]);
});

test('3-7-252: 【更新】 レシピ内に存在しない categoryName 指定', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryName' => '野菜',
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false,
        'message' => '指定された食材カテゴリーが見つかりませんでした。',
    ]);
});

test('3-7-253: 【更新】 ingredientCategories を空配列で指定（デフォルト削除試行）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredientCategories' => [],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => '食材は削除できません。',
    ]);
});

test('3-7-254: 【更新】 is_default カテゴリーは削除不可', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredientCategories' => [
            ['name' => '食材', 'isDefault' => true, 'order' => 0],
            ['name' => '野菜', 'isDefault' => false, 'order' => 1],
        ],
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $showResponse = $this->actingAs($this->user)->get("/recipes/{$recipeId}");
    $vegetableCategory = collect($showResponse->json('data.ingredientCategories'))
        ->first(fn ($category) => $category['name'] === '野菜');

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredientCategories' => [
            ['id' => $vegetableCategory['id'], 'name' => '野菜', 'order' => 0],
        ],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => '食材は削除できません。',
    ]);
});

test('3-7-255: 【更新】 存在しない食材 ID 指定', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'id' => '00000000-0000-0000-0000-000000000001',
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);
});

test('3-7-256: 【更新】 他グループの食材 ID 指定', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    $otherGroup->users()->attach($otherUser->id);

    $otherIngredient = Ingredient::create([
        'group_id' => $otherGroup->id,
        'name' => '他グループの玉ねぎ',
    ]);

    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'id' => $otherIngredient->id,
                'unitId' => $this->ingredientUnit->id,
                'quantityDisplay' => '100',
            ],
        ],
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);
});

test('3-7-257: 【更新】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('update')->once()->andThrow(new \Exception('Database connection failed'));
    });

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);
});

// ===== destroy() メソッドのテストケース =====


// ===== destroy() メソッドのテストケース =====

test('3-7-258: 【削除】 正常な料理削除', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

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

test('3-7-259: 【削除】 削除成功メッセージの確認', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $response = $this->actingAs($this->user)->delete("/recipes/{$recipeId}");

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toContain('カレーライス');
});

test('3-7-260: 【削除】 未認証ユーザー', function () {
    $response = $this->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(401);
    $response->assertJson(['success' => false, 'message' => '認証が必要です。']);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-261: 【削除】 グループが存在しない', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(422);
    $response->assertJson(['success' => false, 'message' => 'ユーザーはグループに所属していません。']);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-262: 【削除】 存在しない料理削除', function () {
    $response = $this->actingAs($this->user)->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404);
    $response->assertJson(['success' => false]);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-263: 【削除】 他グループの料理削除', function () {
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

test('3-7-264: 【削除】 同一グループの他ユーザーの料理削除', function () {
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

test('3-7-265: 【削除】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('delete')->once()->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);
});

test('3-7-266: 【新規作成】 他グループユーザーを ownerUserId に指定', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id,
    ]);

    $response = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $otherUser->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ownerUserId']);

    $responseData = $response->json();
    $this->assertContains(
        'ownerUserIdは同じグループに所属するユーザーを指定してください。',
        $responseData['errors']['ownerUserId']
    );
});

test('3-7-267: 【更新】 同一グループなら ownerUserId を変更できる', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $this->group->users()->attach($otherUser->id);

    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $otherUser->id,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '料理/レシピ(カレーライス)を更新しました。',
    ]);

    $this->assertDatabaseHas('recipes', [
        'id' => $recipeId,
        'owner_user_id' => $otherUser->id,
    ]);

    $recipe = Recipe::find($recipeId);
    expect($recipe->owner_user_id)->toBe($otherUser->id);
});

test('3-7-268: 【更新】 他グループユーザーを ownerUserId に指定', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert([
        'user_id' => $otherUser->id,
        'group_id' => $otherGroup->id,
    ]);

    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = $createResponse->json('data.id');

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $otherUser->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ownerUserId']);

    $responseData = $response->json();
    $this->assertContains(
        'ownerUserIdは同じグループに所属するユーザーを指定してください。',
        $responseData['errors']['ownerUserId']
    );

    $recipe = Recipe::find($recipeId);
    expect($recipe->owner_user_id)->toBe($this->user->id);
});

test('3-7-269: 【新規作成】 バリデーションエラー（url が http(s) スキームでない）', function () {
    $data = [
        'name' => 'カレーライス',
        'url' => 'ftp://example.com/recipe',
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);

    $responseData = $response->json();
    $this->assertContains('urlに正しい形式を指定してください。', $responseData['errors']['url']);
});

test('3-7-270: 【更新】 バリデーションエラー（url が http(s) スキームでない）', function () {
    $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id,
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", [
        'name' => 'カレーライス',
        'url' => 'ftp://example.com/recipe',
        'ownerUserId' => $this->user->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['url']);

    $responseData = $response->json();
    $this->assertContains('urlに正しい形式を指定してください。', $responseData['errors']['url']);
});

