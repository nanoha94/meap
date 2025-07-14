<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="InvitationStoreSuccess",
 *     description="成功",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="token",
 *             type="string",
 *             example="1234567890"
 *         ),
 *         @OA\Property(
 *             property="expires_at",
 *             type="string",
 *             format="date-time",
 *             example="2025-01-01 00:00:00"
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="InvitationShowSuccess",
 *     description="成功",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="token", type="string", example="1234567890"),
 *         @OA\Property(property="expires_at", type="string", format="date-time", example="2025-01-01 00:00:00"),
 *         @OA\Property(property="inviter", type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="山田太郎"),
 *             @OA\Property(property="avatar_seed", type="string", example="1234567890")
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="InvitationJoinSuccess",
 *     description="成功",
 *     @OA\JsonContent(
 *        type="object",
 *        @OA\Property(property="message", type="string", example="グループに参加しました。")
 *     )
 * )
 */
class InvitationResponses {}
