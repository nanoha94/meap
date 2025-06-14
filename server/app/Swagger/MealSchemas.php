<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="Meal",
 *     required={"date", "category"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="date", type="string", format="date", description="日付", example="2023-10-05"),
 *     @OA\Property(property="category", type="string", description="種別", example="朝食"),
 *     @OA\Property(property="menu", type="array", description="献立メニュー",
 *         @OA\Items(type="object",
 *             @OA\Property(property="role", type="string", description="分類", example="主食"),
 *             @OA\Property(property="dishes", type="array", description="料理",
 *                 @OA\Items(ref="#/components/schemas/Dish")
 *             )
 *         )
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="MealCategory",
 *     required={"id", "name", "color"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="カテゴリ名", example="朝食"),
 *     @OA\Property(property="colorId", type="string", description="色ID", example="1"),
 *     @OA\Property(property="order", type="integer", description="ソート順", example="1"),
 * )

 */

class MealSchemas {}
