<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="MealPlan",
 *     required={"date", "meals"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="date", type="string", format="date", description="日付", example="2023-10-05"),
 *     @OA\Property(property="meals", type="array", description="献立メニュー",
 *         @OA\Items(ref="#/components/schemas/MealPlanItem")
 *     )
 * )
 *
 * 献立1品。GET レスポンスの meals の要素型（レシピ情報＋献立カテゴリID）。
 *
 * @OA\Schema(
 *     schema="MealPlanItem",
 *     required={"id", "recipeId", "recipeOrder", "recipeName", "categoryId", "order"},
 *     @OA\Property(property="id", type="string", description="レシピID", example="1"),
 *     @OA\Property(property="categoryId", type="string", format="uuid", description="献立カテゴリID"),
 *     @OA\Property(property="order", type="integer", description="表示順", example=0),
 *     @OA\Property(property="recipeId", type="string", format="uuid", description="レシピID"),
 *     @OA\Property(property="recipeName", type="string", description="料理名", example="ハンバーグ"),
 *     @OA\Property(property="recipeThumbnail", ref="#/components/schemas/Image", nullable=true, description="サムネイル画像情報"),
 *     @OA\Property(property="recipeOrder", type="integer", description="1食内のレシピ並び順（0始まり）", example=0),
 *     @OA\Property(
 *         property="ingredients",
 *         type="array",
 *         nullable=true,
 *         description="レシピの食材一覧（include_ingredients=true の場合のみ）",
 *         @OA\Items(ref="#/components/schemas/IngredientItem")
 *     ),
 * )
 *
 * POST/PUT 献立リクエスト用。レスポンスの meals は上記フラット配列（MealPlanItem[]）。
 *
 * @OA\Schema(
 *     schema="Meal",
 *     required={"id", "categoryId", "order", "recipes"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="categoryId", type="string", format="uuid", description="献立カテゴリID"),
 *     @OA\Property(property="order", type="integer", description="表示順", example=0),
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
