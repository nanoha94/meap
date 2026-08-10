# BillingController テストケース詳細仕様

## 概要

課金・サブスクリプション API のテスト。Stripe Checkout / Customer Portal セッション作成、課金状態取得、請求履歴・次回お支払い予定取得を検証する。`auth:sanctum` + `verified` ミドルウェア配下のため、未認証（401）・メール未認証（409）も確認する。`BillingService` はモックし、HTTP レスポンス（ステータスコード・JSON 構造）を中心に確認する。

`subscribe` / `purchasePack` のパスパラメータはルート `where` 制約で絞り込むため、enum 外の値は FormRequest バリデーション（422）に到達せず **404** となる。

## エンドポイント

| メソッド | パス | コントローラメソッド |
|---|---|---|
| GET | `/billing/status` | `status()` |
| GET | `/billing/invoices` | `invoices()` |
| POST | `/billing/subscribe/{subscriptionType}` | `subscribe()` |
| POST | `/billing/portal` | `portal()` |
| POST | `/billing/subscription/resume` | `resume()` |
| POST | `/billing/packs/{packType}` | `purchasePack()` |

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 3-15-1 | 【課金状態取得】 正常に課金状態を取得できる | 正常系 | 認証済み・メール認証済みユーザー | HTTP 200、plan / isSubscribed / subscriptionStatus / subscriptionEndsAt / pendingPlanChange / pmType / pmLastFour / pmExpMonth / pmExpYear を含む JSON | `BillingController::status()` |
| 3-15-2 | 【課金状態取得】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::status()` |
| 3-15-3 | 【課金状態取得】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::status()` |
| 3-15-4 | 【課金状態取得】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::status()` |
| 3-15-5 | 【課金状態取得】 サービス例外 | 異常系 | BillingService::getBillingStatus が例外を投げる | HTTP 500 | `BillingController::status()` |
| 3-15-6 | 【請求履歴取得】 正常に請求履歴と次回お支払い予定を取得できる | 正常系 | 認証済み・メール認証済みユーザー | HTTP 200、data.upcomingInvoice（date / lines / subtotal / tax / total / amountDue）および data.pastInvoices（各要素に id / date / total / invoiceUrl）を含む JSON | `BillingController::invoices()` |
| 3-15-7 | 【請求履歴取得】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::invoices()` |
| 3-15-8 | 【請求履歴取得】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::invoices()` |
| 3-15-9 | 【請求履歴取得】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::invoices()` |
| 3-15-10 | 【請求履歴取得】 サービス例外 | 異常系 | BillingService::getInvoices が例外を投げる | HTTP 500 | `BillingController::invoices()` |
| 3-15-11 | 【サブスク開始】 Checkout URL を返却する | 正常系 | 認証済み・メール認証済みユーザー、subscriptionType=standard | HTTP 200、success=true、data.checkoutUrl が URL 文字列 | `BillingController::subscribe()` |
| 3-15-12 | 【サブスク開始】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::subscribe()` |
| 3-15-13 | 【サブスク開始】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::subscribe()` |
| 3-15-14 | 【サブスク開始】 ルート不一致（subscriptionType 不正） | 異常系 | subscriptionType にルート制約外の値（例: pro） | HTTP 404 | `BillingController::subscribe()` |
| 3-15-15 | 【サブスク開始】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::subscribe()` |
| 3-15-16 | 【サブスク開始】 既にサブスク済み | 異常系 | BillingService::createSubscriptionCheckout が HttpException 422（already_subscribed）を投げる | HTTP 422、already_subscribed メッセージ | `BillingController::subscribe()` |
| 3-15-17 | 【サブスク開始】 サービス例外 | 異常系 | BillingService::createSubscriptionCheckout が例外を投げる | HTTP 500 | `BillingController::subscribe()` |
| 3-15-18 | 【Customer Portal】 Portal URL を返却する | 正常系 | 認証済み・メール認証済みユーザー | HTTP 200、success=true、data.portalUrl が URL 文字列 | `BillingController::portal()` |
| 3-15-19 | 【Customer Portal】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::portal()` |
| 3-15-20 | 【Customer Portal】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::portal()` |
| 3-15-21 | 【Customer Portal】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::portal()` |
| 3-15-22 | 【Customer Portal】 課金アカウント未登録 | 異常系 | BillingService::createPortalSession が HttpException 422（no_billing_account）を投げる | HTTP 422、no_billing_account メッセージ | `BillingController::portal()` |
| 3-15-23 | 【Customer Portal】 サービス例外 | 異常系 | BillingService::createPortalSession が例外を投げる | HTTP 500 | `BillingController::portal()` |
| 3-15-24 | 【プラン変更予定取り消し】 正常にプラン変更予定を取り消せる | 正常系 | 認証済み・メール認証済みユーザー | HTTP 200、success=true、data に更新後の BillingStatus（pendingPlanChange 含む） | `BillingController::resume()` |
| 3-15-25 | 【プラン変更予定取り消し】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::resume()` |
| 3-15-26 | 【プラン変更予定取り消し】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::resume()` |
| 3-15-27 | 【プラン変更予定取り消し】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::resume()` |
| 3-15-28 | 【プラン変更予定取り消し】 予定変更なし | 異常系 | BillingService::resumeSubscription が HttpException 422（no_pending_plan_change）を投げる | HTTP 422、no_pending_plan_change メッセージ | `BillingController::resume()` |
| 3-15-29 | 【プラン変更予定取り消し】 サービス例外 | 異常系 | BillingService::resumeSubscription が例外を投げる | HTTP 500 | `BillingController::resume()` |
| 3-15-30 | 【パック購入】 Checkout URL を返却する（light） | 正常系 | 認証済み・メール認証済みユーザー、packType=light | HTTP 200、success=true、data.checkoutUrl が URL 文字列 | `BillingController::purchasePack()` |
| 3-15-31 | 【パック購入】 Checkout URL を返却する（value） | 正常系 | 認証済み・メール認証済みユーザー、packType=value | HTTP 200、success=true、data.checkoutUrl が URL 文字列 | `BillingController::purchasePack()` |
| 3-15-32 | 【パック購入】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::purchasePack()` |
| 3-15-33 | 【パック購入】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::purchasePack()` |
| 3-15-34 | 【パック購入】 ルート不一致（packType 不正） | 異常系 | packType にルート制約外の値（例: premium） | HTTP 404 | `BillingController::purchasePack()` |
| 3-15-35 | 【パック購入】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::purchasePack()` |
| 3-15-36 | 【パック購入】 サービス例外 | 異常系 | BillingService::createPackCheckout が例外を投げる | HTTP 500 | `BillingController::purchasePack()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/BillingControllerTest.php --stop-on-failure
```
