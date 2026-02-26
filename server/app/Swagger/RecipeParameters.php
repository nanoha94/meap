<?php

namespace App\Swagger;

/**
 * @OA\Parameter(
 *     parameter="RecipeLimitParam",
 *     name="limit",
 *     in="query",
 *     description="取得件数（1-100）",
 *     required=false,
 *     @OA\Schema(
 *         type="integer",
 *         default=15,
 *         minimum=1,
 *         maximum=100
 *     )
 * )
 * @OA\Parameter(
 *     parameter="RecipeOffsetParam",
 *     name="offset",
 *     in="query",
 *     description="取得開始位置（0以上）",
 *     required=false,
 *     @OA\Schema(
 *         type="integer",
 *         default=0,
 *         minimum=0
 *     )
 * )
 * @OA\Parameter(
 *     parameter="RecipeIdParam",
 *     name="id",
 *     in="path",
 *     description="料理ID",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         example="1"
 *     )
 * )
 * @OA\Parameter(
 *     parameter="RecipeCategoryIdParam",
 *     name="id",
 *     in="path",
 *     description="料理カテゴリID",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         example="1"
 *     )
 * )
 * 
 * @OA\Parameter(
 *     parameter="RecipeSortParam",
 *     name="sort",
 *     in="query",
 *     description="並び替えキー",
 *     required=false,
 *     @OA\Schema(
 *         type="string",
 *         default="created_at",
 *         enum={"created_at", "last_planned_date", "name"}
 *     )
 * )
 * @OA\Parameter(
 *     parameter="RecipeOrderParam",
 *     name="order",
 *     in="query",
 *     description="並び順",
 *     required=false,
 *     @OA\Schema(
 *         type="string",
 *         default="desc",
 *         enum={"asc", "desc"}
 *     )
 * )
 * @OA\Parameter(
 *     parameter="RecipeRecipeNameParam",
 *     name="recipe_name",
 *     in="query",
 *     description="料理名の部分一致検索",
 *     required=false,
 *     @OA\Schema(type="string", maxLength=255)
 * )
 * @OA\Parameter(
 *     parameter="RecipeIngredientNameParam",
 *     name="ingredient_name",
 *     in="query",
 *     description="食材名の部分一致検索",
 *     required=false,
 *     @OA\Schema(type="string", maxLength=255)
 * )
 * @OA\Parameter(
 *     parameter="RecipeCategoryIdsParam",
 *     name="category_ids",
 *     in="query",
 *     description="カテゴリIDの配列（OR条件）",
 *     required=false,
 *     @OA\Schema(
 *         type="array",
 *         @OA\Items(type="string", format="uuid")
 *     )
 * )
 * @OA\Parameter(
 *     parameter="RecipeLastPlannedDateFromParam",
 *     name="last_planned_date_from",
 *     in="query",
 *     description="前回の献立日の開始日",
 *     required=false,
 *     @OA\Schema(type="string", format="date", example="2025-02-21")
 * )
 * @OA\Parameter(
 *     parameter="RecipeLastPlannedDateToParam",
 *     name="last_planned_date_to",
 *     in="query",
 *     description="前回の献立日の終了日",
 *     required=false,
 *     @OA\Schema(type="string", format="date", example="2025-02-21")
 * )
 */

class RecipeParameters {}
