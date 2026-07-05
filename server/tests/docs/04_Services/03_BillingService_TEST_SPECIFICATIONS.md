# BillingService テストケース詳細仕様

## 概要

Stripe Checkout / Customer Portal セッション生成、課金状態取得、請求履歴・次回お支払い予定取得を担う `BillingService` の単体テスト。Cashier の `newSubscription` / `checkout` / `billingPortalUrl` / `upcomingInvoice` / `invoices` / `createOrGetStripeCustomer` 等をモックまたはスタブし、ビジネスロジックと例外条件を検証する。

## テスト方針

- Stripe API への実通信は行わない
- `config('billing.price_ids.*')` はテスト用の固定 price ID を設定する
- `config('app.frontend_url')` はテスト用 URL を設定し、Checkout / Portal のコールバック URL を検証する
- `priceId()` は private のため、`createSubscriptionCheckout()` / `createPackCheckout()` 経由で未設定時の 500 を検証する
- `getInvoices()` は Group の `hasStripeId()` / `upcomingInvoice()` / `invoices()` をモックし、Stripe API への実通信は行わない

## テストケース一覧表

| ID     | テスト名                                                              | 種別   | 入力条件                                                | 期待される出力                                                                                                   | 該当メソッド                                   |
| ------ | --------------------------------------------------------------------- | ------ | ------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | ---------------------------------------------- |
| 4-3-1  | 【サブスク Checkout】 Checkout URL を返す                             | 正常系 | 未加入の Group、BillingSubscriptionType::STANDARD       | Stripe Checkout URL 文字列                                                                                       | `BillingService::createSubscriptionCheckout()` |
| 4-3-2  | 【サブスク Checkout】 メタデータに group_id がセットされる            | 正常系 | 未加入の Group、BillingSubscriptionType::STANDARD       | newSubscription の withMetadata に group_id が渡される                                                           | `BillingService::createSubscriptionCheckout()` |
| 4-3-3  | 【サブスク Checkout】 既にサブスク済みなら 422 を投げる               | 異常系 | subscribed('default')=true の Group                     | HttpException 422（already_subscribed メッセージ）                                                               | `BillingService::createSubscriptionCheckout()` |
| 4-3-4  | 【サブスク Checkout】 価格 ID 未設定なら 500 を投げる                 | 異常系 | billing.price_ids.subscription_standard が空            | HttpException 500（price_not_configured メッセージ）                                                             | `BillingService::createSubscriptionCheckout()` |
| 4-3-5  | 【Customer Portal】 Portal URL を返す                                 | 正常系 | stripe_id を持つ Group                                  | Stripe Portal URL 文字列                                                                                         | `BillingService::createPortalSession()`        |
| 4-3-6  | 【Customer Portal】 Stripe 未登録なら 422 を投げる                    | 異常系 | stripe_id が null の Group                              | HttpException 422（no_billing_account メッセージ）                                                               | `BillingService::createPortalSession()`        |
| 4-3-7  | 【パック Checkout】 Checkout URL を返す                               | 正常系 | BillingPackType::LIGHT                                  | Stripe Checkout URL 文字列                                                                                       | `BillingService::createPackCheckout()`         |
| 4-3-8  | 【パック Checkout】 バリューパックの Checkout URL を返す              | 正常系 | BillingPackType::VALUE                                  | Stripe Checkout URL 文字列                                                                                       | `BillingService::createPackCheckout()`         |
| 4-3-9  | 【パック Checkout】 メタデータに type/group_id/credits がセットされる | 正常系 | BillingPackType::LIGHT                                  | checkout の metadata に type=pack、group_id、credits が渡される。invoice_creation.enabled=true かつ invoice_data.metadata に同値が渡される | `BillingService::createPackCheckout()`         |
| 4-3-10 | 【パック Checkout】 価格 ID 未設定なら 500 を投げる（LIGHT）          | 異常系 | billing.price_ids.pack_light が空                       | HttpException 500（price_not_configured メッセージ）                                                             | `BillingService::createPackCheckout()`         |
| 4-3-11 | 【パック Checkout】 価格 ID 未設定なら 500 を投げる（VALUE）          | 異常系 | billing.price_ids.pack_value が空                       | HttpException 500（price_not_configured メッセージ）                                                             | `BillingService::createPackCheckout()`         |
| 4-3-12 | 【課金状態取得】 未加入（FREE）の状態を返す                           | 正常系 | plan=FREE、サブスクなし                                 | plan=free、isSubscribed=false、subscriptionStatus=null、pendingPlanChange=null                                      | `BillingService::getBillingStatus()`           |
| 4-3-13 | 【課金状態取得】 サブスク中（active）の状態を返す                     | 正常系 | plan=STANDARD、active な subscription レコード          | plan=standard、isSubscribed=true、subscriptionStatus=active、pendingPlanChange=null                                 | `BillingService::getBillingStatus()`           |
| 4-3-14 | 【課金状態取得】 猶予期間中（Grace Period）の状態を返す               | 正常系 | plan=STANDARD、解約済みだが ends_at が未来の subscription              | plan=standard、isSubscribed=true、pendingPlanChange={nextPlan:free, changesAt: ISO8601}、subscriptionEndsAt が ISO8601 文字列                                     | `BillingService::getBillingStatus()`           |
| 4-3-15 | 【課金状態取得】 キャンセル済み（猶予期間終了後）の状態を返す         | 正常系 | ends_at が過去の subscription（stripe_status=canceled） | isSubscribed=false、pendingPlanChange=null、subscriptionStatus=canceled、subscriptionEndsAt が過去の ISO8601 文字列 | `BillingService::getBillingStatus()`           |
| 4-3-16 | 【課金状態取得】 pmType / pmLastFour / pmExpMonth / pmExpYear を返す | 正常系 | pm_type='card'、pm_last_four='4242' の Group、Stripe PaymentMethod に exp_month=12 / exp_year=2028 | pmType=card、pmLastFour=4242、pmExpMonth=12、pmExpYear=2028 | `BillingService::getBillingStatus()`           |
| 4-3-17 | 【課金状態取得】 plan が null のとき FREE を返す                      | 正常系 | plan カラムが null の Group                             | plan=free                                                                                                        | `BillingService::getBillingStatus()`           |
| 4-3-18 | 【課金状態取得】 FREE に戻った後は ends_at が未来でも pendingPlanChange=null | 正常系 | plan=FREE、stripe_status=canceled、ends_at が未来の subscription | plan=free、pendingPlanChange=null、subscriptionEndsAt が ISO8601 文字列                                              | `BillingService::getBillingStatus()`           |
| 4-3-19 | 【課金状態取得】 解約キャンセル後は pendingPlanChange=null かつ ends_at をクリアする | 正常系 | plan=STANDARD、active、ends_at が未来、cancel_at_period_end=false で同期後 | pendingPlanChange=null、subscriptionEndsAt=null                                                                   | `BillingService::getBillingStatus()`           |
| 4-3-20 | 【課金状態取得】 active かつ cancel_at_period_end=true で ends_at=null のとき解約予定を返す | 正常系 | plan=STANDARD、active、ends_at=null、Stripe cancel_at_period_end=true | pendingPlanChange={nextPlan:free, changesAt: ISO8601}、subscriptionEndsAt が current_period_end の ISO8601 文字列 | `BillingService::getBillingStatus()`           |
| 4-3-21 | 【課金状態取得】 cancel_at_period_end=true かつ current_period_end=null cancel_at ありで subscriptionEndsAt を返す | 正常系 | plan=STANDARD、active、ends_at=null、cancel_at_period_end=true、current_period_end なし、cancel_at あり | pendingPlanChange={nextPlan:free, changesAt: ISO8601}、subscriptionEndsAt が cancel_at の ISO8601 文字列 | `BillingService::getBillingStatus()`           |
| 4-3-22 | 【課金状態取得】 解約予定時に pendingPlanChange を返す                | 正常系 | plan=STANDARD、stripe_status=canceled、ends_at が未来の subscription | pendingPlanChange={nextPlan:free, changesAt: ISO8601}                                                            | `BillingService::getBillingStatus()`           |
| 4-3-23 | 【課金状態取得】 予定変更なしのとき pendingPlanChange=null            | 正常系 | plan=STANDARD、active、ends_at=null の subscription     | pendingPlanChange=null                                                                                           | `BillingService::getBillingStatus()`           |
| 4-3-24 | 【プラン変更予定取り消し】 解約予定を取り消してサブスクを継続する     | 正常系 | plan=STANDARD、stripe_status=canceled、ends_at が未来の subscription | resume() が呼ばれ、例外なし                                                                                      | `BillingService::resumeSubscription()`         |
| 4-3-25 | 【プラン変更予定取り消し】 予定変更なしなら 422 を投げる              | 異常系 | plan=STANDARD、active、ends_at=null の subscription     | HttpException 422（no_pending_plan_change メッセージ）                                                           | `BillingService::resumeSubscription()`         |
| 4-3-26 | 【請求履歴取得】 次回お支払い予定と過去請求履歴を返す                 | 正常系 | stripe_id を持つ Group、upcomingInvoice() / invoices() がデータを返す | upcomingInvoice に date / lines / subtotal / tax / total / amountDue、pastInvoices に id / date / total / invoiceUrl | `BillingService::getInvoices()`                |
| 4-3-27 | 【請求履歴取得】 Stripe 未登録なら空を返す                            | 正常系 | stripe_id が null の Group（hasStripeId=false）          | upcomingInvoice=null、pastInvoices=[]                                                                            | `BillingService::getInvoices()`                |
| 4-3-28 | 【請求履歴取得】 upcoming 取得失敗時は null と pastInvoices を返す    | 正常系 | stripe_id を持つ Group、upcomingInvoice() が例外を投げる | upcomingInvoice=null、pastInvoices は invoices() の結果                                                         | `BillingService::getInvoices()`                |
| 4-3-29 | 【請求履歴取得】 upcoming が null のとき upcomingInvoice は null      | 正常系 | stripe_id を持つ Group、upcomingInvoice() が null を返す | upcomingInvoice=null、pastInvoices は invoices() の結果                                                         | `BillingService::getInvoices()`                |
| 4-3-30 | 【請求履歴取得】 過去請求がない場合 pastInvoices は空配列             | 正常系 | stripe_id を持つ Group、invoices() が空コレクション     | upcomingInvoice は upcomingInvoice() の結果、pastInvoices=[]                                                     | `BillingService::getInvoices()`                |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/BillingServiceTest.php --stop-on-failure
```
