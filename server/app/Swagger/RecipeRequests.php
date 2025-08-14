<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="RecipeRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *    @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="name", type="string", description="料理名", example="ハンバーグ"),
 *         @OA\Property(property="categoryIds", type="array", description="カテゴリID", @OA\Items(type="string", example="1")),
 *         @OA\Property(property="ingredients", type="array", description="食材", @OA\Items(ref="#/components/schemas/Ingredient")),
 *         @OA\Property(property="thumbnailId", type="string", description="サムネイル画像ID", example="1"),
 *         @OA\Property(property="url", type="string", description="レシピURL", example="https://www.google.com"),
 *         @OA\Property(property="steps", type="array", description="手順", 
 *            @OA\Items(
 *                type="object",
 *                @OA\Property(property="instruction", type="string", description="手順", example="ハンバーグを作る"),
 *                @OA\Property(property="imageId", type="string", description="画像ID", example="1"),
 *                @OA\Property(property="order", type="integer", description="並び順", example=1)
 *            )),
 *         @OA\Property(property="memo", type="string", description="メモ", example="ハンバーグは美味しい")
 *     )
 * )
 * @OA\RequestBody(
 *     request="RecipeCategoryRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/RecipeCategory")
 * )
 * @OA\RequestBody(
 *     request="RecipeCategoryBulkUpdateRequest",
 *     description="一括更新する料理カテゴリデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/RecipeCategory")
 *         )
 *     )
 * )
 * @OA\RequestBody(
 *     request="RecipeCategoryBulkDestroyRequest",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="ids",
 *             type="array",
 *             @OA\Items(type="string", description="料理カテゴリID", example="1")
 *         )
 *     )
 * )
 */

class RecipeRequests {}
