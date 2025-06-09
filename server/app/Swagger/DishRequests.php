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
 *     required=true,
 *      @OA\JsonContent(
 *         type="object",
 *         required={"name"},
 *         @OA\Property(property="name", type="string", example="肉料理"),
 *     )
 * )
 */

class DishRequests {}
