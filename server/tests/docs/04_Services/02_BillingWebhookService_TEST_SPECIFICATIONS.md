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
- サブスクリプションのデフォルト形は `makeSquareSubscriptionObject`（Webhook 用）および `makeSquareSubscriptionSdk`（Retrieve API 応答用）
- `oncePerEvent` の検証では `beforeEach` で `Cache::flush()` し、同一 event ID で 2 回呼び出す
- private メソッド（`subscriptionFromPayload` / `syncPlanFromSquareSubscription` 等）は public メソッド経由で間接的に検証する

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 4-2-1 | 【定期更新】 invoice.payment_made でプラン更新と利用回数リセットを行う | 正常系 | FREE の Group、`invoice.payment_made`（`subscription_id` 付き invoice）、retrieveSubscription が standard plan / `chargedThroughDate` を返す | plan=STANDARD、ai_monthly_remaining=STANDARD の月間上限、ai_usage_reset_at=charged_through_date | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-2 | 【定期更新】 顧客不在はスキップする | 異常系 | Group の `square_customer_id` とペイロードの `customer_id` が不一致 | plan / ai_monthly_remaining が変更されない | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-3 | 【定期更新】 同一 event ID の再送は二重処理しない | 異常系 | 同一 event ID で `handleInvoicePaymentMade` を 2 回呼び出し（2 回目の前に ai_monthly_remaining を 0 に変更） | 2 回目以降は renew 処理が走らず ai_monthly_remaining=0 のまま | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-10 | 【定期更新】 invoice.payment_made で billing_histories にサブスク description を保存する | 正常系 | FREE の Group、`payment_ids` 付き invoice、retrieveSubscription が standard plan を返す | `billing_histories` に type=subscription、description=スタンダードプラン、square_payment_id=payment_ids[0] | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-11 | 【定期更新】 同一 Payment ID の再処理で billing_histories が重複しない | 正常系 | 異なる event ID で同一 `payment_ids` の invoice を 2 回処理 | `billing_histories` が 1 件のみ | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-12 | 【定期更新】 不明 plan variation では billing_histories を書かない | 異常系 | retrieveSubscription が未登録 plan variation ID を返す、`payment_ids` 付き invoice | `billing_histories` にレコードなし | `BillingWebhookService::handleInvoicePaymentMade()` |
| 4-2-4 | 【サブスク同期】 ACTIVE で STANDARD プランを付与する | 正常系 | FREE の Group、`status=ACTIVE`、standard plan variation ID | plan=STANDARD | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-5 | 【サブスク同期】 CANCELED（期間終了後）で FREE に戻す | 正常系 | STANDARD の Group、`subscription.updated` で `status=CANCELED`、`charged_through_date` が過去 | plan=FREE | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-6 | 【サブスク同期】 PAUSED で FREE に戻す | 正常系 | STANDARD の Group、`status=PAUSED` | plan=FREE | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-7 | 【サブスク同期】 不明な plan variation ID ではプラン更新しない | 異常系 | FREE の Group、`status=ACTIVE`、`plan_variation_id` が config 未登録 | plan=FREE のまま | `BillingWebhookService::handleSubscriptionUpdated()` |
| 4-2-8 | 【支払い失敗】 payment.updated（FAILED）は例外なく処理できる | 正常系 | 任意の Group、`payment.updated` 相当ペイロード（`status=FAILED`） | 例外なし、Group の plan は変更されない | `BillingWebhookService::handlePaymentUpdated()` |
| 4-2-9 | 【サブスク同期】 PENDING（未来開始・未課金）ではプラン付与しない | 正常系 | FREE の Group、`status=PENDING` | plan=FREE のまま | `BillingWebhookService::handleSubscriptionUpdated()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/BillingWebhookServiceTest.php --stop-on-failure
```
