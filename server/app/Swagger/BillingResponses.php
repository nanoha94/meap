<?php

namespace App\Swagger;

/**
 * 課金関連レスポンス
 *
 * @OA\Schema(
 *     schema="BillingCheckoutData",
 *     required={"checkoutUrl"},
 *     @OA\Property(property="checkoutUrl", type="string", description="Stripe Checkout URL", example="https://checkout.stripe.com/c/pay/cs_test_xxx")
 * )
 *
 * @OA\Schema(
 *     schema="BillingPortalData",
 *     required={"portalUrl"},
 *     @OA\Property(property="portalUrl", type="string", description="Stripe Customer Portal URL", example="https://billing.stripe.com/p/session/test_xxx")
 * )
 *
 * @OA\Schema(
 *     schema="BillingInvoiceLine",
 *     required={"description", "quantity", "amount"},
 *     @OA\Property(property="description", type="string", nullable=true, description="明細の説明", example="Standard Plan"),
 *     @OA\Property(property="quantity", type="integer", nullable=true, description="数量", example=1),
 *     @OA\Property(property="amount", type="integer", description="金額（最小通貨単位）", example=580)
 * )
 *
 * @OA\Schema(
 *     schema="BillingUpcomingInvoice",
 *     required={"date", "lines", "subtotal", "tax", "total", "amountDue"},
 *     @OA\Property(property="date", type="string", format="date-time", description="請求予定日"),
 *     @OA\Property(property="lines", type="array", @OA\Items(ref="#/components/schemas/BillingInvoiceLine")),
 *     @OA\Property(property="subtotal", type="integer", description="小計（最小通貨単位）", example=580),
 *     @OA\Property(property="tax", type="integer", description="税額（最小通貨単位）", example=0),
 *     @OA\Property(property="total", type="integer", description="合計（最小通貨単位）", example=580),
 *     @OA\Property(property="amountDue", type="integer", description="支払予定額（最小通貨単位）", example=580)
 * )
 *
 * @OA\Schema(
 *     schema="BillingPastInvoice",
 *     required={"id", "date", "total", "invoiceUrl"},
 *     @OA\Property(property="id", type="string", description="Stripe Invoice ID", example="in_1ABC123"),
 *     @OA\Property(property="date", type="string", format="date-time", description="請求日"),
 *     @OA\Property(property="total", type="integer", description="合計（最小通貨単位）", example=580),
 *     @OA\Property(property="invoiceUrl", type="string", nullable=true, description="請求書URL", example="https://invoice.stripe.com/i/acct_xxx/test_xxx")
 * )
 *
 * @OA\Schema(
 *     schema="BillingInvoices",
 *     required={"upcomingInvoice", "pastInvoices"},
 *     @OA\Property(property="upcomingInvoice", ref="#/components/schemas/BillingUpcomingInvoice", nullable=true),
 *     @OA\Property(property="pastInvoices", type="array", @OA\Items(ref="#/components/schemas/BillingPastInvoice"))
 * )
 *
 * @OA\Schema(
 *     schema="PendingPlanChange",
 *     required={"nextPlan", "changesAt"},
 *     @OA\Property(property="nextPlan", type="string", description="変更先プラン", example="free"),
 *     @OA\Property(property="changesAt", type="string", format="date-time", description="プラン変更予定日時")
 * )
 *
 * @OA\Schema(
 *     schema="BillingStatus",
 *     required={"plan", "isSubscribed", "subscriptionStatus", "subscriptionEndsAt", "pendingPlanChange", "pmType", "pmLastFour", "pmExpMonth", "pmExpYear"},
 *     @OA\Property(property="plan", type="string", description="現在の料金プラン", example="free"),
 *     @OA\Property(property="isSubscribed", type="boolean", description="有効なサブスクリプションがあるか", example=false),
 *     @OA\Property(property="subscriptionStatus", type="string", nullable=true, description="Stripe サブスクリプション状態", example="active"),
 *     @OA\Property(property="subscriptionEndsAt", type="string", format="date-time", nullable=true, description="サブスクリプション終了日時"),
 *     @OA\Property(property="pendingPlanChange", ref="#/components/schemas/PendingPlanChange", nullable=true, description="予定されているプラン変更（解約予定など）"),
 *     @OA\Property(property="pmType", type="string", nullable=true, description="登録済み支払い方法の種類", example="card"),
 *     @OA\Property(property="pmLastFour", type="string", nullable=true, description="登録済み支払い方法の下4桁", example="4242"),
 *     @OA\Property(property="pmExpMonth", type="integer", nullable=true, description="登録済み支払い方法の有効期限（月）", example=12),
 *     @OA\Property(property="pmExpYear", type="integer", nullable=true, description="登録済み支払い方法の有効期限（年）", example=2028)
 * )
 *
 * @OA\Schema(
 *     schema="BillingCheckoutResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Stripe Checkout セッションを作成しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingCheckoutData")
 * )
 *
 * @OA\Schema(
 *     schema="BillingPortalResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Stripe Customer Portal セッションを作成しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingPortalData")
 * )
 *
 * @OA\Schema(
 *     schema="BillingInvoicesResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="請求履歴を取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingInvoices")
 * )
 *
 * @OA\Schema(
 *     schema="BillingStatusResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="課金・サブスクリプション状態を取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingStatus")
 * )
 *
 * @OA\Response(
 *     response="BillingCheckoutSuccess",
 *     description="Stripe Checkout セッションを作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingCheckoutResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingPortalSuccess",
 *     description="Stripe Customer Portal セッションを作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingPortalResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingInvoicesSuccess",
 *     description="請求履歴を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingInvoicesResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingStatusSuccess",
 *     description="課金・サブスクリプション状態を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingStatusResponse")
 * )
 *
 * @OA\Schema(
 *     schema="BillingResumeResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="プラン変更予定を取り消しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingStatus")
 * )
 *
 * @OA\Response(
 *     response="BillingResumeSuccess",
 *     description="プラン変更予定を取り消しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingResumeResponse")
 * )
 */
class BillingResponses {}
