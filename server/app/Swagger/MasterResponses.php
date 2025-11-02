<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="MasterSuccess",
 *     description="マスターデータを取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="マスターデータを取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="object",
 *             @OA\Property(
 *                 property="recipeCategories",
 *                 type="array",
 *                 description="料理カテゴリ一覧",
 *                 @OA\Items(ref="#/components/schemas/RecipeCategory")
 *             ),
 *             @OA\Property(
 *                 property="ingredientCategories",
 *                 type="array",
 *                 description="食材カテゴリ一覧",
 *                 @OA\Items(ref="#/components/schemas/IngredientCategory")
 *             ),
 *             @OA\Property(
 *                 property="ingredientUnits",
 *                 type="array",
 *                 description="食材単位一覧",
 *                 @OA\Items(ref="#/components/schemas/IngredientUnit")
 *             ),
 *             @OA\Property(
 *                 property="menuCategories",
 *                 type="array",
 *                 description="メニュー種別一覧",
 *                 @OA\Items(ref="#/components/schemas/MenuCategory")
 *             ),
 *             @OA\Property(
 *                 property="shoppingTags",
 *                 type="array",
 *                 description="買い物タグ一覧",
 *                 @OA\Items(ref="#/components/schemas/ShoppingTag")
 *             )
 *         )
 *     )
 * )
 */
class MasterResponses {}
