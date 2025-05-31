<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="MealRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/Meal")
 * )
 */

class MealRequests {}
