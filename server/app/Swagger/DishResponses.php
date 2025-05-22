<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="DishIndexSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="料理データ一覧",
 *             @OA\Items(ref="#/components/schemas/Dish")
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="料理総数",
 *             example=100
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="DishStoreSuccess",
 *     description="正常に登録されました",
 *     @OA\JsonContent(ref="#/components/schemas/Dish")
 * )
 *  * @OA\Response(
 *     response="DishShowSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(ref="#/components/schemas/Dish")
 * )
 * @OA\Response(
 *     response="DishUpdateSuccess",
 *     description="正常に更新されました",
 *     @OA\JsonContent(ref="#/components/schemas/Dish")
 * )
 * @OA\Response(
 *     response="DishDestroySuccess",
 *     description="正常に削除されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string")
 *     )
 * )
 * @OA\Response(
 *     response="DishCategoryStoreSuccess",
 *     description="正常に登録されました",
 *     @OA\JsonContent(ref="#/components/schemas/DishCategory")
 * )
 * @OA\Response(
 *     response="DishCategoryUpdateSuccess",
 *     description="正常に更新されました",
 *     @OA\JsonContent(ref="#/components/schemas/DishCategory")
 * )
 * @OA\Response(
 *     response="DishCategoryDestroySuccess",
 *     description="正常に削除されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string")
 *     )
 * )
 */
class DishResponses {}
