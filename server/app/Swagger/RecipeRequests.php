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
 *             @OA\Property(property="categories", type="string", description="カテゴリ（JSON文字列）", example="[{""id"":""1"",""name"":""肉料理""}]"),
 *             @OA\Property(property="ingredients", type="string", description="食材（JSON文字列）", example="[{""id"":""1"",""name"":""牛肉"",""quantity"":100,""unitId"":""1""}]"),
 *             @OA\Property(property="seasonings", type="string", description="調味料（JSON文字列）", example="[{""id"":""1"",""name"":""塩"",""quantity"":1,""unitId"":""1""}]"),
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
 */

class RecipeRequests {}
