<?php

namespace App\Services;

use App\Traits\AutoComplement;

class RecipeService
{
    use AutoComplement;

    /**
     * レシピのサムネイル情報をフォーマット
     */
    public function formatImage($image): ?array
    {
        return app(ImageService::class)->formatImage($image);
    }

    /**
     * レシピの手順情報をフォーマット
     */
    public function formatRecipeSteps($steps): array
    {
        return $steps->map(fn($item) => [
            'id' => $item->id,
            'instruction' => $item->instruction,
            'image' => $this->formatImage($item->images->first()),
            'order' => $item->pivot->order,
        ])->toArray();
    }

    /**
     * レシピのカテゴリー情報をフォーマット
     */
    public function formatRecipeCategories($categories): array
    {
        return $categories->sortBy('order')->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'order' => $item->order,
        ])->toArray();
    }

    /**
     * レシピの食材情報をフォーマット
     */
    public function formatRecipeIngredients($ingredients): array
    {
        return $ingredients->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'quantity' => $item->pivot->quantity,
            'unitId' => $item->pivot->unit_id,
            'categoryId' => $item->pivot->category_id,
            'order' => $item->pivot->order
        ])->toArray();
    }

    /**
     * レシピの完全なレスポンスをフォーマット
     */
    public function formatCompleteRecipeResponse($recipe): array
    {
        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'thumbnail' => $this->formatImage($recipe->thumbnails->first()),
            'url' => $recipe->url,
            'steps' => $this->formatRecipeSteps($recipe->steps),
            'memo' => $recipe->memo,
            'categories' => $this->formatRecipeCategories($recipe->categories),
            'ingredients' => $this->formatRecipeIngredients($recipe->ingredients),
        ];
    }
}
