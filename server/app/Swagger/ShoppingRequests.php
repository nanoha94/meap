<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="ShoppingItemStoreRequest",
 *     description="登録する買い物アイテムデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         required={"name", "categoryId"},
 *         @OA\Property(property="name", type="string", description="買い物アイテム名（数量込み）", example="ひき肉100g"),
 *         @OA\Property(property="categoryId", type="string", description="カテゴリID", example="1"),
 *         @OA\Property(
 *             property="tags",
 *             type="array",
 *             description="買い物タグ配列",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="string", description="買い物タグID", example="1", nullable=true),
 *                 @OA\Property(property="name", type="string", description="買い物タグ名", example="サラダ")
 *             )
 *         ),
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
 *             property="data",
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
 *             property="ids",
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
 *     required=true,
 *     @OA\JsonContent(type="object", 
 *      @OA\Property(property="name", type="string", description="買い物カテゴリ名", example="食料品"), 
 *      @OA\Property(property="order", type="integer", description="買い物カテゴリ順序", example="1"))
 * )
 * 
 * @OA\RequestBody(
 *     request="ShoppingCategoryBulkUpdateRequest",
 *     description="一括更新する買い物カテゴリデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(type="object", 
 *              @OA\Property(property="id", type="string", description="買い物カテゴリID", example="1"), 
 *              @OA\Property(property="name", type="string", description="買い物カテゴリ名", example="食料品"), 
 *              @OA\Property(property="order", type="integer", description="買い物カテゴリ順序", example="1"))
 *         )
 *     )
 * )
 * 
 * @OA\RequestBody(
 *     request="ShoppingCategoryBulkDestroyRequest",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="ids",
 *             type="array",
 *             @OA\Items(type="string", description="買い物カテゴリID", example="1")
 *         )
 *     )
 * )
 * 
 * @OA\RequestBody(
 *     request="ShoppingTagStoreRequest",
 *     description="登録する買い物タグデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="name", type="string", description="買い物タグ名", example="サラダ")
 *     )
 * )
 */

class ShoppingRequests {}
