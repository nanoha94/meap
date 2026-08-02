<?php

namespace App\Swagger;

/**
 * マスターデータ
 *
 * @OA\Schema(
 *     schema="Master",
 *     required={"users", "recipeCategories", "ingredientUnits", "mealCategories", "shoppingCategories", "shoppingTags"},
 *     @OA\Property(
 *         property="users",
 *         type="array",
 *         description="ユーザー一覧",
 *         @OA\Items(ref="#/components/schemas/User")
 *     ),
 *     @OA\Property(
 *         property="recipeCategories",
 *         type="array",
 *         description="料理カテゴリ一覧",
 *         @OA\Items(ref="#/components/schemas/RecipeCategory")
 *     ),
 *     @OA\Property(
 *         property="ingredientUnits",
 *         type="array",
 *         description="食材単位一覧",
 *         @OA\Items(ref="#/components/schemas/IngredientUnit")
 *     ),
 *     @OA\Property(
 *         property="mealCategories",
 *         type="array",
 *         description="献立カテゴリ―一覧",
 *         @OA\Items(ref="#/components/schemas/MealCategory")
 *     ),
 *     @OA\Property(
 *         property="shoppingCategories",
 *         type="array",
 *         description="買い物カテゴリ一覧",
 *         @OA\Items(ref="#/components/schemas/ShoppingCategory")
 *     ),
 *     @OA\Property(
 *         property="shoppingTags",
 *         type="array",
 *         description="買い物タグ一覧",
 *         @OA\Items(ref="#/components/schemas/ShoppingTag")
 *     )
 * )
 *
 * マスターデータ取得レスポンス（success, message, data: Master）
 *
 * @OA\Schema(
 *     schema="MasterResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="マスターデータを取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/Master", description="マスターデータ")
 * )
 *
 * @OA\Response(
 *     response="MasterSuccess",
 *     description="マスターデータを取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/MasterResponse")
 * )
 */
class MasterResponses {}
