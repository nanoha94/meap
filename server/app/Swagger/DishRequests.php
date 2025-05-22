<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="DishRequest",
 *     description="登録する料理データ",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/Dish")
 * )
 * @OA\RequestBody(
 *     request="DishCategoryRequest",
 *     description="登録する料理カテゴリデータ",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/DishCategory")
 * )
 */

class DishRequests {}
