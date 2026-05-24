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
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'quantity' => 1]]
    ]);
    $this->actingAs($this->user)->post('/recipes', [
        'name' => '肉じゃが',
        'servingCount' => 2,
        'ownerUserId' => $this->user->id,
        'ingredients' => [['name' => 'じゃがいも', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'quantity' => 1]]
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

test('3-7-13: 【一覧取得】 category_ids を指定してカテゴリで絞り込みできること（指定したいずれかのカテゴリに属するレシピが返る）', function () {
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
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'quantity' => 1]],
    ]);
    $curryWithOnion->assertStatus(201);
    $this->actingAs($this->user)->postJson('/recipes', [
        'name' => 'カレーパン',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId],
        'ingredients' => [['name' => 'パン', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'quantity' => 1]],
    ]);
    $this->actingAs($this->user)->postJson('/recipes', [
        'name' => 'サラダ',
        'servingCount' => 1,
        'ownerUserId' => $this->user->id,
        'categoryIds' => [$catId],
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'quantity' => 1]],
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

test('3-7-29: 【一覧取得】 バリデーションエラー（sort が不正な値）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?sort=invalid_column');
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false
    ]);
    $response->assertJsonValidationErrors(['sort']);
});

test('3-7-30: 【一覧取得】 バリデーションエラー（order が不正な値）', function () {
    $response = $this->actingAs($this->user)->get('/recipes?order=invalid');
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false
    ]);
    $response->assertJsonValidationErrors(['order']);
});

test('3-7-31: 【一覧取得】 データベース接続エラー', function () {
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

test('3-7-32: 【一覧取得】 RecipeService 例外', function () {
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

test('3-7-33: 【新規作成】 正常な料理作成', function () {
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

test('3-7-42: 【新規作成】 最小限のデータで料理作成', function () {
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

test('3-7-43: 【新規作成】 料理にカテゴリを紐づけ', function () {
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

test('3-7-44: 【新規作成】 料理に食材を紐づけ', function () {
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

test('3-7-45: 【新規作成】 最小限の必須フィールドのみで食材を紐づけ', function () {
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
                'categoryId' => $this->ingredientCategory->id
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

test('3-7-46: 【新規作成】 料理に手順を紐づけ', function () {
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

test('3-7-47: 【新規作成】 最小限の必須フィールドのみで手順を紐づけ', function () {
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

test('3-7-48: 【新規作成】 料理に画像を紐づけ', function () {
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

test('3-7-49: 【新規作成】 requires_quantity=true の食材単位で数量指定', function () {
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
            'categoryId' => $this->ingredientCategory->id,
            'quantity' => 2.5
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

test('3-7-50: 【新規作成】 requires_quantity=false の食材単位で数量指定', function () {
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
            'categoryId' => $this->ingredientCategory->id,
            'quantity' => 2.5
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
    expect($responseData['ingredients'][0]['quantity'])->toBe(2.5);
});

test('3-7-51: 【新規作成】 requires_quantity=false の食材単位で数量省略', function () {
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
            'categoryId' => $this->ingredientCategory->id
            // quantity を省略
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

test('3-7-52: 【新規作成】 すべての項目を含む料理作成', function () {
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

test('3-7-55: 【新規作成】 バリデーションエラー（name 未入力）', function () {
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

test('3-7-56: 【新規作成】 バリデーションエラー（name が文字列でない）', function () {
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

test('3-7-57: 【新規作成】 バリデーションエラー（name が 255 文字超過）', function () {
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

test('3-7-58: 【新規作成】 バリデーションエラー（url が文字列でない）', function () {
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

test('3-7-59: 【新規作成】 バリデーションエラー（url が 2048 文字超過）', function () {
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

test('3-7-60: 【新規作成】 バリデーションエラー（thumbnailId が UUID 形式でない）', function () {
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

test('3-7-61: 【新規作成】 バリデーションエラー（categoryIds が配列でない）', function () {
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

test('3-7-62: 【新規作成】 バリデーションエラー（categoryIds.* が UUID 形式でない）', function () {
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

test('3-7-63: 【新規作成】 バリデーションエラー（categoryIds.* 未入力）', function () {
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

test('3-7-64: 【新規作成】 バリデーションエラー（ingredients が配列でない）', function () {
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

test('3-7-65: 【新規作成】 バリデーションエラー（ingredients.\*.id が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'id' => 'invalid-uuid',
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id
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

test('3-7-66: 【新規作成】 バリデーションエラー（ingredients.\*.name 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-67: 【新規作成】 バリデーションエラー（ingredients.\*.name が文字列でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => 123,
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id
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

test('3-7-68: 【新規作成】 バリデーションエラー（ingredients.\*.name が 255 文字超過）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => str_repeat('a', 256),
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id
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

test('3-7-69: 【新規作成】 バリデーションエラー（ingredients.\*.unitId 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'categoryId' => $this->ingredientCategory->id
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

test('3-7-70: 【新規作成】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => 'invalid-uuid',
                'categoryId' => $this->ingredientCategory->id
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

test('3-7-71: 【新規作成】 バリデーションエラー（ingredients.\*.categoryId 未入力）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-72: 【新規作成】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない）', function () {
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

test('3-7-73: 【新規作成】 バリデーションエラー（ingredients.\*.quantity が数値でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 'not_numeric'
            ]
        ],
        'ownerUserId' => $this->user->id
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

test('3-7-74: 【新規作成】 バリデーションエラー（ingredients.\*.order が整数でない）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
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

test('3-7-76: 【新規作成】 バリデーションエラー（ingredients.\*.order が負の値）', function () {
    $data = [
        'name' => 'カレーライス',
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
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

test('3-7-75: 【新規作成】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で数量省略）', function () {
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
            'categoryId' => $this->ingredientCategory->id
            // quantity を省略
        ]]
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.0.quantity']);
});

test('3-7-77: 【新規作成】 バリデーションエラー（steps が配列でない）', function () {
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

test('3-7-78: 【新規作成】 バリデーションエラー（steps.\*.id が UUID 形式でない）', function () {
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

test('3-7-79: 【新規作成】 バリデーションエラー（steps.\*.instruction 未入力）', function () {
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

test('3-7-80: 【新規作成】 バリデーションエラー（steps.\*.instruction が文字列でない）', function () {
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

test('3-7-81: 【新規作成】 バリデーションエラー（steps.\*.instruction が 255 文字超過）', function () {
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

test('3-7-82: 【新規作成】 バリデーションエラー（steps.\*.imageId が UUID 形式でない）', function () {
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

test('3-7-83: 【新規作成】 バリデーションエラー（steps.\*.order 未入力）', function () {
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

test('3-7-84: 【新規作成】 バリデーションエラー（steps.\*.order が整数でない）', function () {
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

test('3-7-85: 【新規作成】 バリデーションエラー（steps.\*.order が負の値）', function () {
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

test('3-7-86: 【新規作成】 バリデーションエラー（memo が文字列でない）', function () {
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

test('3-7-87: 【新規作成】 バリデーションエラー（memo が 255 文字超過）', function () {
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

test('3-7-53: 【新規作成】 serving_count が null でも正常に作成できる', function () {
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

test('3-7-54: 【新規作成】 同一材料名で単位が異なる行は複数登録できる', function () {
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
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 100,
            ],
            [
                'name' => '米',
                'unitId' => $secondUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 1,
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

test('3-7-88: 【新規作成】 バリデーションエラー（serving_count が整数でない）', function () {
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

test('3-7-89: 【新規作成】 バリデーションエラー（serving_count が 1 未満）', function () {
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

test('3-7-90: 【新規作成】 バリデーションエラー（ownerUserId 未入力）', function () {
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

test('3-7-91: 【新規作成】 バリデーションエラー（ownerUserId が UUID 形式でない）', function () {
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

test('3-7-92: 【新規作成】 バリデーションエラー（ingredients 同一 name と unitId の組み合わせが重複）', function () {
    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [
            [
                'name' => '米',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 1,
            ],
            [
                'name' => '米',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 2,
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->post('/recipes', $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.1.name']);

    $responseData = $response->json();
    $this->assertContains(__('validation.duplicate_ingredient'), $responseData['errors']['ingredients.1.name']);
});

test('3-7-93: 【新規作成】 存在しない食材単位 ID 指定', function () {
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

test('3-7-94: 【新規作成】 他グループの食材単位 ID 指定', function () {
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
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 100
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

test('3-7-95: 【新規作成】 存在しない食材カテゴリ ID 指定', function () {
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

test('3-7-96: 【新規作成】 他グループの食材カテゴリ ID 指定', function () {
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

test('3-7-97: 【新規作成】 存在しない料理カテゴリ ID 指定', function () {
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

test('3-7-98: 【新規作成】 他グループの料理カテゴリ ID 指定', function () {
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

test('3-7-99: 【新規作成】 存在しない画像 ID 指定（thumbnailId）', function () {
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

test('3-7-100: 【新規作成】 他グループの画像 ID 指定（thumbnailId）', function () {
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

test('3-7-101: 【新規作成】 存在しない画像 ID 指定（steps.\*.imageId）', function () {
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

test('3-7-102: 【新規作成】 他グループの画像 ID 指定（steps.\*.imageId）', function () {
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

test('3-7-103: 【新規作成】 未認証ユーザー', function () {
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

test('3-7-104: 【新規作成】 グループが存在しない', function () {
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

test('3-7-105: 【新規作成】 データベース接続エラー', function () {
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

test('3-7-106: 【新規作成】 料理作成失敗', function () {
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

test('3-7-107: 【新規作成】 食材紐づけ失敗', function () {
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

test('3-7-108: 【新規作成】 手順紐づけ失敗', function () {
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

test('3-7-109: 【新規作成】 画像紐づけ失敗', function () {
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

test('3-7-110: 【新規作成】 ImageService 例外', function () {
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

test('3-7-111: 【詳細取得】 正常な料理詳細取得', function () {
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

test('3-7-112: 【詳細取得】 すべての項目を含む料理詳細取得', function () {
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

test('3-7-113: 【詳細取得】 存在しない料理詳細取得', function () {
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

test('3-7-114: 【詳細取得】 他グループの料理詳細取得', function () {
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

test('3-7-115: 【詳細取得】 未認証ユーザー', function () {
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

test('3-7-116: 【詳細取得】 グループが存在しない', function () {
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

test('3-7-117: 【詳細取得】 データベース接続エラー', function () {
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

test('3-7-118: 【更新】 正常な料理更新', function () {
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

test('3-7-119: 【更新】 最小限のデータで料理更新', function () {
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

test('3-7-120: 【更新】 料理のカテゴリ更新', function () {
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

test('3-7-121: 【更新】 料理の食材更新', function () {
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
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 200
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

test('3-7-122: 【更新】 最小限の必須フィールドのみで食材を更新', function () {
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
                'categoryId' => $this->ingredientCategory->id
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

test('3-7-123: 【更新】 料理の手順更新', function () {
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

test('3-7-124: 【更新】 最小限の必須フィールドのみで手順を更新', function () {
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

test('3-7-125: 【更新】 手順の画像を削除（imageId が null）', function () {
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

test('3-7-126: 【更新】 手順の画像を削除（imageId キーが存在しない）', function () {
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

test('3-7-127: 【更新】 料理の画像更新', function () {
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

test('3-7-128: 【更新】 サムネイルを削除（thumbnailId が null）', function () {
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

test('3-7-129: 【更新】 サムネイルを削除（thumbnailId キーが存在しない）', function () {
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

test('3-7-130: 【更新】 更新成功メッセージの確認', function () {
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

test('3-7-131: 【更新】 requires_quantity=true の食材単位で数量指定', function () {
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
            'categoryId' => $this->ingredientCategory->id,
            'quantity' => 2.5
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

test('3-7-132: 【更新】 requires_quantity=false の食材単位で数量指定', function () {
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
            'categoryId' => $this->ingredientCategory->id,
            'quantity' => 2.5
        ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    $response->assertJsonPath('data', null);

    // 更新内容は DB で確認（update は data を返さない）
    $recipe = Recipe::with('ingredients')->find($recipeId);
    expect($recipe->ingredients)->toHaveCount(1);
    expect($recipe->ingredients[0]->name)->toBe('玉ねぎ');
    expect((float) $recipe->ingredients[0]->pivot->quantity)->toBe(2.5);
});

test('3-7-133: 【更新】 requires_quantity=false の食材単位で数量省略', function () {
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
            'categoryId' => $this->ingredientCategory->id
            // quantity を省略
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

test('3-7-134: 【更新】 すべての項目を含む料理更新', function () {
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

test('3-7-158: 【更新】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で数量省略）', function () {
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
            'categoryId' => $this->ingredientCategory->id
            // quantity を省略
        ]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.0.quantity']);
});

// Update validation tests
test('3-7-138: 【更新】 バリデーションエラー（name 未入力）', function () {
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

test('3-7-139: 【更新】 バリデーションエラー（name が文字列でない）', function () {
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

test('3-7-140: 【更新】 バリデーションエラー（name が 255 文字超過）', function () {
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

test('3-7-141: 【更新】 バリデーションエラー（url が文字列でない）', function () {
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

test('3-7-142: 【更新】 バリデーションエラー（url が 2048 文字超過）', function () {
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

test('3-7-143: 【更新】 バリデーションエラー（thumbnailId が UUID 形式でない）', function () {
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

test('3-7-145: 【更新】 バリデーションエラー（categoryIds.\* が UUID 形式でない）', function () {
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

test('3-7-146: 【更新】 バリデーションエラー（categoryIds.* 未入力）', function () {
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

test('3-7-144: 【更新】 バリデーションエラー（categoryIds が配列でない）', function () {
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

test('3-7-148: 【更新】 バリデーションエラー（ingredients.\*.id が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['id' => 'invalid-uuid', 'name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.id']);
});

test('3-7-147: 【更新】 バリデーションエラー（ingredients が配列でない）', function () {
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

test('3-7-149: 【更新】 バリデーションエラー（ingredients.\*.name 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

test('3-7-150: 【更新】 バリデーションエラー（ingredients.\*.name が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => 123, 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

test('3-7-151: 【更新】 バリデーションエラー（ingredients.\*.name が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ownerUserId' => $this->user->id
    ]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => str_repeat('あ', 256), 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.name']);
});

test('3-7-152: 【更新】 バリデーションエラー（ingredients.\*.unitId 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'categoryId' => $this->ingredientCategory->id]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.unitId']);
});

test('3-7-153: 【更新】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => 'invalid-uuid', 'categoryId' => $this->ingredientCategory->id]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.unitId']);
});

test('3-7-154: 【更新】 バリデーションエラー（ingredients.\*.categoryId 未入力）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.categoryId']);
});

test('3-7-155: 【更新】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない）', function () {
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

test('3-7-156: 【更新】 バリデーションエラー（ingredients.\*.quantity が数値でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'quantity' => 'not-a-number']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.quantity']);
});

test('3-7-157: 【更新】 バリデーションエラー（ingredients.\*.order が整数でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'order' => 'not-an-integer']],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.order']);
});

test('3-7-159: 【更新】 バリデーションエラー（ingredients.\*.order が負の値）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $this->ingredientCategory->id, 'order' => -1]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ingredients.0.order']);
});

test('3-7-160: 【更新】 バリデーションエラー（steps が配列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'steps' => 'not-an-array', 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['steps']);
});

test('3-7-161: 【更新】 バリデーションエラー（steps.\*.id が UUID 形式でない）', function () {
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

test('3-7-162: 【更新】 バリデーションエラー（steps.\*.instruction 未入力）', function () {
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

test('3-7-163: 【更新】 バリデーションエラー（steps.\*.instruction が文字列でない）', function () {
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

test('3-7-164: 【更新】 バリデーションエラー（steps.\*.instruction が 255 文字超過）', function () {
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

test('3-7-165: 【更新】 バリデーションエラー（steps.\*.imageId が UUID 形式でない）', function () {
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

test('3-7-166: 【更新】 バリデーションエラー（steps.\*.order 未入力）', function () {
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

test('3-7-167: 【更新】 バリデーションエラー（steps.\*.order が整数でない）', function () {
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

test('3-7-168: 【更新】 バリデーションエラー（steps.\*.order が負の値）', function () {
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

test('3-7-169: 【更新】 バリデーションエラー（memo が文字列でない）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'memo' => 123, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['memo']);
});

test('3-7-170: 【更新】 バリデーションエラー（memo が 255 文字超過）', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'memo' => str_repeat('あ', 256), 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['memo']);
});

test('3-7-135: 【更新】 serving_count が null でも正常に更新できる', function () {
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

test('3-7-171: 【更新】 バリデーションエラー（serving_count が整数でない）', function () {
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

test('3-7-172: 【更新】 バリデーションエラー（serving_count が 1 未満）', function () {
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

test('3-7-173: 【更新】 バリデーションエラー（ownerUserId 未入力）', function () {
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

test('3-7-174: 【更新】 バリデーションエラー（ownerUserId が UUID 形式でない）', function () {
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

test('3-7-175: 【更新】 バリデーションエラー（ingredients 同一 name と unitId の組み合わせが重複）', function () {
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
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 1,
            ],
            [
                'name' => '米',
                'unitId' => $this->ingredientUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 2,
            ],
        ],
        'ownerUserId' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    $response->assertJsonValidationErrors(['ingredients.1.name']);

    $responseData = $response->json();
    $this->assertContains(__('validation.duplicate_ingredient'), $responseData['errors']['ingredients.1.name']);
});

test('3-7-176: 【更新】 存在しない食材単位 ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

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
        ],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-7-177: 【更新】 他グループの食材単位 ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherUnit = IngredientUnit::create(['group_id' => $otherGroup->id, 'name' => '他', 'position' => 'suffix', 'requires_quantity' => true, 'order' => 0, 'is_default' => false]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $otherUnit->id, 'categoryId' => $this->ingredientCategory->id, 'quantity' => 100]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-178: 【更新】 存在しない食材カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => '00000000-0000-0000-0000-000000000000', 'quantity' => 100]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-179: 【更新】 他グループの食材カテゴリ ID 指定', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherCategory = IngredientCategory::create(['group_id' => $otherGroup->id, 'name' => '他', 'order' => 0]);

    $data = [
        'name' => 'カレーライス',
        'servingCount' => 4,
        'ingredients' => [['name' => '玉ねぎ', 'unitId' => $this->ingredientUnit->id, 'categoryId' => $otherCategory->id, 'quantity' => 100]],
        'ownerUserId' => $this->user->id
    ];

    $response = $this->actingAs($this->user)->put("/recipes/{$recipeId}", $data);

    $response->assertStatus(404);
});

test('3-7-180: 【更新】 存在しない料理カテゴリ ID 指定', function () {
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

test('3-7-181: 【更新】 他グループの料理カテゴリ ID 指定', function () {
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

test('3-7-182: 【更新】 存在しない画像 ID 指定（thumbnailId）', function () {
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

test('3-7-183: 【更新】 他グループの画像 ID 指定（thumbnailId）', function () {
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

test('3-7-184: 【更新】 存在しない画像 ID 指定（steps.\*.imageId）', function () {
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

test('3-7-185: 【更新】 他グループの画像 ID 指定（steps.\*.imageId）', function () {
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

test('3-7-186: 【更新】 存在しない料理更新', function () {
    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(404);
    $response->assertJson(['success' => false]);
});

test('3-7-187: 【更新】 他グループの料理更新', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherGroup = Group::create(['group_size' => 1]);
    DB::table('group_user_mappings')->insert(['user_id' => $otherUser->id, 'group_id' => $otherGroup->id]);
    $otherRecipe = Recipe::create(['group_id' => $otherGroup->id, 'owner_user_id' => $otherUser->id, 'name' => '他の料理']);

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put("/recipes/{$otherRecipe->id}", $data);

    $response->assertStatus(404);
});

test('3-7-136: 【更新】 同一グループの他ユーザーの料理更新', function () {
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

test('3-7-137: 【更新】 同一材料名で単位が異なる行は複数登録できる', function () {
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
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 100,
            ],
            [
                'name' => '米',
                'unitId' => $secondUnit->id,
                'categoryId' => $this->ingredientCategory->id,
                'quantity' => 1,
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

test('3-7-188: 【更新】 未認証ユーザー', function () {
    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id];

    $response = $this->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(401);
    $response->assertJson(['success' => false, 'message' => '認証が必要です。']);
});

test('3-7-189: 【更新】 グループが存在しない', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $user->id];

    $response = $this->actingAs($user)->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(422);
    $response->assertJson(['success' => false, 'message' => 'ユーザーはグループに所属していません。']);
});

test('3-7-190: 【更新】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('update')->once()->andThrow(new \Exception('Database connection failed'));
    });

    $data = ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id];

    $response = $this->actingAs($this->user)->put('/recipes/00000000-0000-0000-0000-000000000000', $data);

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);
});

// ===== destroy() メソッドのテストケース =====

test('3-7-191: 【削除】 正常な料理削除', function () {
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

test('3-7-192: 【削除】 削除成功メッセージの確認', function () {
    $createResponse = $this->actingAs($this->user)->post('/recipes', ['name' => 'カレーライス', 'servingCount' => 4, 'ownerUserId' => $this->user->id]);
    $recipeId = getRecipeIdAfterStore($this->group);

    $response = $this->actingAs($this->user)->delete("/recipes/{$recipeId}");

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toContain('カレーライス');
});

test('3-7-193: 【削除】 存在しない料理削除', function () {
    $response = $this->actingAs($this->user)->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404);
    $response->assertJson(['success' => false]);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-194: 【削除】 他グループの料理削除', function () {
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

test('3-7-195: 【削除】 同一グループの他ユーザーの料理削除', function () {
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

test('3-7-196: 【削除】 未認証ユーザー', function () {
    $response = $this->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(401);
    $response->assertJson(['success' => false, 'message' => '認証が必要です。']);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-197: 【削除】 グループが存在しない', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(422);
    $response->assertJson(['success' => false, 'message' => 'ユーザーはグループに所属していません。']);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);

    // Content-Typeがapplication/jsonであることを確認
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-7-198: 【削除】 データベース接続エラー', function () {
    $this->mock(\App\Services\RecipeService::class, function ($mock) {
        $mock->shouldReceive('delete')->once()->andThrow(new \Exception('Database connection failed'));
    });

    $response = $this->actingAs($this->user)->delete('/recipes/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(500);
    $response->assertJson(['success' => false]);

    // レスポンス構造の確認
    $response->assertJsonStructure(['success', 'message']);
});
