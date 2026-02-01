<?php

namespace App\Swagger;

/**
 * 招待情報（詳細取得時の data）
 *
 * @OA\Schema(
 *     schema="Invitation",
 *     required={"token", "expires_at", "inviter"},
 *     @OA\Property(property="token", type="string", description="招待トークン", example="1234567890"),
 *     @OA\Property(property="expires_at", type="string", format="date-time", description="有効期限", example="2025-01-01 00:00:00"),
 *     @OA\Property(property="inviter", ref="#/components/schemas/User", description="招待者情報")
 * )
 *
 * 招待詳細取得レスポンス（success, message, data: Invitation）
 *
 * @OA\Schema(
 *     schema="InvitationDetailResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="招待情報を取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/Invitation", description="招待情報")
 * )
 *
 * 招待作成レスポンス（success, message, data: token, expires_at）
 *
 * @OA\Schema(
 *     schema="InvitationStoreResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="招待トークンを作成しました。"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         description="作成された招待トークン情報",
 *         @OA\Property(property="token", type="string", description="招待トークン", example="1234567890"),
 *         @OA\Property(property="expires_at", type="string", format="date-time", description="有効期限", example="2025-01-01 00:00:00")
 *     )
 * )
 *
 * @OA\Response(
 *     response="InvitationStoreSuccess",
 *     description="招待トークンを作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/InvitationStoreResponse")
 * )
 * @OA\Response(
 *     response="InvitationShowSuccess",
 *     description="招待情報を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/InvitationDetailResponse")
 * )
 * @OA\Response(
 *     response="InvitationJoinSuccess",
 *     description="グループに参加しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 */
class InvitationResponses {}
