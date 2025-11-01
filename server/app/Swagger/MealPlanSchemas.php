<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="MealPlan",
 *     required={"date", "categoryId"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="date", type="string", format="date", description="日付", example="2023-10-05"),
 *     @OA\Property(property="category", type="object",
 *         @OA\Property(property="id", type="string", description="ID", example="1"),
 *         @OA\Property(property="name", type="string", description="カテゴリ名", example="朝食"),
 *         @OA\Property(property="colorId", type="string", description="色ID", example="1")
 *     ),
 *     @OA\Property(property="menu", type="array", description="献立",
 *         @OA\Items(type="object",
 *             @OA\Property(property="courseType", type="object",
 *                 @OA\Property(property="id", type="string", description="ID", example="1"),
 *                 @OA\Property(property="name", type="string", description="分類名", example="主食")
 *             ),
 *             @OA\Property(property="recipes", type="array", description="料理",
 *                 @OA\Items(ref="#/components/schemas/Recipe")
 *             )
 *         )
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="MealType",
 *     required={"id", "name", "colorId"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="カテゴリ名", example="朝食"),
 *     @OA\Property(property="colorId", type="string", description="色ID", example="1"),
 *     @OA\Property(property="order", type="integer", description="ソート順", example=1)
 * )
 * 
 * @OA\Schema(
 *     schema="CourseType",
 *     required={"id", "name", "order"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="コース種別名", example="朝食"),
 *     @OA\Property(property="order", type="integer", description="ソート順", example=1)
 * )
 */

class MealPlanSchemas {}
