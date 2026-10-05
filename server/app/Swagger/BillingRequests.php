<?php

namespace App\Swagger;

/**
 * サブスクリプション開始リクエスト
 *
 * @OA\Schema(
 *     schema="BillingSubscribeRequest",
 *     @OA\Property(property="cardToken", type="string", nullable=true, description="Square カードトークン（未登録時は必須。登録済みカードで開始する場合は省略可）", example="cnon:card-nonce-ok"),
 *     @OA\Property(property="verificationToken", type="string", nullable=true, description="CreateCard の optional verification_token。通常は tokenize(verificationDetails) 利用時は省略", example="verf:verification-token")
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
 *     @OA\Property(property="cardToken", type="string", nullable=true, description="Square カードトークン（未登録時は必須。登録済みカードで購入する場合は省略可）", example="cnon:card-nonce-ok"),
 *     @OA\Property(property="verificationToken", type="string", nullable=true, description="CreateCard の optional verification_token。通常は tokenize(verificationDetails) 利用時は省略", example="verf:verification-token")
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
 *     @OA\Property(property="cardToken", type="string", description="Square カードトークン", example="cnon:card-nonce-ok"),
 *     @OA\Property(property="verificationToken", type="string", nullable=true, description="CreateCard の optional verification_token。通常は tokenize(verificationDetails) 利用時は省略", example="verf:verification-token")
 * )
 *
 * @OA\RequestBody(
 *     request="BillingCardUpdateRequest",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/BillingCardUpdateRequest")
 * )
 */
class BillingRequests {}
