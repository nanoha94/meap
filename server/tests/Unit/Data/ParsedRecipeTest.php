<?php

use App\Data\ParsedRecipe;
use App\Support\ValidationLimits;
use Tests\TestCase;

uses(TestCase::class);

function validParsedRecipePayload(): array
{
    return [
        'name' => 'テストレシピ',
        'servingCount' => 2,
        'ingredients' => [
            [
                'name' => '玉ねぎ',
                'quantity' => 1,
                'quantityDisplay' => '1',
                'unitName' => '個',
                'categoryName' => '',
            ],
        ],
        'steps' => [
            ['instruction' => '玉ねぎを切る'],
        ],
    ];
}

// ===== fromArray() メソッドのテストケース =====

test('7-1-1: 【fromArray】 正常な OpenAI レスポンスを ParsedRecipe に変換できる', function () {
    $parsed = ParsedRecipe::fromArray(validParsedRecipePayload());

    expect($parsed->name)->toBe('テストレシピ');
    expect($parsed->servingCount)->toBe(2);
    expect($parsed->ingredients)->toHaveCount(1);
    expect($parsed->steps)->toHaveCount(1);
});

test('7-1-2: 【fromArray】 ingredients が件数上限超過のとき InvalidArgumentException', function () {
    $ingredients = array_fill(0, ValidationLimits::RECIPE_INGREDIENTS_MAX + 1, [
        'name' => '材料',
        'quantity' => 1,
        'quantityDisplay' => '1',
        'unitName' => '個',
        'categoryName' => '',
    ]);

    expect(fn () => ParsedRecipe::fromArray([
        ...validParsedRecipePayload(),
        'ingredients' => $ingredients,
    ]))->toThrow(InvalidArgumentException::class, '材料は100個以下で指定してください。');
});

test('7-1-3: 【fromArray】 steps が件数上限超過のとき InvalidArgumentException', function () {
    $steps = array_fill(0, ValidationLimits::RECIPE_STEPS_MAX + 1, [
        'instruction' => '手順',
    ]);

    expect(fn () => ParsedRecipe::fromArray([
        ...validParsedRecipePayload(),
        'steps' => $steps,
    ]))->toThrow(InvalidArgumentException::class, '手順は100個以下で指定してください。');
});

test('7-1-4: 【fromArray】 name が文字列上限超過のとき InvalidArgumentException', function () {
    expect(fn () => ParsedRecipe::fromArray([
        ...validParsedRecipePayload(),
        'name' => str_repeat('あ', ValidationLimits::STRING_MAX + 1),
    ]))->toThrow(InvalidArgumentException::class, 'レシピ名は255文字以内で指定してください。');
});

test('7-1-5: 【fromArray】 ingredients.*.name が文字列上限超過のとき InvalidArgumentException', function () {
    expect(fn () => ParsedRecipe::fromArray([
        ...validParsedRecipePayload(),
        'ingredients' => [[
            'name' => str_repeat('あ', ValidationLimits::STRING_MAX + 1),
            'quantity' => 1,
            'quantityDisplay' => '1',
            'unitName' => '個',
            'categoryName' => '',
        ]],
    ]))->toThrow(InvalidArgumentException::class, '材料名は255文字以内で指定してください。');
});

test('7-1-6: 【fromArray】 steps.*.instruction が文字列上限超過のとき InvalidArgumentException', function () {
    expect(fn () => ParsedRecipe::fromArray([
        ...validParsedRecipePayload(),
        'steps' => [[
            'instruction' => str_repeat('あ', ValidationLimits::STRING_MAX + 1),
        ]],
    ]))->toThrow(InvalidArgumentException::class, '調理手順は255文字以内で指定してください。');
});
