<?php

namespace App\Swagger;

/**
 * レシピ作成/更新リクエスト
 *
 * @OA\Schema(
 *     schema="RecipeRequest",
 *     required={"name", "categoryIds", "ownerUserId"},
 *     @OA\Property(property="id", type="string", nullable=true, description="ID（更新時のみ）", example="1"),
 *     @OA\Property(property="name", type="string", description="料理名", example="ハンバーグ"),
 *     @OA\Property(property="url", type="string", nullable=true, description="レシピURL", example="https://www.google.com"),
 *     @OA\Property(property="memo", type="string", nullable=true, description="メモ", example="ハンバーグは美味しい"),
 *     @OA\Property(property="servingCount", type="integer", nullable=true, description="分量（○○人分）", example=4, minimum=1),
 *     @OA\Property(property="thumbnailId", type="string", nullable=true, description="サムネイル画像ID", example="1"),
 *     @OA\Property(property="categoryIds", type="array", description="カテゴリID", @OA\Items(type="string", example="1")),
 *     @OA\Property(property="ownerUserId", type="string", description="編集責任者のユーザーID", example="00000000-0000-0000-0000-000000000000"),
 *     @OA\Property(property="ingredients", type="array", description="食材",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="string", nullable=true, description="ID（更新時）", example="1"),
 *             @OA\Property(property="name", type="string", description="食材名", example="牛肉"),
 *             @OA\Property(property="quantity", type="number", nullable=true, description="量", example=1.5),
 *             @OA\Property(property="unitId", type="string", description="単位ID", example="1"),
 *             @OA\Property(property="categoryId", type="string", description="カテゴリID", example="1"),
 *             @OA\Property(property="order", type="integer", nullable=true, description="並び順", example=1)
 *         )
 *     ),
 *     @OA\Property(property="steps", type="array", description="手順",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="string", description="手順ID", example="1", nullable=true),
 *             @OA\Property(property="instruction", type="string", description="手順", example="ハンバーグを作る"),
 *             @OA\Property(property="imageId", type="string", nullable=true, description="画像ID", example="1"),
 *             @OA\Property(property="order", type="integer", description="並び順", example=1)
 *         )
 *     )
 * )
 *
 * レシピカテゴリー作成リクエスト
 *
 * @OA\Schema(
 *     schema="RecipeCategoryRequest",
 *     required={"name", "order"},
 *     @OA\Property(property="name", type="string", description="カテゴリ名", example="肉料理"),
 *     @OA\Property(property="order", type="integer", description="並び順", example=1)
 * )
 *
 * @OA\RequestBody(
 *     request="RecipeRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/RecipeRequest")
 * )
 * @OA\RequestBody(
 *     request="RecipeCategoryRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/RecipeCategoryRequest")
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
