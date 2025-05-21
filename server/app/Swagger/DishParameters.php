<?php

namespace App\Swagger;

/**
 * @OA\Parameter(
 *     parameter="DishPageParam",
 *     name="page",
 *     in="query",
 *     description="ページ番号",
 *     required=false,
 *     @OA\Schema(
 *         type="integer",
 *         default=1,
 *         minimum=1
 *     )
 * )
 * @OA\Parameter(
 *     parameter="DishPerPageParam",
 *     name="per_page",
 *     in="query",
 *     description="1ページあたりの表示件数",
 *     required=false,
 *     @OA\Schema(
 *         type="integer",
 *         default=15,
 *         minimum=1,
 *         maximum=100
 *     )
 * )
 * @OA\Parameter(
 *     parameter="DishIdParam",
 *     name="id",
 *     in="path",
 *     description="料理ID",
 *     required=true,
 *     @OA\Schema(
 *         type="string",
 *         example="1"
 *     )
 * )
 */

class DishParameters {}
