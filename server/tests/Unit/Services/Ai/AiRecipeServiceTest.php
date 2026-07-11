<?php

use App\Data\ParsedRecipe;
use App\Models\Color;
use App\Models\Group;
use App\Models\IngredientUnit;
use App\Models\User;
use App\Services\Ai\AiRecipeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([
        ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
        ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
        ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
    ] as $color) {
        Color::create($color);
    }

    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->group = Group::createGroup();
    $this->group->users()->attach($this->user->id);

    $this->service = app(AiRecipeService::class);
});

// ===== normalizeParsedRecipe() メソッドのテストケース =====

test('4-4-1: 【normalizeParsedRecipe】 quantity のみの材料は DB 単位に基づき display を補完する', function () {
    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => 'テストレシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'quantity' => 1,
                'quantityDisplay' => null,
                'unitName' => '個',
                'categoryName' => '野菜',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBe(1.0);
    expect($ingredient->quantityDisplay)->toBe('1');
});

test('4-4-2: 【normalizeParsedRecipe】 requires_quantity=false の DB 単位は両方 null', function () {
    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => '調味料レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '塩',
                'quantity' => 1,
                'quantityDisplay' => '1',
                'unitName' => '適量',
                'categoryName' => '調味料',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBeNull();
    expect($ingredient->quantityDisplay)->toBeNull();
});

test('4-4-3: 【normalizeParsedRecipe】 グループ独自の requires_quantity=false 単位を反映する', function () {
    IngredientUnit::create([
        'group_id' => $this->group->id,
        'name' => 'たっぷり',
        'position' => 'prefix',
        'requires_quantity' => false,
        'order' => 99,
        'is_default' => false,
    ]);

    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => 'テスト',
        'servingCount' => null,
        'ingredients' => [
            [
                'name' => '油',
                'quantity' => 2,
                'quantityDisplay' => '2',
                'unitName' => 'たっぷり',
                'categoryName' => '調味料',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBeNull();
    expect($ingredient->quantityDisplay)->toBeNull();
});

test('4-4-4: 【normalizeParsedRecipe】 DB に存在しない unitName は requires_quantity=true として正規化する', function () {
    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => 'テストレシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '小麦粉',
                'quantity' => 200,
                'quantityDisplay' => null,
                'unitName' => 'グラム',
                'categoryName' => '粉類',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBe(200.0);
    expect($ingredient->quantityDisplay)->toBe('200');
    expect($ingredient->unitName)->toBe('グラム');
});

test('4-4-5: 【normalizeParsedRecipe】 帯分数 display のスペース区切りを保持する', function () {
    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => '分数レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'quantity' => 1.5,
                'quantityDisplay' => '1 1/2',
                'unitName' => '個',
                'categoryName' => '野菜',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBe(1.5);
    expect($ingredient->quantityDisplay)->toBe('1 1/2');
});

test('4-4-6: 【normalizeParsedRecipe】 帯分数 display の「と」区切りを保持する', function () {
    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => '分数レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '塩',
                'quantity' => 1.5,
                'quantityDisplay' => '1と1/2',
                'unitName' => '大さじ',
                'categoryName' => '調味料',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBe(1.5);
    expect($ingredient->quantityDisplay)->toBe('1と1/2');
});

test('4-4-7: 【normalizeParsedRecipe】 quantity と display が矛盾する場合は quantity を優先する', function () {
    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => '矛盾レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '塩',
                'quantity' => 1,
                'quantityDisplay' => '1/2',
                'unitName' => '大さじ',
                'categoryName' => '調味料',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBe(1.0);
    expect($ingredient->quantityDisplay)->toBe('1');
});

test('4-4-8: 【normalizeParsedRecipe】 quantityDisplay に混入した prefix 単位名を除去する', function () {
    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => 'prefix 混入レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '塩',
                'quantity' => 1,
                'quantityDisplay' => '大さじ1',
                'unitName' => '大さじ',
                'categoryName' => '調味料',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBe(1.0);
    expect($ingredient->quantityDisplay)->toBe('1');
});

test('4-4-9: 【normalizeParsedRecipe】 quantityDisplay に混入した suffix 単位名を除去する', function () {
    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => 'suffix 混入レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'quantity' => 1,
                'quantityDisplay' => '1個',
                'unitName' => '個',
                'categoryName' => '野菜',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBe(1.0);
    expect($ingredient->quantityDisplay)->toBe('1');
});

test('4-4-10: 【normalizeParsedRecipe】 全角 quantityDisplay を半角に正規化する', function () {
    $parsedRecipe = ParsedRecipe::fromArray([
        'name' => '全角分数レシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '塩',
                'quantity' => 1.5,
                'quantityDisplay' => '１と１／２',
                'unitName' => '大さじ',
                'categoryName' => '調味料',
            ],
        ],
        'steps' => [],
    ]);

    $normalized = $this->service->normalizeParsedRecipe($parsedRecipe, $this->group);
    $ingredient = $normalized->ingredients[0];

    expect($ingredient->quantity)->toBe(1.5);
    expect($ingredient->quantityDisplay)->toBe('1と1/2');
});
