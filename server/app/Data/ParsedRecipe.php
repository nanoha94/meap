<?php

namespace App\Data;

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

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'servingCount' => $this->servingCount,
            'ingredients' => array_map(
                fn (ParsedRecipeIngredient $ingredient) => $ingredient->toArray(),
                $this->ingredients,
            ),
            'steps' => array_map(
                fn (ParsedRecipeStep $step) => $step->toArray(),
                $this->steps,
            ),
        ];
    }

    public static function fromArray(array $data): self
    {
        $servingCount = $data['servingCount'] ?? null;

        $ingredients = array_map(
            fn (array $ingredient) => ParsedRecipeIngredient::fromArray($ingredient),
            $data['ingredients'] ?? [],
        );

        $steps = array_map(
            fn (array $step) => ParsedRecipeStep::fromArray($step),
            $data['steps'] ?? [],
        );

        return new self(
            name: (string) ($data['name'] ?? ''),
            servingCount: $servingCount === null || $servingCount === '' ? null : (int) $servingCount,
            ingredients: $ingredients,
            steps: $steps,
        );
    }
}
