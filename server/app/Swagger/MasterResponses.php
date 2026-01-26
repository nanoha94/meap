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
 *                 property="users",
 *                 type="array",
 *                 description="ユーザー一覧",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="string", example="1234567890"),
 *                     @OA\Property(property="name", type="string", example="山田太郎"),
 *                     @OA\Property(property="language", type="string", example="ja", description="ユーザーの言語設定"),
 *                     @OA\Property(property="avatar", type="object",
 *                         @OA\Property(property="seed", type="string", example="1234567890"),
 *                         @OA\Property(property="url", type="string", example="https://example.com/avatar.jpg"),
 *                         @OA\Property(property="width", type="integer", example=300),
 *                         @OA\Property(property="height", type="integer", example=300)
 *                     )
 *                 )
 *             ),
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
 *                 property="shoppingCategories",
 *                 type="array",
 *                 description="買い物カテゴリ一覧",
 *                 @OA\Items(ref="#/components/schemas/ShoppingCategory")
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
