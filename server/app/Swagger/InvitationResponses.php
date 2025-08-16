<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="InvitationStoreSuccess",
 *     description="招待トークンを作成しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="招待トークンを作成しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="object",
 *             @OA\Property(
 *                 property="token",
 *                 type="string",
 *                 example="1234567890"
 *             ),
 *             @OA\Property(
 *                 property="expires_at",
 *                 type="string",
 *                 format="date-time",
 *                 example="2025-01-01 00:00:00"
 *             )
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="InvitationShowSuccess",
 *     description="招待情報を取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="招待情報を取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="object",
 *             @OA\Property(property="token", type="string", example="1234567890"),
 *             @OA\Property(property="expires_at", type="string", format="date-time", example="2025-01-01 00:00:00"),
 *             @OA\Property(property="inviter", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="山田太郎"),
 *                 @OA\Property(property="avatar", type="object",
 *                     @OA\Property(property="seed", type="string", example="1234567890"),
 *                     @OA\Property(property="url", type="string", example="https://example.com/image.jpg"),
 *                     @OA\Property(property="width", type="integer", example=300),
 *                     @OA\Property(property="height", type="integer", example=200),
 *                 )
 *             )
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="InvitationJoinSuccess",
 *     description="グループに参加しました。",
 *     @OA\JsonContent(
 *        type="object",
 *        @OA\Property(property="success", type="boolean", example=true),
 *        @OA\Property(property="message", type="string", example="グループに参加しました。"),
 *        @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 */
class InvitationResponses {}
