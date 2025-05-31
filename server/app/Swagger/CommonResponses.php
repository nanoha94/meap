<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="Unauthorized",
 *     description="認証エラー",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             example="Unauthenticated."
 *         )
 *     )
 * )
 * 
 * @OA\Response(
 *     response="NotFound",
 *     description="リソースが見つかりませんでした",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             example="Resource not found."
 *         )
 *     )
 * )
 * 
 * @OA\Response(
 *     response="ValidationErrors",
 *     description="バリデーションエラー",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             example="The given data was invalid."
 *         ),
 *         @OA\Property(
 *             property="errors",
 *             type="object",
 *             @OA\Property(
 *                 property="XXXXXX",
 *                 type="array",
 *                 @OA\Items(type="string", example="The XXXXXX field is required.")
 *             )
 *         )
 *     )
 * )
 */



class CommonResponses {}
