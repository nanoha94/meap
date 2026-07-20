<?php

namespace App\Swagger;

/**
 * AIレシピ解析レスポンス（画像・URL共通: success, message, data: ParsedRecipe）
 *
 * @OA\Schema(
 *     schema="AiRecipeParseResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="画像からレシピ情報を読み取りました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/ParsedRecipe")
 * )
 *
 * @OA\Response(
 *     response="AiRecipeParseSuccess",
 *     description="レシピ情報をAI解析しました。",
 *     @OA\JsonContent(ref="#/components/schemas/AiRecipeParseResponse")
 * )
 *
 * @OA\Response(
 *     response="AiUsageLimitExceeded",
 *     description="AI利用回数の上限超過（月次）または短時間の連続リクエスト",
 *     @OA\JsonContent(
 *         oneOf={
 *             @OA\Schema(
 *                 required={"success", "message", "error_code"},
 *                 @OA\Property(property="success", type="boolean", example=false),
 *                 @OA\Property(property="message", type="string", example="今月のAI利用回数の上限に達しました。"),
 *                 @OA\Property(property="error_type", type="string", example="ai_monthly_limit_exceeded"),
 *                 @OA\Property(property="error_code", type="integer", example=429)
 *             ),
 *             @OA\Schema(
 *                 required={"success", "message", "error_code"},
 *                 @OA\Property(property="success", type="boolean", example=false),
 *                 @OA\Property(property="message", type="string", example="短時間で複数回リクエストしているので機能を一時停止しています。時間をおいて試してください。"),
 *                 @OA\Property(property="error_type", type="string", example="ai_rate_limit_exceeded"),
 *                 @OA\Property(property="error_code", type="integer", example=429)
 *             )
 *         }
 *     )
 * )
 */
class AiRecipeResponses {}
