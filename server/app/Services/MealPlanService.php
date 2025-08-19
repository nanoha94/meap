<?php

namespace App\Services;

use App\Models\CourseType;

class MealPlanService
{
    protected RecipeService $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    /**
     * 献立のコースタイプ情報をフォーマット
     */
    public function formatMealPlanCourseType($courseType): array
    {
        return [
            'id' => $courseType->id,
            'name' => $courseType->name
        ];
    }

    /**
     * 献立のレシピ情報をフォーマット
     */
    public function formatMealPlanRecipe($recipe): array
    {
        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'thumbnailUrl' => $recipe->thumbnail_url,
            'url' => $recipe->url,
            'recipe' => $recipe->recipe,
            'memo' => $recipe->memo,
            'categories' => $this->recipeService->formatRecipeCategories($recipe->categories),
            'ingredients' => $this->recipeService->formatRecipeIngredients($recipe->ingredients)
        ];
    }

    /**
     * 献立のメニュー情報をフォーマット
     */
    public function formatMealPlanMenu($recipes): array
    {
        return $recipes->groupBy('pivot.course_type_id')->map(function ($recipes, $courseTypeId) {
            $courseType = CourseType::find($courseTypeId);
            return [
                'courseType' => $this->formatMealPlanCourseType($courseType),
                'recipes' => $recipes->map(fn($recipe) => $this->formatMealPlanRecipe($recipe))
            ];
        })->values()->toArray();
    }

    /**
     * 献立の完全なレスポンスをフォーマット
     */
    public function formatCompleteMealPlanResponse($mealPlan): array
    {
        return [
            'id' => $mealPlan->id,
            'date' => $mealPlan->date,
            'category' => [
                'id' => $mealPlan->mealType->id,
                'name' => $mealPlan->mealType->name,
                'colorId' => $mealPlan->mealType->color_id,
            ],
            'menu' => $this->formatMealPlanMenu($mealPlan->recipes)
        ];
    }
}
