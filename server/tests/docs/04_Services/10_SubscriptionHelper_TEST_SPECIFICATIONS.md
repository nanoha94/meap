# SubscriptionHelper テストケース詳細仕様

## 概要

`BillingService` と `BillingWebhookService` が共有するサブスクリプション同期用純関数 `SubscriptionHelper` の Feature テスト。Square SDK オブジェクトの正規化、請求周期・契約終了日の算出、`subscriptions` テーブルへの upsert を直接検証する。

## テスト方針

- Square API への実通信は行わない
- `config('app.timezone')` は `Asia/Tokyo` を `beforeEach` で設定する
- Square SDK オブジェクトは `makeSubscriptionHelperSquareSdk` で構築する
- 日付の期待値は `billingDateToCarbon()` と同様に、タイムゾーン付き `startOfDay()->startOfSecond()` の Carbon と比較する
- `upsertSubscriptionRecord()` は `RefreshDatabase` で DB 状態を検証する。成功時は `true`、ID または status が不正でスキップしたときは `false` を返す

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 4-10-1 | 【請求周期終了日】 charged_through_date を Carbon に変換する | 正常系 | `charged_through_date='2026-03-20'`、`canceled_date=null` | 該当日の Carbon（日始まり・秒切り捨て） | `SubscriptionHelper::periodEnd()` |
| 4-10-2 | 【請求周期終了日】 charged_through_date 欠落時 canceled_date にフォールバックする | 正常系 | `charged_through_date=null`、`canceled_date='2026-04-01'` | canceled_date 由来の Carbon | `SubscriptionHelper::periodEnd()` |
| 4-10-3 | 【請求周期終了日】 日付が両方欠落なら null を返す | 異常系 | 両方 null または空文字 | null | `SubscriptionHelper::periodEnd()` |
| 4-10-4 | 【契約終了日】 active かつ canceled_date なしなら null | 正常系 | `status=active`、`canceled_date=null`、periodEnd あり | null | `SubscriptionHelper::resolveEndsAt()` |
| 4-10-5 | 【契約終了日】 canceled なら periodEnd を返す | 正常系 | `status=canceled`、periodEnd あり | periodEnd | `SubscriptionHelper::resolveEndsAt()` |
| 4-10-6 | 【契約終了日】 canceled_date ありなら periodEnd を返す | 正常系 | `status=active`、`canceled_date` あり、periodEnd あり | periodEnd | `SubscriptionHelper::resolveEndsAt()` |
| 4-10-7 | 【Square 正規化】 Subscription の各フィールドを配列に変換する | 正常系 | 全フィールドを持つ `SquareSubscription` | id / customer_id / plan_variation_id / status / start_date / charged_through_date / canceled_date の配列 | `SubscriptionHelper::normalizeFromSquare()` |
| 4-10-8 | 【Square 正規化】 id が空文字なら null にする | 異常系 | `id=''` の `SquareSubscription` | 配列の `id` が null | `SubscriptionHelper::normalizeFromSquare()` |
| 4-10-9 | 【サブスク upsert】 正規化配列から subscriptions を新規作成する | 正常系 | Group、`status=ACTIVE`、charged_through_date / start_date 付き配列 | true を返し、`subscriptions` に active、ends_at=null、current_period_* が日付と一致 | `SubscriptionHelper::upsertSubscriptionRecord()` |
| 4-10-10 | 【サブスク upsert】 解約予定（canceled_date 付き ACTIVE）で ends_at を設定する | 正常系 | `status=ACTIVE`、`canceled_date` と charged_through_date が同一 | true を返し、status=canceled、ends_at / canceled_at が周期終了日 | `SubscriptionHelper::upsertSubscriptionRecord()` |
| 4-10-11 | 【サブスク upsert】 charged_through_date 欠落時 canceled_date で current_period_end を補完する | 正常系 | charged_through_date 欠落、canceled_date のみ | true を返し、current_period_end が canceled_date と一致 | `SubscriptionHelper::upsertSubscriptionRecord()` |
| 4-10-12 | 【サブスク upsert】 既存レコードを square_subscription_id で更新する | 正常系 | 既存 Subscription、同一 square_subscription_id で upsert | true を返し、レコード 1 件のまま status / plan_variation が更新される | `SubscriptionHelper::upsertSubscriptionRecord()` |
| 4-10-13 | 【サブスク upsert】 subscription id 欠落時は DB を更新しない | 異常系 | `id=''` | false を返し、subscriptions 件数 0 | `SubscriptionHelper::upsertSubscriptionRecord()` |
| 4-10-14 | 【サブスク upsert】 status 欠落時は DB を更新しない | 異常系 | `status=null` | false を返し、subscriptions 件数 0 | `SubscriptionHelper::upsertSubscriptionRecord()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/SubscriptionHelperTest.php --stop-on-failure
```
