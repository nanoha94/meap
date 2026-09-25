<?php

namespace App\Swagger;

/**
 * サブスクリプション開始リクエスト
 *
 * @OA\Schema(
 *     schema="BillingSubscribeRequest",
 *     @OA\Property(property="cardToken", type="string", nullable=true, description="PAY.JP カードトークン（未登録時は必須。登録済みカードで開始する場合は省略可）", example="tok_xxxxx")
 * )
 * 
 * @OA\RequestBody(
 *     request="BillingSubscribeRequest",
 *     required=false,
 *     @OA\JsonContent(ref="#/components/schemas/BillingSubscribeRequest")
 * )
 * 
 * 買い切りパック購入リクエスト
 *
 * @OA\Schema(
 *     schema="BillingPackPurchaseRequest",
 *     @OA\Property(property="cardToken", type="string", nullable=true, description="PAY.JP カードトークン（未登録時は必須。登録済みカードで購入する場合は省略可）", example="tok_xxxxx")
 * )
 * 
 * @OA\RequestBody(
 *     request="BillingPackPurchaseRequest",
 *     required=false,
 *     @OA\JsonContent(ref="#/components/schemas/BillingPackPurchaseRequest")
 * )
 *
 * カード登録・更新リクエスト
 *
 * @OA\Schema(
 *     schema="BillingCardUpdateRequest",
 *     required={"cardToken"},
 *     @OA\Property(property="cardToken", type="string", description="PAY.JP カードトークン", example="tok_xxxxx")
 * )
 *
 * @OA\RequestBody(
 *     request="BillingCardUpdateRequest",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/BillingCardUpdateRequest")
 * )
 */
class BillingRequests {}
