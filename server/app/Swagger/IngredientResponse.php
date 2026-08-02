<?php

namespace App\Swagger;

/**
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
 *     response="IngredientUnitIndexSuccess",
 *     description="食材単位を5件取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/IngredientUnitIndexResponse")
 * )
 */

class IngredientResponse {}
