# PAY.JP 課金・Webhook 手順

サブスクリプション更新や解約をアプリ側に反映するには **PAY.JP Webhook の受信が必須** です。カード登録 API だけでは `Group.plan` や周期更新後の AI 枠リセットは完了しません。

Webhook は `POST /payjp/webhook` に届き、`X-Payjp-Webhook-Token` ヘッダーで正当性を検証します（`PayjpWebhookController` → `BillingWebhookService`）。

アカウント作成・プラン・Dashboard 上の Webhook URL 登録は先に [PAY.JP アカウント設定（Phase 0）](PAY.JP_アカウント設定_手順.md) を完了してください。

## 環境変数

`server/.env`（本番は Railway の Variables）に以下を設定します。雛形は [server/.env.example](../server/.env.example) の PAY.JP セクションを参照。

| 変数                               | 説明                                                   |
| ---------------------------------- | ------------------------------------------------------ |
| `PAYJP_SECRET_KEY`                 | 秘密鍵（`sk_test_...` / `sk_live_...`）                |
| `PAYJP_PUBLIC_KEY`                 | 公開鍵（サーバー側で必要な場合）                       |
| `PAYJP_WEBHOOK_TOKEN`              | Webhook Token（`whook_...`。管理画面のアカウント設定） |
| `PAYJP_PLAN_SUBSCRIPTION_STANDARD` | スタンダードプラン ID（例: `pln_standard`）            |
| `PAYJP_PRICE_PACK_LIGHT`           | ライトパック金額（400）                                |
| `PAYJP_PRICE_PACK_VALUE`           | バリューパック金額（800）                              |

フロント: `NEXT_PUBLIC_PAYJP_PUBLIC_KEY`（`pk_test_...` / `pk_live_...`）

---

## ローカル開発

PAY.JP は `localhost` に直接 Webhook を送れないため、**payjp-cli の `listen`** を使います（テストモードのイベントのみ）。

### 1. 前提

- [payjp-cli](https://docs.pay.jp/v2/guide/developers/payjp-cli) をインストールし、PAY.JP アカウントでログイン済みであること
- Docker（Sail 等）でバックエンドが起動していること
- `server/.env` に `PAYJP_WEBHOOK_TOKEN`（管理画面のアカウント設定の値）を設定済みであること

### 2. Webhook 転送を起動

課金テスト中は、別ターミナルで常に起動しておきます。

```bash
payjp-cli listen --forward-to http://localhost:8001/payjp/webhook
```

- `8001` は Laravel コンテナの HTTP ポート（`APP_HTTP_PORT`）。プロジェクトの Sail 設定に合わせて変更する。
- ローカルだけ、転送するイベントを絞る例（**管理画面にはこの設定はない**。CLI 専用）:

```bash
payjp-cli listen --events subscription.created,subscription.renewed,subscription.updated,subscription.deleted,subscription.paused,charge.failed --forward-to http://localhost:8001/payjp/webhook
```

### 3. 課金テストの流れ

ブラウザでの確認項目は [PAY.JP 課金 E2E 手順](PAY.JP_課金_E2E_手順.md) を参照してください。

1. `payjp-cli listen` を起動
2. フロントでカード登録・サブスク開始またはパック購入
3. CLI に転送成功（HTTP 200）が表示されることを確認
4. `/settings/billing` でプラン・利用回数が反映されていることを確認

---

## ステージング・本番（Railway）

**payjp-cli は本番では不要**です。公開 URL に Dashboard から直接 Webhook を送ります。

### 1. Webhook エンドポイント

| 環境         | URL                                          |
| ------------ | -------------------------------------------- |
| ステージング | `https://dev.api.meap-app.com/payjp/webhook` |
| 本番         | `https://api.meap-app.com/payjp/webhook`     |

PAY.JP 管理画面で **テストモード / ライブモードごと** に URL を登録する（データ・Webhook はモード別）。**イベント種別の選択 UI はなく**、URL とモードを追加すればそのモードのイベントが送られる。

### 2. Railway の環境変数

- `PAYJP_WEBHOOK_TOKEN` はモード共通のアカウント Token（ヘッダ検証用）
- API キー・プラン ID は **ステージングは test、本番は live** に切り替える

### 3. 動作確認

1. 管理画面の Webhook から **テストイベント送信**
2. またはステージング / 本番で実際にサブスク・更新を発生させる
3. 配信履歴で HTTP 200 を確認

---

## トラブルシュート

### プランが `free` のまま / `subscriptions` が更新されない

| 確認項目     | ローカル                                               | ステージング・本番                         |
| ------------ | ------------------------------------------------------ | ------------------------------------------ |
| Webhook 転送 | `payjp-cli listen` が起動しているか                    | Dashboard の URL が `.../payjp/webhook` か |
| Token        | `.env` の `PAYJP_WEBHOOK_TOKEN` とヘッダーが一致するか | 同上                                       |
| モード       | テスト決済なら test キー・test Webhook 設定か          | live キーと live Webhook の組み合わせか    |
| デプロイ     | `PayjpWebhookController` がデプロイ済みか              | 同上                                       |

### 401 / Invalid webhook token

`PAYJP_WEBHOOK_TOKEN` が管理画面のアカウント設定の `whook_...` と一致しているか確認する。

### ローカルと本番の使い分け（まとめ）

|              | ローカル                                                            | ステージング・本番    |
| ------------ | ------------------------------------------------------------------- | --------------------- |
| Webhook 受信 | `payjp-cli listen --forward-to http://localhost:8001/payjp/webhook` | PAY.JP 管理画面の URL |
| Token        | 管理画面の `PAYJP_WEBHOOK_TOKEN`                                    | 同左                  |
| 常時実行     | 課金テスト中のみ CLI                                                | **不要**              |
