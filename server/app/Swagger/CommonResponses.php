<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="Unauthorized",
 *     description="認証エラー",
 *      @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 * )
 * @OA\Response(
 *     response="NotFound",
 *     description="リソースが見つかりませんでした",
 *      @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string")
 *         )
 * )
 */



class CommonResponses {}
