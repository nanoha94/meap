<?php

namespace App\Swagger;

/**
 * レシピ一覧取得レスポンス（BaseApiIndexResponse + data: Recipe[]）
 *
 * @OA\Schema(
 *     schema="RecipeIndexResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="料理データ一覧",
 *                 @OA\Items(ref="#/components/schemas/RecipeListItem")
 *             )
 *         )
 *     }
 * )
 *
 * レシピ詳細取得レスポンス（success, message, data: Recipe）
 *
 * @OA\Schema(
 *     schema="RecipeShowResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="レシピ(カレーライス)を取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/Recipe")
 * )
 *
 * 料理カテゴリ一覧取得レスポンス（BaseApiIndexResponse + data: RecipeCategory[]）
 *
 * @OA\Schema(
 *     schema="RecipeCategoryIndexResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="料理カテゴリデータ一覧",
 *                 @OA\Items(ref="#/components/schemas/RecipeCategory")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Response(
 *     response="RecipeIndexSuccess",
 *     description="レシピを10件取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/RecipeIndexResponse")
 * )
 * @OA\Response(
 *     response="RecipeStoreSuccess",
 *     description="レシピ(カレーライス)を作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="RecipeShowSuccess",
 *     description="レシピ(カレーライス)を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/RecipeShowResponse")
 * )
 * @OA\Response(
 *     response="RecipeUpdateSuccess",
 *     description="レシピ(カレーライス)を更新しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="RecipeDestroySuccess",
 *     description="レシピ(カレーライス)を削除しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="RecipeCategoryIndexSuccess",
 *     description="料理カテゴリ一覧を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/RecipeCategoryIndexResponse")
 * )
 * @OA\Response(
 *     response="RecipeCategoryStoreSuccess",
 *     description="料理カテゴリー(和食)を作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="RecipeCategoryBulkStoreSuccess",
 *     description="料理カテゴリーを○件作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="RecipeCategoryBulkUpdateSuccess",
 *     description="料理カテゴリーを3件更新しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="RecipeCategoryBulkDestroySuccess",
 *     description="料理カテゴリーを2件削除しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 */
class RecipeResponses {}
