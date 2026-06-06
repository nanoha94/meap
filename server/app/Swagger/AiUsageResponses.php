<?php

namespace App\Swagger;

/**
 * AI利用状況レスポンス
 *
 * @OA\Schema(
 *     schema="AiUsageStatus",
 *     required={"plan", "usageCount", "usageLimit", "resetsAt"},
 *     @OA\Property(property="plan", type="string", description="現在の料金プラン", example="free"),
 *     @OA\Property(property="usageCount", type="integer", description="現在のAI利用回数", example=1),
 *     @OA\Property(property="usageLimit", type="integer", description="現在の料金プランのAI利用上限", example=3),
 *     @OA\Property(property="resetsAt", type="string", format="date-time", description="次回リセット日時", example="2025-05-15T00:00:00+09:00")
 * )
 *
 * @OA\Response(
 *     response="AiUsageShowSuccess",
 *     description="AI利用状況を取得しました。",
 *     @OA\JsonContent(
 *         required={"success", "message", "data"},
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="AI利用状況を取得しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/AiUsageStatus")
 *     )
 * )
 */
class AiUsageResponses {}
