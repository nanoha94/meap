<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="UserIndexSuccess",
 *     description="同じグループのユーザーを5件取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="同じグループのユーザーを5件取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="ユーザ一覧",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="name", type="string", example="山田太郎"),
 *                 @OA\Property(property="language", type="string", example="ja", description="ユーザーの言語設定"),
 *                 @OA\Property(property="avatar", type="object",
 *                     @OA\Property(property="seed", type="string", example="1234567890"),
 *                     @OA\Property(property="url", type="string", example="https://example.com/avatar.jpg"),
 *                     @OA\Property(property="width", type="integer", example=300),
 *                     @OA\Property(property="height", type="integer", example=300)
 *                 )
 *             )
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="ユーザ総数",
 *             example=5
 *         )
 *     )
 * )
 * 
 * @OA\Response(
 *     response="UserShowSuccess",
 *     description="認証ユーザー情報を取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="ユーザーを取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="object",
 *             description="認証ユーザー情報",
 *             @OA\Property(property="id", type="string", example="1234567890"),
 *             @OA\Property(property="name", type="string", example="山田太郎"),
 *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
 *             @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2024-01-01T00:00:00.000000Z"),
 *             @OA\Property(property="avatar_seed", type="string", example="1234567890")
 *         )
 *     )
 * )
 */
class UserResponses {}
