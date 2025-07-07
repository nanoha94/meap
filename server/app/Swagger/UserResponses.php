<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="UserIndexSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="ユーザ一覧",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="山田太郎")
 *             )
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="ユーザ総数",
 *             example=100
 *         )
 *     )
 * )
 */
class UserResponses {}
