# BillingWebhookService テストケース詳細仕様

## 概要

Square Webhook イベントに応じた課金状態同期を担う `BillingWebhookService` の Feature テスト。Webhook ペイロードを配列で構築し、各 public メソッドを直接呼び出して DB 状態を検証する。`AiUsageService` は実インスタンスを使用し、`renewBillingPeriod()` および `GroupPlanService::update()` との連携を確認する。

## テスト方針

- Square API への実通信は行わない
- `config('billing.subscription_plan_ids.standard')` にはテスト用の固定 Plan Variation ID（`pln_standard_test`）を `beforeEach` で設定する
- Group は `square_customer_id` を持つ Factory レコードとして作成する（`makeSquareBillableGroup`）
- 顧客検索は `Group::findBySquareCustomerId()` 経由で行われる
- `subscription.updated` は `data.object.subscription`（`makeSquareSubscriptionWebhookEnvelope`）
- `invoice.payment_made` は `data.object.invoice`（`subscription_id` / `primary_recipient.customer_id` / 任意で `payment_ids` または `order_id`）。プラン・周期は `SquareBillingClient::retrieveSubscription` をモックして返す。`order_id` 経由の Payment ID 取得は `retrieveOrder` をモックする
- 請求履歴の `amount` / `paid_at` は `retrievePayment` の合計金額と作成日時を優先する。失敗時は invoice の `payment_requests`（`total_completed_amount_money` → `computed_amount_money`）と `updated_at`、最後に `next_payment_amount_money` を使う。どちらからも金額が取れないときは既存の `amount` / `paid_at` を消さない
- サブスクリプションのデフォルト形は `makeSquareSubscriptionObject`（Webhook 用）および `makeSquareSubscriptionSdk`（Retrieve API 応答用）
- `oncePerEvent` の検証では `beforeEach` で `Cache::flush()` し、同一 event ID で 2 回呼び出す
- private メソッド（`subscriptionFromPayload` / `upsertSubscriptionRecord` / `syncGroupPlanFromSubscription` 等）は public メソッド経由で間接的に検証する

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 4-2-1 | 【定期更新】 invoice.payment_made でプラン更新と利用回数リセットを行う | 正常系 | FREE の Group、`invoice.payment_made`（`subscription_id` 付き invoice）、retrieveSubscription が standard plan / `chargedThroughDate` を返す | plan=STANDARD、ai_monthly_remaining=STANDARD の月間上限、ai_usage_reset_at=charged_through_date | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-2 | 【定期更新】 顧客不在はスキップする | 異常系 | Group の `square_customer_id` とペイロードの `customer_id` が不一致 | plan / ai_monthly_remaining が変更されない | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-3 | 【定期更新】 同一 event ID の再送は二重処理しない | 異常系 | 同一 event ID で `handleInvoicePaymentMade` を 2 回呼び出し（2 回目の前に ai_monthly_remaining を 0 に変更） | 2 回目以降は renew 処理が走らず ai_monthly_remaining=0 のまま | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-4 | 【定期更新】 invoice.payment_made で billing_histories にサブスク description を保存する | 正常系 | FREE の Group、`payment_ids` 付き invoice、retrieveSubscription が standard plan を返す、retrievePayment が合計 580 と作成日時を返す | `billing_histories` に type=subscription、description=スタンダードプラン、square_payment_id=payment_ids[0]、amount=580、paid_at=Payment の作成日時 | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-5 | 【定期更新】 同一 Payment ID の再処理で billing_histories が重複しない | 正常系 | 異なる event ID で同一 `payment_ids` の invoice を 2 回処理、retrievePayment が合計 580 と作成日時を返す | `billing_histories` が 1 件のみ、amount=580、paid_at=Payment の作成日時 | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-6 | 【定期更新】 Payment の金額と決済日時で既存 billing_histories を上書きする | 正常系 | 同一 square_payment_id の billing_histories（amount=100）が既にある、retrievePayment が合計 580 と作成日時を返す | `billing_histories` は 1 件のまま、description=スタンダードプラン、amount=580、paid_at=Payment の作成日時 | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-7 | 【定期更新】 Payment 取得失敗時は Invoice の金額と更新日時を保存する | 正常系 | retrievePayment が SquareApiException、invoice の total_completed_amount_money=0・computed_amount_money=480・updated_at あり | `billing_histories` に description=スタンダードプラン、amount=480、paid_at=invoice.updated_at | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-8 | 【定期更新】 金額が取れないときは既存の amount と paid_at を残す | 正常系 | amount=580 / paid_at 付きの billing_histories が既にある、retrievePayment が SquareApiException、invoice に金額も updated_at も無い | `billing_histories` は 1 件のまま、description=スタンダードプラン、amount と paid_at は更新前の値 | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-9 | 【定期更新】 不明 plan variation では billing_histories を書かない | 異常系 | retrieveSubscription が未登録 plan variation ID を返す、`payment_ids` 付き invoice | `billing_histories` にレコードなし | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-10 | 【サブスク同期】 ACTIVE で STANDARD プランを付与する | 正常系 | FREE の Group、`status=ACTIVE`、standard plan variation ID | plan=STANDARD | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-11 | 【サブスク同期】 CANCELED（期間終了後）で FREE に戻す | 正常系 | STANDARD の Group、`subscription.updated` で `status=CANCELED`、`charged_through_date` が過去 | plan=FREE | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-12 | 【サブスク同期】 PAUSED で FREE に戻す | 正常系 | STANDARD の Group、`status=PAUSED` | plan=FREE | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-13 | 【サブスク同期】 不明な plan variation ID ではプラン更新しない | 異常系 | FREE の Group、`status=ACTIVE`、`plan_variation_id` が config 未登録 | plan=FREE のまま | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-14 | 【支払い失敗】 payment.updated（FAILED）は例外なく処理できる | 正常系 | 任意の Group、`payment.updated` 相当ペイロード（`status=FAILED`） | 例外なし、Group の plan は変更されない | `BillingWebhookService::handlePaymentUpdated()` |
| 4-2-15 | 【サブスク同期】 PENDING（未来開始・未課金）ではプラン付与しない | 正常系 | FREE の Group、`status=PENDING` | plan=FREE のまま | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-16 | 【サブスク同期】 DEACTIVATED で subscriptions.status を更新する | 正常系 | STANDARD の Group、ローカル Subscription（status=active）、Webhook `status=DEACTIVATED` | `subscriptions.status=canceled` | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-17 | 【サブスク同期】 ACTIVE（charged_through_date 付き）で current_period_end を更新する | 正常系 | Group、ローカル Subscription（current_period_end=null）、Webhook `status=ACTIVE` と charged_through_date | `subscriptions.current_period_end` が charged_through_date と一致 | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-18 | 【サブスク同期】 status 欠落ペイロードでは subscriptions も plan も更新しない | 正常系 | STANDARD の Group、既存 Subscription（status=active）、Webhook ペイロードの `status` が null | `subscriptions.status` は active のまま、`Group.plan` も STANDARD のまま | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-19 | 【サブスク同期】 charged_through_date 欠落時 canceled_date で current_period_end を補完する | 正常系 | Group、ローカル Subscription（current_period_end=null）、Webhook `status=ACTIVE`、`charged_through_date` 欠落、`canceled_date` のみ | `subscriptions.current_period_end` が canceled_date と一致 | `BillingWebhookService::handleSubscriptionUpdated()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/BillingWebhookServiceTest.php --stop-on-failure
```
