# BillingWebhookService テストケース詳細仕様

## 概要

PAY.JP Webhook イベントに応じた課金状態同期を担う `BillingWebhookService` の Feature テスト。Webhook ペイロードを配列で構築し、各 public メソッドを直接呼び出して DB 状態を検証する。`AiUsageService` は実インスタンスを使用し、`renewBillingPeriod()` および `GroupPlanService::update()` との連携を確認する。

## テスト方針

- PAY.JP API への実通信は行わない
- `config('billing.subscription_plan_ids.standard')` にはテスト用の固定 plan ID（`pln_standard_test`）を `beforeEach` で設定する
- Group は `payjp_customer_id` を持つ Factory レコードとして作成する（`makePayjpBillableGroup`）
- 顧客検索は `Group::findByPayjpCustomerId()` 経由で行われる
- サブスクリプション本体は `['id' => eventId, 'data' => subscription]` 形式のエンベロープ（`makePayjpWebhookEnvelope`）で渡す
- サブスクリプションのデフォルト形は `makePayjpSubscriptionPayload`（`customer` / `status` / `plan.id` / `current_period_end` 等）
- `oncePerEvent` の検証では `beforeEach` で `Cache::flush()` し、同一 event ID で 2 回呼び出す
- private メソッド（`subscriptionFromPayload` / `syncPlanFromPayjpSubscription` 等）は public メソッド経由で間接的に検証する

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 4-2-1 | 【定期更新】 subscription.renewed でプラン更新と利用回数リセットを行う | 正常系 | FREE の Group（`payjp_customer_id` あり）、`subscription.renewed` 相当ペイロード（standard plan ID、`current_period_end` あり） | plan=STANDARD、ai_monthly_remaining=STANDARD の月間上限、ai_usage_reset_at=current_period_end | `BillingWebhookService::handleSubscriptionRenewed()` |
| 4-2-2 | 【定期更新】 顧客不在はスキップする | 異常系 | Group の `payjp_customer_id` とペイロードの `customer` が不一致 | plan / ai_monthly_remaining が変更されない | `BillingWebhookService::handleSubscriptionRenewed()` |
| 4-2-3 | 【定期更新】 同一 event ID の再送は二重処理しない | 異常系 | 同一 event ID で `handleSubscriptionRenewed` を 2 回呼び出し（2 回目の前に ai_monthly_remaining を 0 に変更） | 2 回目以降は renew 処理が走らず ai_monthly_remaining=0 のまま | `BillingWebhookService::handleSubscriptionRenewed()` |
| 4-2-4 | 【サブスク同期】 active で STANDARD プランを付与する | 正常系 | FREE の Group、`status=active`、standard plan ID | plan=STANDARD | `BillingWebhookService::handleSubscriptionChanged()` |
| 4-2-5 | 【サブスク同期】 canceled（期間終了後）で FREE に戻す | 正常系 | STANDARD の Group、`status=canceled`、`current_period_end` が過去 | plan=FREE | `BillingWebhookService::handleSubscriptionDeleted()` |
| 4-2-6 | 【サブスク同期】 paused で FREE に戻す | 正常系 | STANDARD の Group、`status=paused` | plan=FREE | `BillingWebhookService::handleSubscriptionPaused()` |
| 4-2-7 | 【サブスク同期】 不明な plan ID ではプラン更新しない | 異常系 | FREE の Group、`status=active`、`plan.id` が config 未登録 | plan=FREE のまま | `BillingWebhookService::handleSubscriptionChanged()` |
| 4-2-8 | 【支払い失敗】 charge.failed は例外なく処理できる | 正常系 | 任意の Group、`charge.failed` 相当ペイロード（`data.id` のみ） | 例外なし、Group の plan は変更されない | `BillingWebhookService::handleChargeFailed()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/BillingWebhookServiceTest.php --stop-on-failure
```
