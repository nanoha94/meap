<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="RecipeRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="multipart/form-data",
 *         @OA\Schema(
 *             required={"name"},
 *             @OA\Property(property="name", type="string", description="料理名", example="ハンバーグ"),
 *             @OA\Property(property="categoryIds", type="string", description="カテゴリ（JSON文字列）", example="[""1""]"),
 *             @OA\Property(property="ingredients", type="string", description="食材（JSON文字列）", example="[{""id"":""1"",""name"":""牛肉"",""quantity"":100,""unitId"":""1"", ""order"":1}]"),
 *             @OA\Property(property="seasonings", type="string", description="調味料（JSON文字列）", example="[{""id"":""1"",""name"":""塩"",""quantity"":1,""unitId"":""1"", ""order"":1}]"),
 *             @OA\Property(property="thumbnailImage", type="string", format="binary", description="サムネイル画像ファイル"),
 *             @OA\Property(property="url", type="string", description="レシピURL", example="https://www.google.com"),
 *             @OA\Property(property="instructions", type="string", description="レシピ（テキスト入力）", example="ハンバーグを作る"),
 *             @OA\Property(property="memo", type="string", description="メモ", example="ハンバーグは美味しい")
 *         )
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
