<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="DishRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/Dish")
 * )
 * @OA\RequestBody(
 *     request="DishCategoryRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/DishCategory")
 * )
 */

class DishRequests {}
