<?php

namespace App\Swagger;

/**
 * 食材カテゴリー一覧取得レスポンス（BaseApiIndexResponse + data: IngredientCategory[]）
 *
 * @OA\Schema(
 *     schema="IngredientCategoryIndexResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="食材カテゴリ一覧",
 *                 @OA\Items(ref="#/components/schemas/IngredientCategory")
 *             )
 *         )
 *     }
 * )
 *
 * 食材単位一覧取得レスポンス（BaseApiIndexResponse + data: IngredientUnit[]）
 *
 * @OA\Schema(
 *     schema="IngredientUnitIndexResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="食材単位一覧",
 *                 @OA\Items(ref="#/components/schemas/IngredientUnit")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Response(
 *     response="IngredientCategoryIndexSuccess",
 *     description="食材カテゴリーを5件取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/IngredientCategoryIndexResponse")
 * )
 * @OA\Response(
 *     response="IngredientUnitIndexSuccess",
 *     description="食材単位を5件取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/IngredientUnitIndexResponse")
 * )
 * @OA\Response(
 *     response="IngredientCategoryBulkStoreSuccess",
 *     description="3件の食材カテゴリーを一括作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="IngredientCategoryBulkUpdateSuccess",
 *     description="3件の食材カテゴリーを更新しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="IngredientCategoryBulkDestroySuccess",
 *     description="2件の食材カテゴリーを削除しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * 
 */

class IngredientResponse {}
