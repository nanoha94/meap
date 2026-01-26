<?php

namespace App\Swagger;

/**
 * 
 * @OA\Response(
 *     response="IngredientCategoryIndexSuccess",
 *     description="食材カテゴリーを5件取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="食材カテゴリーを5件取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="食材カテゴリ一覧",
 *             @OA\Items(ref="#/components/schemas/IngredientCategory")
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="食材カテゴリ総数",
 *             example=5
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="IngredientUnitIndexSuccess",
 *     description="食材単位を5件取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="食材単位を5件取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="食材単位一覧",
 *             @OA\Items(ref="#/components/schemas/IngredientUnit")
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="食材単位総数",
 *             example=5
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="IngredientCategoryBulkStoreSuccess",
 *     description="3件の食材カテゴリーを一括作成しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="3件の食材カテゴリーを一括作成しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="作成された食材カテゴリ一覧",
 *             @OA\Items(ref="#/components/schemas/IngredientCategory")
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="IngredientCategoryBulkUpdateSuccess",
 *     description="3件の食材カテゴリーを更新しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="3件の食材カテゴリーを更新しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/IngredientCategory")
 *     )
 * )
 * @OA\Response(
 *     response="IngredientCategoryBulkDestroySuccess",
 *     description="2件の食材カテゴリーを削除しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="2件の食材カテゴリーを削除しました。"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 * 
 */

class IngredientResponse {}
