<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="DishRequest",
 *     description="登録する料理データ",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/Dish")
 * )
 */

class DishRequests {}
