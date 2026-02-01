<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="MealPlan",
 *     required={"date", "mealCategory"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="date", type="string", format="date", description="日付", example="2023-10-05"),
 *     @OA\Property(property="mealCategory", ref="#/components/schemas/MealCategory", description="献立カテゴリ"),
 *     @OA\Property(property="recipes", type="array", description="料理一覧",
 *         @OA\Items(ref="#/components/schemas/RecipeListItem")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="MealCategory",
 *     required={"id", "name", "colorCodeHex"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="カテゴリ名", example="朝食"),
 *     @OA\Property(property="colorCodeHex", type="string", description="色コード（16進）", example="#F5B12E"),
 *     @OA\Property(property="order", type="integer", description="ソート順", example=1)
 * )
 * 
 */

class MealPlanSchemas {}
