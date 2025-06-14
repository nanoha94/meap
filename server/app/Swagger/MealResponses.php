<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="MealIndexSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="meals",
 *             type="array", 
 *             @OA\Items(type="object",
 *                 @OA\Property(property="date", type="string", format="date", description="日付", example="2023-10-05"),
 *                 @OA\Property(property="menu", type="array", description="献立メニュー",
 *                     @OA\Items(ref="#/components/schemas/Meal")
 *                 )
 *             )
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="献立総数",
 *             example=100
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="MealStoreSuccess",
 *     description="正常に登録されました",
 *     @OA\JsonContent(ref="#/components/schemas/Meal")
 * )
 * @OA\Response(
 *     response="MealShowSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(ref="#/components/schemas/Meal")
 * )
 * @OA\Response(
 *     response="MealUpdateSuccess",
 *     description="正常に更新されました",
 *     @OA\JsonContent(ref="#/components/schemas/Meal")
 * )
 * @OA\Response(
 *     response="MealDestroySuccess",
 *     description="正常に削除されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="id", type="string")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="MealCategoryStoreSuccess",
 *     description="正常に登録されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="categories",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/MealCategory")
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="MealCategoryBulkUpdateSuccess",
 *     description="正常に更新されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="categories",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/MealCategory")
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="MealCategoryDestroySuccess",
 *     description="正常に削除されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="id", type="string")
 *     )
 * )
 */
class MealResponses {}
