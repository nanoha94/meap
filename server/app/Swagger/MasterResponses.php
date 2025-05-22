<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="MasterSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="dishCategories",
 *             type="array",
 *             description="料理カテゴリ一覧",
 *             @OA\Items(ref="#/components/schemas/DishCategory")
 *         ),
 *     )
 * )
 */
class MasterResponses {}
