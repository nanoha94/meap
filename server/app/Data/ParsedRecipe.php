<?php

namespace App\Data;

use App\Support\ValidationLimits;
use InvalidArgumentException;

readonly class ParsedRecipe
{
    /**
     * @param  ParsedRecipeIngredient[]  $ingredients
     * @param  ParsedRecipeStep[]  $steps
     */
    public function __construct(
        public string $name,
        public ?int $servingCount,
        public array $ingredients,
        public array $steps,
    ) {}

    /**
     * OpenAI レスポンスの構造を配列に変換する。
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'servingCount' => $this->servingCount,
            'ingredients' => array_map(
                fn(ParsedRecipeIngredient $ingredient) => $ingredient->toArray(),
                $this->ingredients,
            ),
            'steps' => array_map(
                fn(ParsedRecipeStep $step) => $step->toArray(),
                $this->steps,
            ),
        ];
    }

    /**
     * 配列から ParsedRecipe オブジェクトに変換する。
     * @param  array<string, mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        self::validateStructure($data);

        $servingCount = $data['servingCount'] ?? null;

        $ingredients = array_map(
            fn(array $ingredient) => ParsedRecipeIngredient::fromArray($ingredient),
            $data['ingredients'] ?? [],
        );

        $steps = array_map(
            fn(array $step) => ParsedRecipeStep::fromArray($step),
            $data['steps'] ?? [],
        );

        return new self(
            name: (string) ($data['name'] ?? ''),
            servingCount: $servingCount === null || $servingCount === '' ? null : (int) $servingCount,
            ingredients: $ingredients,
            steps: $steps,
        );
    }

    /**
     * レスポンスの件数・文字列長を検証する。
     *
     * @param  array<string, mixed>  $data
     */
    private static function validateStructure(array $data): void
    {
        self::assertStringLength('name', (string) ($data['name'] ?? ''), ValidationLimits::STRING_MAX);

        $ingredients = $data['ingredients'] ?? [];
        if (! is_array($ingredients)) {
            self::throwMustBeArray('ingredients');
        }
        if (count($ingredients) > ValidationLimits::RECIPE_INGREDIENTS_MAX) {
            self::throwExceedsMaxCount('ingredients', ValidationLimits::RECIPE_INGREDIENTS_MAX);
        }

        foreach ($ingredients as $index => $ingredient) {
            if (! is_array($ingredient)) {
                self::throwMustBeArray("ingredients.{$index}");
            }

            self::assertStringLength(
                "ingredients.{$index}.name",
                (string) ($ingredient['name'] ?? ''),
                ValidationLimits::STRING_MAX,
            );
            self::assertOptionalStringLength(
                "ingredients.{$index}.quantityDisplay",
                $ingredient['quantityDisplay'] ?? null,
                ValidationLimits::STRING_MAX,
            );
            self::assertStringLength(
                "ingredients.{$index}.unitName",
                (string) ($ingredient['unitName'] ?? ''),
                ValidationLimits::STRING_MAX,
            );
            self::assertStringLength(
                "ingredients.{$index}.categoryName",
                (string) ($ingredient['categoryName'] ?? ''),
                ValidationLimits::STRING_MAX,
            );
        }

        $steps = $data['steps'] ?? [];
        if (! is_array($steps)) {
            self::throwMustBeArray('steps');
        }
        if (count($steps) > ValidationLimits::RECIPE_STEPS_MAX) {
            self::throwExceedsMaxCount('steps', ValidationLimits::RECIPE_STEPS_MAX);
        }

        foreach ($steps as $index => $step) {
            if (! is_array($step)) {
                self::throwMustBeArray("steps.{$index}");
            }

            self::assertStringLength(
                "steps.{$index}.instruction",
                (string) ($step['instruction'] ?? ''),
                ValidationLimits::STRING_MAX,
            );
        }
    }

    /**
     * 文字列長を検証する。
     */
    private static function assertStringLength(string $field, string $value, int $max): void
    {
        if (mb_strlen($value) > $max) {
            throw new InvalidArgumentException(__('validation.parsed_recipe.exceeds_max_length', [
                'attribute' => self::attributeLabel($field),
                'max' => $max,
            ]));
        }
    }

    /**
     * オプションの文字列長を検証する。
     */
    private static function assertOptionalStringLength(string $field, mixed $value, int $max): void
    {
        if ($value === null || $value === '') {
            return;
        }

        self::assertStringLength($field, (string) $value, $max);
    }

    /**
     * 配列でない場合にエラーを投げる。
     */
    private static function throwMustBeArray(string $field): never
    {
        throw new InvalidArgumentException(__('validation.parsed_recipe.must_be_array', [
            'attribute' => self::attributeLabel($field),
        ]));
    }

    /**
     * 配列の要素数が上限を超えている場合にエラーを投げる。
     */
    private static function throwExceedsMaxCount(string $field, int $max): never
    {
        throw new InvalidArgumentException(__('validation.parsed_recipe.exceeds_max_count', [
            'attribute' => self::attributeLabel($field),
            'max' => $max,
        ]));
    }

    /**
     * 属性ラベルを取得する。
     */
    private static function attributeLabel(string $field): string
    {
        if ($field === 'name') {
            return __('validation.attributes.recipe.name');
        }

        $normalized = self::normalizeAttributeKey($field);
        $attributes = trans('validation.attributes');

        if (is_array($attributes) && isset($attributes[$normalized]) && is_string($attributes[$normalized])) {
            return $attributes[$normalized];
        }

        return $field;
    }

    /**
     * 属性キーを正規化する。
     */
    private static function normalizeAttributeKey(string $field): string
    {
        $normalized = preg_replace('/\.\d+\./', '.*.', $field) ?? $field;

        return preg_replace('/\.\d+$/', '.*', $normalized) ?? $normalized;
    }
}
