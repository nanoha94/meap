<?php

namespace App\Swagger;

/**
 * @OA\Parameter(
 *     parameter="ShoppingIdParam",
 *     name="id",
 *     in="path",
 *     description="買い物アイテムID",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         example="1"
 *     )
 * )
 * @OA\Parameter(
 *     parameter="ShoppingCategoryIdParam",
 *     name="id",
 *     in="path",
 *     description="買い物カテゴリID",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         example="1"
 *     )
 * )
 */

class ShoppingParameters {}
