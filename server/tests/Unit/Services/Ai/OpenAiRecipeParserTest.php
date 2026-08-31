<?php

use App\Enums\HttpStatusCode;
use App\Interfaces\RecipeOcrInterface;
use App\Services\Ai\OpenAiRecipeParser;
use App\Support\ValidationLimits;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

uses(TestCase::class);

const OPENAI_RECIPE_PARSER_TEST_MAX_TOKENS = 8192;

function makeOpenAiRecipeParser(string $ocrText = 'テストレシピ'): OpenAiRecipeParser
{
    $ocr = Mockery::mock(RecipeOcrInterface::class);
    $ocr->shouldReceive('extract')
        ->once()
        ->andReturn($ocrText);

    return new OpenAiRecipeParser($ocr);
}

function validStructuredRecipeJson(): string
{
    return json_encode([
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
    ], JSON_THROW_ON_ERROR);
}

function fakeOpenAiStructuredResponse(string $content): void
{
    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                [
                    'message' => [
                        'content' => $content,
                    ],
                ],
            ],
        ]),
    ]);
}

function expectOpenAiRecipeParserServerError(callable $callback): void
{
    try {
        $callback();
        test()->fail('Expected HttpException was not thrown.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(HttpStatusCode::BAD_GATEWAY->value);
        expect($e->getMessage())->toBe('サーバー内部エラーが発生しました。');
    }
}

// ===== parseImage() メソッドのテストケース =====

test('4-6-1: 【parseImage】 OpenAI 構造化リクエストに config の max_tokens を付与する', function () {
    config([
        'ai.models.text' => 'gpt-4o',
        'ai.structure_max_tokens' => OPENAI_RECIPE_PARSER_TEST_MAX_TOKENS,
    ]);

    fakeOpenAiStructuredResponse(validStructuredRecipeJson());

    $parser = makeOpenAiRecipeParser();
    $base64Image = base64_encode('recipe-image-bytes');

    $parsed = $parser->parseImage($base64Image, ['個']);

    expect($parsed->name)->toBe('テストレシピ');

    OpenAI::chat()->assertSent(function (string $method, array $parameters): bool {
        return $method === 'create'
            && ($parameters['max_tokens'] ?? null) === OPENAI_RECIPE_PARSER_TEST_MAX_TOKENS
            && ($parameters['response_format']['type'] ?? null) === 'json_object';
    });
});

test('4-6-2: 【parseImage】 ParsedRecipe 検証失敗時は 502 を投げる', function () {
    config([
        'ai.models.text' => 'gpt-4o',
        'ai.structure_max_tokens' => OPENAI_RECIPE_PARSER_TEST_MAX_TOKENS,
    ]);

    $tooManyIngredients = array_fill(0, ValidationLimits::RECIPE_INGREDIENTS_MAX + 1, [
        'name' => '材料',
        'quantity' => 1,
        'quantityDisplay' => '1',
        'unitName' => '個',
        'categoryName' => '',
    ]);

    fakeOpenAiStructuredResponse(json_encode([
        'name' => 'テストレシピ',
        'servingCount' => 2,
        'ingredients' => $tooManyIngredients,
        'steps' => [],
    ], JSON_THROW_ON_ERROR));

    $parser = makeOpenAiRecipeParser();
    $base64Image = base64_encode('recipe-image-bytes');

    expectOpenAiRecipeParserServerError(
        fn () => $parser->parseImage($base64Image, ['個']),
    );
});
