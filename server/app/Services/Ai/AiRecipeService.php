<?php

namespace App\Services\Ai;

use App\Data\ParsedRecipe;
use App\Data\ParsedRecipeIngredient;
use App\Helpers\Quantity;
use App\Models\Group;
use App\Models\IngredientUnit;
use Illuminate\Support\Collection;

class AiRecipeService
{
    /**
     * グループの単位マスタに基づき、AI 解析結果の quantity / quantityDisplay を正規化する。
     */
    public function normalizeParsedRecipe(ParsedRecipe $parsedRecipe, Group $group): ParsedRecipe
    {
        $unitsKeyedByName = $this->loadUnitsKeyedByName($group);

        $ingredients = array_map(
            fn(ParsedRecipeIngredient $ingredient) => $this->normalizeIngredient($ingredient, $unitsKeyedByName),
            $parsedRecipe->ingredients,
        );

        return new ParsedRecipe(
            name: $parsedRecipe->name,
            servingCount: $parsedRecipe->servingCount,
            ingredients: $ingredients,
            steps: $parsedRecipe->steps,
        );
    }

    /**
     * グループの単位マスタを全件取得し、name をキーとした Collection で返す。
     *
     * @return Collection<string, IngredientUnit>
     */
    private function loadUnitsKeyedByName(Group $group): Collection
    {
        return $group->ingredientUnits()
            ->get(['name', 'position', 'requires_quantity'])
            ->keyBy('name');
    }

    /**
     * AI 解析結果の ingredient を正規化する。
     * @param  ParsedRecipeIngredient  $ingredient
     * @param  Collection<string, IngredientUnit>  $unitsKeyedByName
     * @return ParsedRecipeIngredient
     */
    private function normalizeIngredient(
        ParsedRecipeIngredient $ingredient,
        Collection $unitsKeyedByName,
    ): ParsedRecipeIngredient {
        // 単位マスタから requires_quantity / position を取得する。
        $unit = $unitsKeyedByName->get(trim($ingredient->unitName));
        $requiresQuantity = $unit?->requires_quantity ?? true;

        // quantityDisplay に混入した単位名を除去する。
        $quantityDisplay = $ingredient->quantityDisplay;
        if ($quantityDisplay !== null) {
            $quantityDisplay = Quantity::stripUnitFromDisplay(
                $quantityDisplay,
                $ingredient->unitName,
                $unit?->position,
            );
        }

        // quantity / quantityDisplay のペアを正規化する。
        ['quantity' => $quantity, 'quantityDisplay' => $quantityDisplay] = Quantity::normalizeQuantityPair(
            $ingredient->quantity,
            $quantityDisplay,
            $requiresQuantity,
        );

        return new ParsedRecipeIngredient(
            name: $ingredient->name,
            quantity: $quantity,
            quantityDisplay: $quantityDisplay,
            unitName: $ingredient->unitName,
            categoryName: $ingredient->categoryName,
        );
    }
}
