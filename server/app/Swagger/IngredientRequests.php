<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="IngredientCategoryBulkStoreRequest",
 *     description="一括作成する食材カテゴリデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(type="object",
 *                 @OA\Property(property="name", type="string", description="食材カテゴリ名", example="野菜"),
 *                 @OA\Property(property="order", type="integer", description="食材カテゴリ順序", example="1")
 *             )
 *         )
 *     )
 * )
 * 
 * @OA\RequestBody(
 *     request="IngredientCategoryBulkUpdateRequest",
 *     description="一括更新する食材カテゴリデータ",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(type="object",
 *                 @OA\Property(property="id", type="string", description="食材カテゴリID", example="1"),
 *                 @OA\Property(property="name", type="string", description="食材カテゴリ名", example="野菜"),
 *                 @OA\Property(property="order", type="integer", description="食材カテゴリ順序", example="1")
 *             )
 *         )
 *     )
 * )
 * 
 * @OA\RequestBody(
 *     request="IngredientCategoryBulkDestroyRequest",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="ids",
 *             type="array",
 *             @OA\Items(type="string", description="カテゴリID", example="1")
 *         )
 *     )
 * )
 */

class IngredientRequests {}
