<?php

namespace App\Swagger;

/**
 * AI利用状況レスポンス
 *
 * @OA\Schema(
 *     schema="AiUsageStatus",
 *     required={"plan", "monthlyRemaining", "monthlyLimit", "packRemaining", "resetsAt"},
 *     @OA\Property(property="plan", type="string", description="現在の料金プラン", example="free"),
 *     @OA\Property(property="monthlyRemaining", type="integer", description="月間枠の残り回数", example=2),
 *     @OA\Property(property="monthlyLimit", type="integer", description="月間枠の上限回数", example=3),
 *     @OA\Property(property="packRemaining", type="integer", description="買い切りパックの残り回数", example=0),
 *     @OA\Property(property="resetsAt", type="string", format="date-time", nullable=true, description="次回リセット日時（フリー: 月次、有料: Stripe 課金周期）", example="2025-05-15T00:00:00+09:00")
 * )
 *
 * @OA\Schema(
 *     schema="AiUsageStatusResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="AI利用状況を取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/AiUsageStatus")
 * )
 *
 * @OA\Response(
 *     response="AiUsageShowSuccess",
 *     description="AI利用状況を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/AiUsageStatusResponse")
 * )
 */
class AiUsageResponses {}
