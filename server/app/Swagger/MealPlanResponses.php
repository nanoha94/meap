<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="MealPlanIndexSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="mealPlans",
 *             type="array", 
 *             @OA\Items(type="object",
 *                 @OA\Property(property="date", type="string", format="date", description="日付", example="2023-10-05"),
 *                 @OA\Property(property="menu", type="array", description="献立メニュー",
 *                     @OA\Items(ref="#/components/schemas/MealPlan")
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
 *     response="MealPlanStoreSuccess",
 *     description="正常に登録されました",
 *     @OA\JsonContent(ref="#/components/schemas/MealPlan")
 * )
 * @OA\Response(
 *     response="MealPlanShowSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(ref="#/components/schemas/MealPlan")
 * )
 * @OA\Response(
 *     response="MealPlanUpdateSuccess",
 *     description="正常に更新されました",
 *     @OA\JsonContent(ref="#/components/schemas/MealPlan")
 * )
 * @OA\Response(
 *     response="MealPlanDestroySuccess",
 *     description="正常に削除されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="id", type="string")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="MealTypeStoreSuccess",
 *     description="正常に登録されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="categories",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/MealType")
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="MealTypeBulkUpdateSuccess",
 *     description="正常に更新されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="categories",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/MealType")
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="MealTypeDestroySuccess",
 *     description="正常に削除されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="id", type="string")
 *     )
 * )
 * 
 *  * @OA\Response(
 *     response="CourseTypeIndexSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="コースタイプ一覧",
 *             @OA\Items(ref="#/components/schemas/CourseType")
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="コースタイプ総数",
 *             example=100
 *         )
 *     )
 * )
 */
class MealPlanResponses {}
