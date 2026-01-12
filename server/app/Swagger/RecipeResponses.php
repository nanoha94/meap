<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="RecipeIndexSuccess",
 *     description="レシピを10件取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="レシピを10件取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="料理データ一覧",
 *             @OA\Items(
 *                  @OA\Property(property="id", type="string", description="料理ID", example="1"),
 *                  @OA\Property(property="name", type="string", description="料理名", example="ハンバーグ"),
 *                  @OA\Property(property="categories", type="array", description="カテゴリ",
 *                      @OA\Items(ref="#/components/schemas/RecipeCategory")
 *                  ),
 *                  @OA\Property(property="thumbnail", type="object", ref="#/components/schemas/RecipeThumbnail"),
 *             ),
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="料理総数",
 *             example=10
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="RecipeStoreSuccess",
 *     description="レシピ(カレーライス)を作成しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="レシピ(カレーライス)を作成しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/Recipe")
 *     )
 * )
 * @OA\Response(
 *     response="RecipeShowSuccess",
 *     description="レシピ(カレーライス)を取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="レシピ(カレーライス)を取得しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/Recipe")
 *     )
 * )
 * @OA\Response(
 *     response="RecipeUpdateSuccess",
 *     description="レシピ(カレーライス)を更新しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="レシピ(カレーライス)を更新しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/Recipe")
 *     )
 * )
 * @OA\Response(
 *     response="RecipeDestroySuccess",
 *     description="レシピ(カレーライス)を削除しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="レシピ(カレーライス)を削除しました。"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 * @OA\Response(
 *     response="RecipeCategoryIndexSuccess",
 *     description="料理カテゴリ一覧を取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="料理カテゴリ一覧を取得しました。"),
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RecipeCategory"))
 *     )
 * )
 * @OA\Response(
 *     response="RecipeCategoryStoreSuccess",
 *     description="料理カテゴリー(和食)を作成しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="料理カテゴリー(和食)を作成しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/RecipeCategory")
 *     )
 * )
 * @OA\Response(
 *     response="RecipeCategoryBulkUpdateSuccess",
 *     description="料理カテゴリーを3件更新しました。",
 *      @OA\JsonContent(
 *          type="object",
 *          @OA\Property(property="success", type="boolean", example=true),
 *          @OA\Property(property="message", type="string", example="料理カテゴリーを3件更新しました。"),
 *          @OA\Property(property="data", ref="#/components/schemas/RecipeCategory")
 *      )
 * )
 * @OA\Response(
 *     response="RecipeCategoryBulkDestroySuccess",
 *     description="料理カテゴリーを2件削除しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="料理カテゴリーを2件削除しました。"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 */
class RecipeResponses {}
