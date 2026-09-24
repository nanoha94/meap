<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plan IDs (PAY.JP)
    |--------------------------------------------------------------------------
    |
    | サブスクリプション用 Plan ID（`pln_...`）。キーは BillingSubscriptionType の value と一致。
    |
    */
    'subscription_plan_ids' => [
        'standard' => env('PAYJP_PLAN_SUBSCRIPTION_STANDARD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Amounts
    |--------------------------------------------------------------------------
    |
    | サブスクリプション月額（税込・円）。次回請求予定の表示に使う。
    | キーは BillingSubscriptionType の value と一致。
    |
    */
    'subscription_amounts' => [
        'standard' => (int) env('PAYJP_AMOUNT_SUBSCRIPTION_STANDARD', 580),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pack Prices
    |--------------------------------------------------------------------------
    |
    | 買い切りパックの都度課金金額（円）。PAY.JP Charge API に渡す。
    |
    */
    'pack_prices' => [
        'light' => (int) env('PAYJP_PRICE_PACK_LIGHT', 400),
        'value' => (int) env('PAYJP_PRICE_PACK_VALUE', 800),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pack Credits
    |--------------------------------------------------------------------------
    |
    | 買い切りパック購入時に付与する AI 利用回数（失効なし）。
    |
    */
    'pack_credits' => [
        'light' => 10,
        'value' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Rate Limiting
    |--------------------------------------------------------------------------
    |
    | 1分あたりの課金・カード操作 API 呼び出し上限（ユーザー単位）。
    | throttle:billing が適用された POST ルートで有効。
    |
    */
    'rate_limit_per_minute' => (int) env('BILLING_RATE_LIMIT_PER_MINUTE', 10),

    /*
    |--------------------------------------------------------------------------
    | Charge History
    |--------------------------------------------------------------------------
    |
    | GET /billing/invoices の pastInvoices。サブスク更新・買い切りパックを合算。
    | charge_history_since_years: listCharges の since（この年数より前は取得しない）。
    | charge_history_limit: 1 リクエストあたりの最大件数（PAY.JP API は 1〜100）。
    |
    */
    'charge_history_since_years' => 2,
    'charge_history_limit' => 100,

];
