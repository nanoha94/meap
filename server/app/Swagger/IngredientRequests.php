<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="IngredientCategoryStoreRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/IngredientCategory")
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
 *             @OA\Items(ref="#/components/schemas/IngredientCategory")
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
