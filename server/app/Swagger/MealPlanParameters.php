<?php

namespace App\Swagger;

/**
 * @OA\Parameter(
 *     parameter="MealPlanIdParam",
 *     name="id",
 *     in="path",
 *     description="献立ID",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         example="1"
 *     )
 * )
 * @OA\Parameter(
 *     parameter="MealPlanIdPathParam",
 *     name="mealPlanId",
 *     in="path",
 *     description="献立ID",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         format="uuid"
 *     )
 * )
 * @OA\Parameter(
 *     parameter="MealIdParam",
 *     name="mealId",
 *     in="path",
 *     description="献立の1食ID",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         format="uuid"
 *     )
 * )
 * @OA\Parameter(
 *     parameter="MealCategoryIdParam",
 *     name="id",
 *     in="path",
 *     description="献立カテゴリID",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         example="1"
 *     )
 * )
 */

class MealPlanParameters {}
