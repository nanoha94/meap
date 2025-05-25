<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="ShoppingItemRequest",
 *     description="登録する買い物アイテムデータ",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/ShoppingItem")
 * ), 
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
 * ),
 * @OA\RequestBody(
 *     request="ShoppingItemBulkDeleteRequest",
 *     description="一括削除する買い物アイテムデータ",
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
 * ),
 * @OA\RequestBody(
 *     request="ShoppingCategoryRequest",
 *     description="登録する買い物カテゴリデータ",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/ShoppingCategory")
 * )
 */

class ShoppingRequests {}
