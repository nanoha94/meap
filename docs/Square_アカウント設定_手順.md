# Square アカウント設定（Phase 0）

meap の課金（サブスクリプション・買い切りパック）を動かす前に、Square Developer Console と環境変数の準備を行います。

関連:

- [Phase 0 チェックリスト](Square_Phase0_チェックリスト.md)
- [Webhook 手順](Square_課金_Webhook_手順.md)
- [ブラウザ E2E 手順](Square_課金_E2E_手順.md)

Developer Console の画面操作の詳細は [Sandbox 概要](https://developer.squareup.com/docs/devtools/sandbox/overview) と [日本向け開発](https://developer.squareup.com/docs/international-development/japan) を参照してください。ここでは meap で必要な値と env の対応に絞ります。

---

## 1. Developer Console とアプリ

1. [Square Developer Console](https://developer.squareup.com/console) に Square 加盟店アカウントでサインインする
2. 左 **Applications** → アプリ `meap` を作成（または既存を開く）
3. 画面上部のトグルで **Sandbox** を選ぶ（本番公開前は Sandbox のみでよい）

控える値:

| Console の場所 | 環境変数 |
| -------------- | -------- |
| **Credentials** → Application ID | `SQUARE_APPLICATION_ID` / `NEXT_PUBLIC_SQUARE_APPLICATION_ID` |
| **Credentials** → Access token（Show） | `SQUARE_ACCESS_TOKEN` |
| **Locations** → Location ID（日本・JPY の行） | `SQUARE_LOCATION_ID` / `NEXT_PUBLIC_SQUARE_LOCATION_ID` |

**Access token について:** 日本円用に **Sandbox test account**（国=Japan）を追加した場合は、**OAuth** → **Test account authorizations** で口座を Authorize し、その口座用 Access token を API に使うことがある（Credentials の既定トークンと Location が合わないと CreatePayment / CreateSubscription が失敗する）。Locations が最初から日本・JPY なら Credentials のトークンだけで足りることもある。

本番公開時は同じ手順を上部トグル **Production** で繰り返し、Sandbox の値は本番では使えない。

---

## 2. スタンダード用サブスクプラン（580 円 / 月 / JPY）

meap は SaaS の固定月額のため、Square データの「サブスクリプション」UI より **Catalog API で STATIC プラン**を作る方が確実です。

1. `SUBSCRIPTION_PLAN` を 1 件 Upsert（名前例: meap Standard）
2. 続けて `SUBSCRIPTION_PLAN_VARIATION` を Upsert（月次・580 JPY・STATIC）

作成した **Variation の id**（`SUBSCRIPTION_PLAN_VARIATION` の `catalog_object.id`。Plan 本体の id ではない）を `SQUARE_PLAN_VARIATION_STANDARD` に設定します。

公式: [Subscription Plans and Variations](https://developer.squareup.com/docs/subscriptions-api/plans-and-variations)

**買い切りパック（ライト / バリュー）**は Payments API の都度課金のため、Catalog の商品登録は不要です。金額は `SQUARE_PRICE_PACK_LIGHT` / `SQUARE_PRICE_PACK_VALUE` で指定します。

---

## 3. Webhook 購読（Sandbox → ステージング）

Square の環境は Sandbox と Production のみです。Sandbox 購読の通知先はステージング、Production 購読の通知先は本番にします。ローカルでは Webhook を受信しません。詳細は [Webhook 手順](Square_課金_Webhook_手順.md) を参照してください。

1. Console → アプリ `meap` → **Sandbox** → **Webhooks** → **Subscriptions**
2. **Add subscription** — 名前例: `meap-sandbox`
3. **URL**: `https://dev.api.meap-app.com/square/webhook`（ローカルや `example.com` にはしない）
4. イベント（meap が処理するもの）:
   - `invoice.payment_made`（月次更新・AI 枠リセット）
   - `subscription.updated`（ステータス同期・解約反映）
   - `payment.updated`（パック等の確認・失敗ログ）
5. 購読詳細の **Signature key** → Railway ステージングの `SQUARE_WEBHOOK_SIGNATURE_KEY`
6. 登録した URL をそのまま Railway ステージングの `SQUARE_WEBHOOK_NOTIFICATION_URL` に入れる（**署名検証はこの URL と完全一致**が必要）

ローカルの `server/.env` では、この2変数は空で構いません。

---

## 4. 環境変数の設定

### サーバー（`server/.env` / Railway Variables）

[server/.env.example](../server/.env.example) の Square セクションをコピーし、値を埋めます。

| 変数 | 説明 |
| ---- | ---- |
| `SQUARE_APPLICATION_ID` | Application ID |
| `SQUARE_ACCESS_TOKEN` | Sandbox / Production の Access token（秘密） |
| `SQUARE_LOCATION_ID` | 課金に使う Location ID |
| `SQUARE_PLAN_VARIATION_STANDARD` | サブスク用 Plan Variation ID |
| `SQUARE_WEBHOOK_SIGNATURE_KEY` | Webhook 購読の Signature key（Railway のみ。ローカルは空） |
| `SQUARE_WEBHOOK_NOTIFICATION_URL` | Webhook 購読 URL（Dashboard と同一文字列。Railway のみ。ローカルは空） |
| `SQUARE_AMOUNT_SUBSCRIPTION_STANDARD` | サブスク月額（表示用・580） |
| `SQUARE_PRICE_PACK_LIGHT` | ライトパック（400） |
| `SQUARE_PRICE_PACK_VALUE` | バリューパック（800） |

`SQUARE_ACCESS_TOKEN` はフロントエンドや公開リポジトリに含めないこと。

### フロント（`client/.env` / Vercel）

| 変数 | 説明 |
| ---- | ---- |
| `NEXT_PUBLIC_SQUARE_APPLICATION_ID` | Application ID（Web Payments SDK） |
| `NEXT_PUBLIC_SQUARE_LOCATION_ID` | Location ID（tokenize / SDK 初期化） |
| `NEXT_PUBLIC_SQUARE_SCRIPT_URL` | Sandbox: `https://sandbox.web.squarecdn.com/v1/square.js` / 本番: `https://web.squarecdn.com/v1/square.js` |

---

## 5. Sandbox / Production の対応

| 環境 | キー・Location | Webhook |
| ---- | -------------- | ------- |
| ローカル | Developer Console **Sandbox** | 受信しない。`SQUARE_WEBHOOK_*` は空 |
| ステージング | Developer Console **Sandbox** | Sandbox 購読 URL は `https://dev.api.meap-app.com/square/webhook`。Signature key と `SQUARE_WEBHOOK_NOTIFICATION_URL` は Railway ステージング |
| 本番 | Console **Production** の Credentials / Location / Plan Variation を再取得 | Production 購読 URL は `https://api.meap-app.com/square/webhook`。キーと URL は Railway 本番 |

Sandbox の [テストカード](https://developer.squareup.com/docs/devtools/sandbox/payments) は **Sandbox のみ** 使用可能です。Production では実カードで少額テストを行います。

---

## 6. Phase 0 API スモーク（任意・ブラウザ前）

Web Payments はアプリ実装後に E2E で確認します。Console だけで次が通れば Phase 0 の API 準備は完了です。

1. **Customers** → Create customer → `customer.id`
2. **Cards** → Create card（Sandbox のテスト `source_id` + `customer_id`）→ `card.id`
3. **Subscriptions** → Create subscription（`location_id` / `customer_id` / `card_id` / `plan_variation_id`）

失敗しやすい原因: Production トークン混在、Location が JPY 以外、Variation ID に Plan 本体 id を指定、Subscriptions に payment token をそのまま渡している（**card_id 必須**）。

---

## 7. 設定後の確認

1. [Square Phase 0 チェックリスト](Square_Phase0_チェックリスト.md) をすべてチェック
2. [Webhook 手順](Square_課金_Webhook_手順.md) に従い、Sandbox 購読をステージング URL に合わせ、Railway の環境変数を揃える
3. [Square 課金 E2E 手順](Square_課金_E2E_手順.md) でブラウザから 1 回通す。Webhook の到達確認はステージングで行う
