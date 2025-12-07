<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="MealPlanRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         required={"date", "categoryId"},
 *         @OA\Property(property="date", type="string", format="date", description="日付", example="2023-10-05"),
 *         @OA\Property(property="mealCategoryId", type="string", description="種別ID", example="1"),
 *         @OA\Property(
 *             property="menu",
 *             type="array",
 *             description="献立メニュー",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="categoryId", type="string", description="メニュー種別ID", example="1"),
 *                 @OA\Property(
 *                     property="recipeIds",
 *                     type="array",
 *                     description="料理ID",
 *                     @OA\Items(type="string", description="料理ID", example="1")
 *                 )
 *             )
 *         )
 *     )
 * )
 * @OA\RequestBody(
 *     request="MealCategoryRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         required={"name", "colorId"},
 *         @OA\Property(property="name", type="string", description="カテゴリ名", example="朝食"),
 *         @OA\Property(property="colorId", type="string", description="色ID", example="1"),
 *         @OA\Property(property="order", type="integer", description="ソート順", example=1)
 *     )
 * )
 * @OA\RequestBody(
 *     request="MealCategoryBulkUpdateRequest",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/MealCategory")
 *         )
 *     )
 * )
 * @OA\RequestBody(
 *     request="MealCategoryBulkDestroyRequest",
 *     required=true,
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="ids",
 *             type="array",
 *             description="削除する献立カテゴリのID配列",
 *             @OA\Items(
 *                 type="string",
 *                 description="献立カテゴリID",
 *                 example="2"
 *             )
 *         )
 *     )
 * )
 */

class MealPlanRequests {}
