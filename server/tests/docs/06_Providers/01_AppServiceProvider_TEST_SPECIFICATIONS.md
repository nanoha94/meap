# AppServiceProvider テストケース詳細仕様

## 概要

Stripe Webhook 署名シークレットの必須設定検証を担う `AppServiceProvider::boot()` のテスト。Cashier は `STRIPE_WEBHOOK_SECRET` 未設定時に署名検証ミドルウェアを登録しないため、環境を問わず起動時に fail-closed とする。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 6-1-1 | 【Webhook設定検証】 STRIPE_WEBHOOK_SECRET 未設定時は起動時に例外 | 異常系 | APP_ENV=staging、cashier.webhook.secret が空 | RuntimeException がスローされる | `AppServiceProvider::boot()` |
| 6-1-2 | 【Webhook設定検証】 STRIPE_WEBHOOK_SECRET 設定済みなら起動できる | 正常系 | cashier.webhook.secret に値あり | 例外なく boot 完了 | `AppServiceProvider::boot()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Providers/AppServiceProviderTest.php --stop-on-failure
```
