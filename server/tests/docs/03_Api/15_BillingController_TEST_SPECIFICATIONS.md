# BillingController テストケース詳細仕様

## 概要

課金・サブスクリプション API のテスト。PAY.JP を利用したサブスクリプション開始、買い切りパック購入、カード登録・更新・削除、サブスク解約、プラン変更予定取り消し、課金状態取得、請求履歴・次回お支払い予定取得を検証する。`auth:sanctum` + `verified` ミドルウェア配下のため、未認証（401）・メール未認証（409）も確認する。`BillingService` はモックし、HTTP レスポンス（ステータスコード・JSON 構造）を中心に確認する。

`subscribe` / `purchasePack` のパスパラメータはルート `where` 制約で絞り込むため、enum 外の値は FormRequest バリデーション（422）に到達せず **404** となる。

`BaseApiRequest::authorize()` がグループ所属を先に検査するため、グループ未所属（422）は `rules()` 由来のバリデーションエラーより先に確定する。

## エンドポイント

| メソッド | パス | コントローラメソッド |
|---|---|---|
| GET | `/billing/status` | `status()` |
| GET | `/billing/invoices` | `invoices()` |
| POST | `/billing/subscription/{subscriptionType}` | `subscribe()` |
| POST | `/billing/subscription/cancel` | `cancel()` |
| POST | `/billing/subscription/resume` | `resume()` |
| POST | `/billing/packs/{packType}` | `purchasePack()` |
| POST | `/billing/card` | `updateCard()` |
| DELETE | `/billing/card` | `deleteCard()` |

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 3-15-1 | 【課金状態取得】 正常に課金状態を取得できる | 正常系 | 認証済み・メール認証済みユーザー | HTTP 200、plan / isSubscribed / subscriptionStatus / subscriptionEndsAt / pendingPlanChange / pmType / pmLastFour / pmExpMonth / pmExpYear を含む JSON | `BillingController::status()` |
| 3-15-2 | 【課金状態取得】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::status()` |
| 3-15-3 | 【課金状態取得】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::status()` |
| 3-15-4 | 【課金状態取得】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::status()` |
| 3-15-5 | 【課金状態取得】 サービス例外 | 異常系 | BillingService::getBillingStatus が例外を投げる | HTTP 500 | `BillingController::status()` |
| 3-15-6 | 【請求履歴取得】 正常に請求履歴と次回お支払い予定を取得できる | 正常系 | 認証済み・メール認証済みユーザー | HTTP 200、data.upcomingInvoice（date / lines / subtotal / subtotalExcludingTax / tax / total / amountDue）および data.pastInvoices（各要素に id / date / description / total）を含む JSON | `BillingController::invoices()` |
| 3-15-7 | 【請求履歴取得】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::invoices()` |
| 3-15-8 | 【請求履歴取得】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::invoices()` |
| 3-15-9 | 【請求履歴取得】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::invoices()` |
| 3-15-10 | 【請求履歴取得】 サービス例外 | 異常系 | BillingService::getInvoices が例外を投げる | HTTP 500 | `BillingController::invoices()` |
| 3-15-11 | 【サブスク開始】 正常にサブスクリプションを開始できる | 正常系 | 認証済み・メール認証済みユーザー、subscriptionType=standard、cardToken あり | HTTP 200、success=true、data に BillingStatus | `BillingController::subscribe()` |
| 3-15-12 | 【サブスク開始】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::subscribe()` |
| 3-15-13 | 【サブスク開始】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::subscribe()` |
| 3-15-14 | 【サブスク開始】 ルート不一致（subscriptionType 不正） | 異常系 | subscriptionType にルート制約外の値（例: pro） | HTTP 404 | `BillingController::subscribe()` |
| 3-15-15 | 【サブスク開始】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::subscribe()` |
| 3-15-16 | 【サブスク開始】 cardToken なしでサブスクリプションを開始できる | 正常系 | 認証済み・メール認証済みユーザー、subscriptionType=standard、cardToken なし | HTTP 200、success=true、data に BillingStatus | `BillingController::subscribe()` |
| 3-15-17 | 【サブスク開始】 バリデーションエラー（cardToken が文字列でない） | 異常系 | cardToken が数値 | HTTP 422、cardToken のバリデーションエラー | `BillingController::subscribe()` |
| 3-15-18 | 【サブスク開始】 既にサブスク済み | 異常系 | BillingService::createSubscription が HttpException 422（already_subscribed）を投げる | HTTP 422、already_subscribed メッセージ | `BillingController::subscribe()` |
| 3-15-19 | 【サブスク開始】 サービス例外 | 異常系 | BillingService::createSubscription が例外を投げる | HTTP 500 | `BillingController::subscribe()` |
| 3-15-20 | 【サブスク解約】 正常にサブスクリプションの解約を受け付けできる | 正常系 | 認証済み・メール認証済みユーザー | HTTP 200、success=true、data に BillingStatus | `BillingController::cancel()` |
| 3-15-21 | 【サブスク解約】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::cancel()` |
| 3-15-22 | 【サブスク解約】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::cancel()` |
| 3-15-23 | 【サブスク解約】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::cancel()` |
| 3-15-24 | 【サブスク解約】 有効なサブスクなし | 異常系 | BillingService::cancelSubscription が HttpException 422（no_active_subscription）を投げる | HTTP 422、no_active_subscription メッセージ | `BillingController::cancel()` |
| 3-15-25 | 【サブスク解約】 サービス例外 | 異常系 | BillingService::cancelSubscription が例外を投げる | HTTP 500 | `BillingController::cancel()` |
| 3-15-26 | 【プラン変更予定取り消し】 正常にプラン変更予定を取り消せる | 正常系 | 認証済み・メール認証済みユーザー | HTTP 200、success=true、data に更新後の BillingStatus（pendingPlanChange 含む） | `BillingController::resume()` |
| 3-15-27 | 【プラン変更予定取り消し】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::resume()` |
| 3-15-28 | 【プラン変更予定取り消し】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::resume()` |
| 3-15-29 | 【プラン変更予定取り消し】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::resume()` |
| 3-15-30 | 【プラン変更予定取り消し】 予定変更なし | 異常系 | BillingService::resumeSubscription が HttpException 422（no_pending_plan_change）を投げる | HTTP 422、no_pending_plan_change メッセージ | `BillingController::resume()` |
| 3-15-31 | 【プラン変更予定取り消し】 サービス例外 | 異常系 | BillingService::resumeSubscription が例外を投げる | HTTP 500 | `BillingController::resume()` |
| 3-15-32 | 【パック購入】 正常にパックを購入できる（light、cardToken あり） | 正常系 | 認証済み・メール認証済みユーザー、packType=light、cardToken あり | HTTP 200、success=true、data に BillingStatus | `BillingController::purchasePack()` |
| 3-15-33 | 【パック購入】 正常にパックを購入できる（value、cardToken なし） | 正常系 | 認証済み・メール認証済みユーザー、packType=value、cardToken なし | HTTP 200、success=true、data に BillingStatus | `BillingController::purchasePack()` |
| 3-15-34 | 【パック購入】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::purchasePack()` |
| 3-15-35 | 【パック購入】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::purchasePack()` |
| 3-15-36 | 【パック購入】 ルート不一致（packType 不正） | 異常系 | packType にルート制約外の値（例: premium） | HTTP 404 | `BillingController::purchasePack()` |
| 3-15-37 | 【パック購入】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::purchasePack()` |
| 3-15-38 | 【パック購入】 バリデーションエラー（cardToken が文字列でない） | 異常系 | cardToken が数値 | HTTP 422、cardToken のバリデーションエラー | `BillingController::purchasePack()` |
| 3-15-39 | 【パック購入】 支払い方法未登録 | 異常系 | BillingService::purchasePack が HttpException 422（no_payment_method）を投げる | HTTP 422、支払い方法が登録されていません。カード情報を登録してください。 | `BillingController::purchasePack()` |
| 3-15-40 | 【パック購入】 サービス例外 | 異常系 | BillingService::purchasePack が例外を投げる | HTTP 500 | `BillingController::purchasePack()` |
| 3-15-41 | 【カード更新】 正常にカード情報を更新できる | 正常系 | 認証済み・メール認証済みユーザー、cardToken あり | HTTP 200、success=true、data に BillingStatus | `BillingController::updateCard()` |
| 3-15-42 | 【カード更新】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::updateCard()` |
| 3-15-43 | 【カード更新】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::updateCard()` |
| 3-15-44 | 【カード更新】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::updateCard()` |
| 3-15-45 | 【カード更新】 バリデーションエラー（cardToken 未入力） | 異常系 | cardToken が空文字 | HTTP 422、cardToken のバリデーションエラー | `BillingController::updateCard()` |
| 3-15-46 | 【カード更新】 バリデーションエラー（cardToken が文字列でない） | 異常系 | cardToken が数値 | HTTP 422、cardToken のバリデーションエラー | `BillingController::updateCard()` |
| 3-15-47 | 【カード更新】 サービス例外 | 異常系 | BillingService::updateCard が例外を投げる | HTTP 500 | `BillingController::updateCard()` |
| 3-15-48 | 【カード削除】 正常にカード情報を削除できる | 正常系 | 認証済み・メール認証済みユーザー | HTTP 200、success=true、data=null | `BillingController::deleteCard()` |
| 3-15-49 | 【カード削除】 未認証 | 異常系 | 認証なし | HTTP 401 | `BillingController::deleteCard()` |
| 3-15-50 | 【カード削除】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null）、Accept: application/json | HTTP 409、メール未確認メッセージ | `BillingController::deleteCard()` |
| 3-15-51 | 【カード削除】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `BillingController::deleteCard()` |
| 3-15-52 | 【カード削除】 課金アカウント未登録 | 異常系 | BillingService::deleteCard が HttpException 422（no_billing_account）を投げる | HTTP 422、課金情報が登録されていません。 | `BillingController::deleteCard()` |
| 3-15-53 | 【カード削除】 サービス例外 | 異常系 | BillingService::deleteCard が例外を投げる | HTTP 500 | `BillingController::deleteCard()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/BillingControllerTest.php --stop-on-failure
```
