<?php

namespace App\Swagger;

/**
 * 
 * @OA\Response(
 *     response="IngredientCategoryIndexSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="食材カテゴリ一覧",
 *             @OA\Items(ref="#/components/schemas/IngredientCategory")
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="買い物カテゴリ総数",
 *             example=100
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="IngredientCategoryStoreSuccess",
 *     description="食材カテゴリーを作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/IngredientCategory")
 * )
 * @OA\Response(
 *     response="IngredientCategoryBulkUpdateSuccess",
 *     description="正常に更新されました",
 *     @OA\JsonContent(ref="#/components/schemas/IngredientCategory")
 * )
 * @OA\Response(
 *     response="IngredientCategoryBulkDestroySuccess",
 *     description="正常に削除されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="ids", type="array", @OA\Items(type="string"))
 *     )
 * )
 * 
 */

class IngredientResponse {}
