<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="RecipeRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/Recipe")
 * )
 * @OA\RequestBody(
 *     request="RecipeCategoryRequest",
 *     description="※新規作成時はid不要",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/RecipeCategory")
 * )
 */

class RecipeRequests {}
