<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="ShoppingItemStoreRequest",
 *     description="登録する買い物アイテムデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         @OA\Property(property="name", type="string", description="買い物アイテム名（数量込み）", example="ひき肉100g"),
 *         @OA\Property(property="categoryId", type="string", description="カテゴリID", example="1"),
 *     )
 * )
 * 
 * @OA\RequestBody(
 *     request="ShoppingItemBulkUpdateRequest",
 *     description="一括更新する買い物アイテムデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="items",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/ShoppingItem")
 *         )
 *     )
 * )
 * 
 * @OA\RequestBody(
 *     request="ShoppingItemBulkDestroyRequest",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="itemIds",
 *             type="array",
 *             description="削除する買い物アイテムのID配列",
 *             @OA\Items(
 *                 type="string",
 *                 description="買い物アイテムID",
 *                 example="2"
 *             )
 *         )
 *     )
 * )
 * 
 * @OA\RequestBody(
 *     request="ShoppingCategoryStoreRequest",
 *     description="登録する買い物カテゴリデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         @OA\Property(property="name", type="string", description="カテゴリ名", example="スーパーA")
 *     )
 * )
 * 
 * @OA\RequestBody(
 *     request="ShoppingCategoryBulkUpdateRequest",
 *     description="一括更新する買い物カテゴリデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="categories",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/ShoppingCategory")
 *         )
 *     )
 * )
 */

class ShoppingRequests {}
