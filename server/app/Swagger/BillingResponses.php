<?php

namespace App\Swagger;

/**
 * 課金関連レスポンス
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
 *     @OA\Property(property="subscriptionStatus", type="string", nullable=true, description="PAY.JP サブスクリプション状態", example="active"),
 *     @OA\Property(property="subscriptionEndsAt", type="string", format="date-time", nullable=true, description="サブスクリプション終了日時"),
 *     @OA\Property(property="pendingPlanChange", ref="#/components/schemas/PendingPlanChange", nullable=true, description="予定されているプラン変更（解約予定など）"),
 *     @OA\Property(property="pmType", type="string", nullable=true, description="登録済み支払い方法の種類", example="card"),
 *     @OA\Property(property="pmLastFour", type="string", nullable=true, description="登録済み支払い方法の下4桁", example="4242"),
 *     @OA\Property(property="pmExpMonth", type="integer", nullable=true, description="登録済み支払い方法の有効期限（月）", example=12),
 *     @OA\Property(property="pmExpYear", type="integer", nullable=true, description="登録済み支払い方法の有効期限（年）", example=2028)
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
 *     required={"date", "lines", "subtotal", "subtotalExcludingTax", "tax", "total", "amountDue"},
 *     @OA\Property(property="date", type="string", format="date-time", description="請求予定日"),
 *     @OA\Property(property="lines", type="array", @OA\Items(ref="#/components/schemas/BillingInvoiceLine")),
 *     @OA\Property(property="subtotal", type="integer", description="小計（最小通貨単位）", example=580),
 *     @OA\Property(property="subtotalExcludingTax", type="integer", description="合計（税抜き）（最小通貨単位）", example=527),
 *     @OA\Property(property="tax", type="integer", description="税額（最小通貨単位）", example=53),
 *     @OA\Property(property="total", type="integer", description="合計（最小通貨単位）", example=580),
 *     @OA\Property(property="amountDue", type="integer", description="支払予定額（最小通貨単位）", example=580)
 * )
 *
 * @OA\Schema(
 *     schema="BillingPastInvoice",
 *     required={"id", "date", "description", "total"},
 *     @OA\Property(property="id", type="string", description="PAY.JP Charge ID", example="ch_abc123"),
 *     @OA\Property(property="date", type="string", format="date-time", description="請求日"),
 *     @OA\Property(property="description", type="string", description="請求内容", example="スタンダードプラン"),
 *     @OA\Property(property="total", type="integer", description="合計（最小通貨単位）", example=580)
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
 *     schema="BillingStatusResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="課金・サブスクリプション状態を取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingStatus")
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
 *     schema="BillingSubscribeResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="サブスクリプションを開始しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingStatus")
 * )
 *
 * @OA\Schema(
 *     schema="BillingCancelResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="サブスクリプションの解約を受け付けました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingStatus")
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
 * @OA\Schema(
 *     schema="BillingPackPurchaseResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="買い切りパックを購入しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingStatus")
 * )
 *
 * @OA\Schema(
 *     schema="BillingCardUpdateResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="カード情報を更新しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/BillingStatus")
 * )
 *
 * @OA\Response(
 *     response="BillingStatusSuccess",
 *     description="課金・サブスクリプション状態を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingStatusResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingInvoicesSuccess",
 *     description="請求履歴を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingInvoicesResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingSubscribeSuccess",
 *     description="サブスクリプションを開始しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingSubscribeResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingCancelSuccess",
 *     description="サブスクリプションの解約を受け付けました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingCancelResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingResumeSuccess",
 *     description="プラン変更予定を取り消しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingResumeResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingPackPurchaseSuccess",
 *     description="買い切りパックを購入しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingPackPurchaseResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingCardUpdateSuccess",
 *     description="カード情報を更新しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BillingCardUpdateResponse")
 * )
 *
 * @OA\Response(
 *     response="BillingCardDeleteSuccess",
 *     description="カード情報を削除しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 */
class BillingResponses {}
