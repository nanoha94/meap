# BillingWebhookService テストケース詳細仕様

## 概要

Stripe Webhook イベントに応じた課金状態同期を担う `BillingWebhookService` の単体テスト。Webhook ペイロードを配列で構築し、各 public メソッドを直接呼び出して DB 状態を検証する。`AiUsageService` は実インスタンスを使用し、`renewBillingPeriod()` / `adjustMonthlyRemainingForPlanChange()` との連携を確認する。

## テスト方針

- Stripe API への実通信は行わない
- `config('billing.price_ids.subscription_standard')` はテスト用の固定 price ID（例: `price_test_standard`）を設定する
- `config('billing.subscription_type')` は `'default'`（本番設定と同一）を使用する
- `oncePerEvent` の検証では `Cache::flush()` またはイベント ID を変えて二重呼び出しを再現する
- Group は `stripe_id` を持つ Billable レコードとして Factory / 直接作成する
- private メソッド（`findGroupByStripeId` / `planFromPriceId` / `isManagedSubscription` 等）は public メソッド経由で間接的に検証する

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 4-2-1 | 【請求成功】 サブスク請求成功時にプラン更新と利用回数リセットを行う | 正常系 | FREE の Group（stripe_id あり）、invoice.paid ペイロード（subscription あり、standard price ID、period.end あり） | plan=STANDARD、ai_monthly_remaining=30、ai_usage_reset_at=period.end | `BillingWebhookService::handleInvoicePaid()` |
| 4-2-2 | 【請求成功】 既存 STANDARD プランでも請求周期更新で月間枠をリセットする | 正常系 | STANDARD、ai_monthly_remaining=5、新しい period.end を含む invoice.paid | ai_monthly_remaining=30、ai_usage_reset_at=新 period.end | `BillingWebhookService::handleInvoicePaid()` |
| 4-2-3 | 【請求成功】 subscription 無しの invoice はスキップする | 異常系 | invoice に subscription フィールドなし | Group の plan / ai_monthly_remaining / ai_usage_reset_at が変更されない | `BillingWebhookService::handleInvoicePaid()` |
| 4-2-4 | 【請求成功】 顧客不在（stripe_id 不一致）はスキップする | 異常系 | invoice.customer が DB に存在しない ID | Group の plan / ai_monthly_remaining / ai_usage_reset_at が変更されない | `BillingWebhookService::handleInvoicePaid()` |
| 4-2-5 | 【請求成功】 同一 event ID の再送は二重処理しない | 異常系 | 同一 evt_xxx で handleInvoicePaid を 2 回呼び出し | 2 回目以降は ai_monthly_remaining / ai_pack_remaining が加算・更新されない | `BillingWebhookService::handleInvoicePaid()` |
| 4-2-6 | 【請求成功】 不明な price ID ではプラン更新しない | 異常系 | invoice.lines に config 未登録の price ID、period.end あり | plan は変更なし、ai_monthly_remaining=30（renewBillingPeriod のみ実行） | `BillingWebhookService::handleInvoicePaid()` |
| 4-2-7 | 【請求成功】 period.end なしでは renewBillingPeriod を呼ばない | 異常系 | invoice.lines に period.end なし、standard price ID | plan=STANDARD に更新、ai_monthly_remaining / ai_usage_reset_at は変更なし | `BillingWebhookService::handleInvoicePaid()` |
| 4-2-8 | 【請求成功】 event ID が空の payload でも正常処理される | 異常系 | payload.id が空文字、有効な invoice 内容 | 冪等性チェックなしで処理実行（plan 更新・reset_at 更新が行われる） | `BillingWebhookService::handleInvoicePaid()` |
| 4-2-9 | 【請求成功】 lines.data が空配列の場合プラン更新も周期リセットも行わない | 異常系 | invoice.lines.data=[]、subscription あり、顧客存在 | plan / ai_monthly_remaining / ai_usage_reset_at が変更されない | `BillingWebhookService::handleInvoicePaid()` |
| 4-2-10 | 【サブスク同期】 active な管理対象サブスクで STANDARD プランを付与する | 正常系 | status=active、metadata.type=default、standard price ID | plan=STANDARD、FREE からの変更時は ai_monthly_remaining=30 | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-11 | 【サブスク同期】 trialing な管理対象サブスクで STANDARD プランを付与する | 正常系 | status=trialing、metadata.type=default、standard price ID | plan=STANDARD | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-12 | 【サブスク同期】 past_due な管理対象サブスクでも STANDARD プランを維持する | 正常系 | status=past_due、metadata.type=default、standard price ID | plan=STANDARD | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-13 | 【サブスク同期】 canceled なサブスクで FREE に戻す | 正常系 | status=canceled、metadata.type=default、STANDARD の Group（周期内） | plan=FREE、周期内のため ai_monthly_remaining は維持 | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-14 | 【サブスク同期】 unpaid なサブスクで FREE に戻す | 正常系 | status=unpaid、metadata.type=default | plan=FREE | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-15 | 【サブスク同期】 incomplete なサブスクで FREE に戻す | 正常系 | status=incomplete、metadata.type=default、STANDARD の Group | plan=FREE | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-16 | 【サブスク同期】 metadata なしのサブスクは管理対象として処理される | 正常系 | status=active、metadata が空（type/name キーなし）、standard price ID | plan=STANDARD（config 値へのフォールバックで管理対象と判定） | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-17 | 【サブスク同期】 管理対象外サブスクはスキップする | 異常系 | metadata.type が config('billing.subscription_type') と不一致 | plan / ai_monthly_remaining が変更されない | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-18 | 【サブスク同期】 active でも不明な price ID ではプラン更新しない | 異常系 | status=active、管理対象だが未登録 price ID | plan / ai_monthly_remaining が変更されない | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-19 | 【サブスク同期】 active かつ管理対象だが items.data が空の場合プラン更新しない | 異常系 | status=active、metadata.type=default、items.data=[] | plan / ai_monthly_remaining が変更されない | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-20 | 【サブスク同期】 billing.subscription_type 設定が空の場合は全サブスクが管理対象外となる | 異常系 | config('billing.subscription_type')=''、status=active | plan / ai_monthly_remaining が変更されない | `BillingWebhookService::syncPlanFromSubscription()` |
| 4-2-21 | 【解約予定同期】 解約キャンセル後は ends_at をクリアする | 正常系 | active、ends_at が未来、cancel_at_period_end=false | subscriptions.ends_at=null | `BillingWebhookService::syncSubscriptionCancellationSchedule()` |
| 4-2-22 | 【解約予定同期】 cancel_at_period_end=true のとき ends_at は維持する | 正常系 | active、ends_at が未来、cancel_at_period_end=true | subscriptions.ends_at が維持される | `BillingWebhookService::syncSubscriptionCancellationSchedule()` |
| 4-2-23 | 【解約予定同期】 cancel_at_period_end=true かつ ends_at 未設定時は current_period_end から ends_at を設定する | 正常系 | active、ends_at=null、cancel_at_period_end=true、current_period_end あり | subscriptions.ends_at=current_period_end | `BillingWebhookService::syncSubscriptionCancellationSchedule()` |
| 4-2-24 | 【解約予定同期】 cancel_at_period_end=true かつ ends_at 未設定時は cancel_at から ends_at を設定する | 正常系 | active、ends_at=null、cancel_at_period_end=true、current_period_end なし、cancel_at あり | subscriptions.ends_at=cancel_at | `BillingWebhookService::syncSubscriptionCancellationSchedule()` |
| 4-2-25 | 【パック購入】 買い切りパック購入時に ai_pack_remaining を加算する | 正常系 | metadata.type=pack、payment_status=paid、group_id・credits=10 | ai_pack_remaining が 10 増加、plan / ai_monthly_remaining は変更なし | `BillingWebhookService::handleCheckoutSessionCompleted()` |
| 4-2-26 | 【パック購入】 複数回購入で ai_pack_remaining が累積する | 正常系 | 異なる event ID で credits=10 を 2 回加算 | ai_pack_remaining が合計 20 | `BillingWebhookService::handleCheckoutSessionCompleted()` |
| 4-2-27 | 【パック購入】 metadata.type が pack 以外はスキップする | 異常系 | metadata.type=subscription | ai_pack_remaining が変更されない | `BillingWebhookService::handleCheckoutSessionCompleted()` |
| 4-2-28 | 【パック購入】 payment_status が paid 以外はスキップする | 異常系 | payment_status=unpaid | ai_pack_remaining が変更されない | `BillingWebhookService::handleCheckoutSessionCompleted()` |
| 4-2-29 | 【パック購入】 credits が 0 以下はスキップする | 異常系 | credits=0 | ai_pack_remaining が変更されない | `BillingWebhookService::handleCheckoutSessionCompleted()` |
| 4-2-30 | 【パック購入】 group_id が空または不正ならスキップする | 異常系 | group_id が空文字または非文字列 | ai_pack_remaining が変更されない | `BillingWebhookService::handleCheckoutSessionCompleted()` |
| 4-2-31 | 【パック購入】 存在しない group_id はスキップする | 異常系 | DB に存在しない group_id | ai_pack_remaining が変更されない | `BillingWebhookService::handleCheckoutSessionCompleted()` |
| 4-2-32 | 【パック購入】 同一 event ID の再送は二重加算しない | 異常系 | 同一 evt_xxx で handleCheckoutSessionCompleted を 2 回呼び出し | ai_pack_remaining が 1 回分のみ加算 | `BillingWebhookService::handleCheckoutSessionCompleted()` |
| 4-2-33 | 【プラン更新】 FREE から STANDARD へ変更すると月間残数が新上限になる | 正常系 | FREE、ai_monthly_remaining=0 | plan=STANDARD、ai_monthly_remaining=30 | `BillingWebhookService::updateGroupPlan()` |
| 4-2-34 | 【プラン更新】 同一プランへの更新では月間残数を変更しない | 正常系 | STANDARD、ai_monthly_remaining=20 | plan=STANDARD、ai_monthly_remaining=20 | `BillingWebhookService::updateGroupPlan()` |
| 4-2-35 | 【プラン更新】 STANDARD から FREE へ周期内変更では月間残数を維持する | 正常系 | STANDARD、ai_monthly_remaining=20、ai_usage_reset_at が未来 | plan=FREE、ai_monthly_remaining=20 | `BillingWebhookService::updateGroupPlan()` |
| 4-2-36 | 【プラン更新】 STANDARD から FREE へ周期終了後は月間残数を 0 にする | 正常系 | STANDARD、ai_monthly_remaining=20、ai_usage_reset_at が過去 | plan=FREE、ai_monthly_remaining=0 | `BillingWebhookService::updateGroupPlan()` |
| 4-2-37 | 【プラン更新】 プラン変更しても ai_pack_remaining は変更しない | 正常系 | ai_pack_remaining=15、FREE→STANDARD | ai_pack_remaining=15 | `BillingWebhookService::updateGroupPlan()` |
| 4-2-38 | 【プラン更新】 削除済み Group はサイレントにスキップする | 異常系 | トランザクション内で Group が存在しない ID | 例外を投げずに処理終了 | `BillingWebhookService::updateGroupPlan()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/BillingWebhookServiceTest.php --stop-on-failure
```
