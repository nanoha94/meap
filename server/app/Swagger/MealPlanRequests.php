<?php

namespace App\Swagger;

/**
 * 献立作成リクエスト（POST /meal-plans）
 *
 * @OA\Schema(
 *     schema="MealPlanStoreRequest",
 *     required={"date", "data"},
 *     @OA\Property(property="date", type="string", format="date", description="献立の日付（Y-m-d）", example="2025-02-03"),
 *     @OA\Property(property="data", type="array", description="献立メニュー",
 *         @OA\Items(
 *             type="object",
 *             required={"categoryId", "recipeIds"},
 *             @OA\Property(property="id", type="string", format="uuid", description="メニューID（新規の場合は不要）", example="a0fbbf74-1816-406e-99b7-7ef1e3e365f4", nullable=true),
 *             @OA\Property(property="categoryId", type="string", format="uuid", description="献立カテゴリID", example="a0fbbf74-1816-406e-99b7-7ef1e3e365f4"),
 *             @OA\Property(property="recipeIds", type="array", description="料理IDの配列（1件以上必須）",
 *                 @OA\Items(type="string", format="uuid", description="料理ID", example="a0fbbf74-1816-406e-99b7-7ef1e3e365f4")
 *             )
 *         )
 *     )
 * )
 *
 * 献立更新リクエスト（PUT /meal-plans/{id}）
 *
 * @OA\Schema(
 *     schema="MealPlanUpdateRequest",
 *     required={"data"},
 *     @OA\Property(property="data", type="array", description="献立メニュー",
 *         @OA\Items(
 *             type="object",
 *             required={"categoryId", "recipeIds"},
 *             @OA\Property(property="id", type="string", format="uuid", description="メニューID（新規の場合は不要）", example="a0fbbf74-1816-406e-99b7-7ef1e3e365f4", nullable=true),
 *             @OA\Property(property="categoryId", type="string", format="uuid", description="献立カテゴリID", example="a0fbbf74-1816-406e-99b7-7ef1e3e365f4"),
 *             @OA\Property(property="recipeIds", type="array", description="料理IDの配列（1件以上必須）",
 *                 @OA\Items(type="string", format="uuid", description="料理ID", example="a0fbbf74-1816-406e-99b7-7ef1e3e365f4")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\RequestBody(
 *     request="MealPlanStoreRequest",
 *     description="献立作成。date は必須。data は1件以上必須。",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/MealPlanStoreRequest")
 * )
 * @OA\RequestBody(
 *     request="MealPlanUpdateRequest",
 *     description="献立更新。data は1件以上必須。",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/MealPlanUpdateRequest")
 * )
 *
 * 献立カテゴリ作成/更新リクエスト
 *
 * @OA\Schema(
 *     schema="MealCategoryRequest",
 *     required={"name", "colorId"},
 *     @OA\Property(property="name", type="string", description="カテゴリ名", example="朝食"),
 *     @OA\Property(property="colorId", type="string", description="色ID", example="1"),
 *     @OA\Property(property="order", type="integer", description="ソート順", example=1)
 * )
 *
 * @OA\RequestBody(
 *     request="MealCategoryRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/MealCategoryRequest")
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
