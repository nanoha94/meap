# Stripe 課金・Webhook 手順

サブスクリプションや買い切りパック購入後、アプリ側のプラン・利用回数を更新するには **Stripe Webhook の受信が必須** です。

Checkout 完了だけでは `Group.plan` や `subscriptions` テーブルは更新されません。Webhook が `POST /stripe/webhook` に届き、Laravel Cashier と `BillingWebhookService` が処理します。

## 環境変数

`server/.env`（本番は Railway の Variables）に以下を設定します。

| 変数                                 | 説明                                              |
| ------------------------------------ | ------------------------------------------------- |
| `STRIPE_KEY`                         | 公開可能キー（`pk_test_...` / `pk_live_...`）     |
| `STRIPE_SECRET`                      | シークレットキー（`sk_test_...` / `sk_live_...`） |
| `STRIPE_WEBHOOK_SECRET`              | Webhook 署名シークレット（**環境ごとに異なる**）  |
| `STRIPE_PRICE_SUBSCRIPTION_STANDARD` | スタンダードプランの Price ID                     |
| `STRIPE_PRICE_PACK_LIGHT`            | ライトパックの Price ID                           |
| `STRIPE_PRICE_PACK_VALUE`            | バリューパックの Price ID                         |

---

## ローカル開発

Stripe は `localhost` に直接 Webhook を送れないため、**Stripe CLI の `stripe listen` が必要** です。

### 1. 前提

- [Stripe CLI](https://stripe.com/docs/stripe-cli) をインストールし、`stripe login` 済みであること
- Docker（`./docker-up.sh`）でバックエンドが起動していること

### 2. Webhook 転送を起動

課金テスト中は、別ターミナルで常に起動しておきます。

```bash
stripe listen --forward-to http://localhost:8001/stripe/webhook
```

- `8001` は Laravel コンテナの HTTP ポート（`APP_HTTP_PORT`）
- `8000` は nginx の HTTPS 経由のため、CLI からは `8001` を使う

起動時に表示される `whsec_...` を `server/.env` の `STRIPE_WEBHOOK_SECRET` に設定します。

> **注意:** `stripe listen` を再起動するたびに `whsec_...` が変わる場合があります。`.env` と一致していないと署名検証が失敗し、Webhook は HTTP 403 になります。

### 3. 課金テストの流れ

1. `stripe listen` を起動
2. フロントエンドで Checkout を完了
3. CLI に `[200] POST http://localhost:8001/stripe/webhook` が表示されることを確認
4. `/settings/billing` でプランが反映されていることを確認

### 4. 過去の決済を同期する（Webhook 未受信だった場合）

Checkout は完了しているが DB に反映されていないとき:

```bash
# 該当イベント ID を確認
stripe events list --type customer.subscription.created --limit 5

# listen 起動中に再送
stripe events resend evt_XXXXX
```

---

## 本番（Railway）

**`stripe listen` は本番では不要です。** ローカル専用のツールです。

Railway 上のアプリは公開 URL を持つため、Stripe Dashboard から直接 Webhook を送れます。常時実行するコマンドや追加プロセスは不要です。

### 1. Stripe Dashboard で Webhook エンドポイントを登録

[Developers → Webhooks](https://dashboard.stripe.com/webhooks) でエンドポイントを追加します。

| 項目   | 値                                            |
| ------ | --------------------------------------------- |
| URL    | `https://<本番 API ドメイン>/stripe/webhook`  |
| モード | テスト / 本番それぞれで別エンドポイントを推奨 |

受信するイベント（最低限）:

- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.paid`
- `checkout.session.completed`

Cashier が利用するその他のイベントも含め、「関連イベントを選択」または Cashier 推奨セットを選ぶと安全です。

### 2. Railway の環境変数を設定

Dashboard の Webhook エンドポイント詳細画面にある **Signing secret**（`whsec_...`）を、Railway の `STRIPE_WEBHOOK_SECRET` に設定します。

- ローカル CLI の `whsec_...` とは **別の値** です
- テストモードと本番モードで Price ID・Webhook secret もそれぞれ切り替えます

### 3. 動作確認

1. Stripe Dashboard → Webhooks → 該当エンドポイント → **Send test webhook**
2. または本番/ステージングで Checkout を実行
3. Dashboard の **Recent deliveries** で HTTP 200 を確認

---

## トラブルシュート

### プランが `free` のまま / `subscriptions` が 0

| 確認項目         | ローカル                                         | 本番                                                      |
| ---------------- | ------------------------------------------------ | --------------------------------------------------------- |
| Webhook 転送     | `stripe listen` が起動しているか                 | Dashboard のエンドポイント URL が正しいか                 |
| 署名シークレット | CLI 表示の `whsec_...` と `.env` が一致するか    | Dashboard の Signing secret と Railway の変数が一致するか |
| マイグレーション | `subscriptions` テーブルの migration が `Ran` か | 同上                                                      |
| Stripe 側        | Dashboard → Events でイベントが発火しているか    | Recent deliveries のステータス                            |

DB 状態の確認例:

```bash
docker compose exec laravel.test php artisan tinker --execute="
  \$g = App\Models\Group::first();
  echo 'stripe_id: ' . \$g->stripe_id . PHP_EOL;
  echo 'plan: ' . \$g->plan?->value . PHP_EOL;
  echo 'subscriptions: ' . \$g->subscriptions()->count() . PHP_EOL;
"
```

- `stripe_id` あり + `subscriptions: 0` → Webhook 未受信が最有力
- `subscriptions: 1` 以上 + `plan: free` → Webhook は届いたがプラン同期ロジックを確認

### ローカルと本番の使い分け（まとめ）

|                                  | ローカル                                                          | 本番（Railway）                      |
| -------------------------------- | ----------------------------------------------------------------- | ------------------------------------ |
| Webhook 受信方法                 | `stripe listen --forward-to http://localhost:8001/stripe/webhook` | Stripe Dashboard のエンドポイント    |
| `STRIPE_WEBHOOK_SECRET` の取得元 | Stripe CLI 起動時の表示                                           | Dashboard → Webhook → Signing secret |
| 常時実行が必要か                 | 課金テスト中のみ CLI を起動                                       | **不要**（Railway アプリが直接受信） |
