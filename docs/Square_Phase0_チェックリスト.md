# Square Phase 0 チェックリスト

コード実装前〜初回 E2E 前に、Square Developer Console と環境変数が揃っているか確認します。詳細は [Square アカウント設定手順](Square_アカウント設定_手順.md) を参照してください。

## Developer Console・キー

- [x] Square 加盟店アカウントで Developer Console にサインイン済み
- [x] アプリ `meap` を作成済み
- [x] **Sandbox** で Application ID / Access token を取得済み
- [x] **Locations** で日本・JPY の Location ID を控えた（必要なら Sandbox test account `meap-jp` を追加・Authorize 済み）
- [ ] （本番公開時）**Production** タブで Application ID / Access token / Location ID を再取得済み

## プラン・料金

- [x] Catalog API で `SUBSCRIPTION_PLAN` + `SUBSCRIPTION_PLAN_VARIATION`（580 JPY / 月 / STATIC）を作成
- [x] `SQUARE_PLAN_VARIATION_STANDARD` が Variation の id と一致（Plan 本体 id ではない）
- [x] `SQUARE_AMOUNT_SUBSCRIPTION_STANDARD=580`
- [x] `SQUARE_PRICE_PACK_LIGHT=400` / `SQUARE_PRICE_PACK_VALUE=800`

## Webhook

Square の環境は Sandbox と Production のみ。Sandbox 購読の通知先はステージング、Production 購読の通知先は本番。ローカルでは受信しない（申込とパック購入は API で確認する）。

- [x] Sandbox Webhook 購読を作成済み
- [x] イベント: `invoice.payment_made`, `subscription.updated`, `payment.updated` にチェック
- [ ] Sandbox 購読 URL が `https://dev.api.meap-app.com/square/webhook`（`example.com` や ngrok にはしない）
- [ ] Railway ステージングの `SQUARE_WEBHOOK_SIGNATURE_KEY` がその購読の Signature key
- [ ] Railway ステージングの `SQUARE_WEBHOOK_NOTIFICATION_URL` が購読 URL と完全一致（末尾スラッシュ含む）
- [x] ローカル `server/.env` の `SQUARE_WEBHOOK_SIGNATURE_KEY` と `SQUARE_WEBHOOK_NOTIFICATION_URL` は空
- [ ] 本番: Production 購読 URL が `https://api.meap-app.com/square/webhook`

## 環境変数（サーバー）

- [x] `SQUARE_APPLICATION_ID`
- [x] `SQUARE_ACCESS_TOKEN`
- [x] `SQUARE_LOCATION_ID`
- [x] `SQUARE_PLAN_VARIATION_STANDARD`
- [x] 上記プラン・パック金額の各変数
- [x] ローカルの `SQUARE_WEBHOOK_*` は空（Webhook 用の値は Railway のみ。上記 Webhook 節）
- [ ] ステージングは Sandbox キー、本番は Production キー（混在なし）

## 環境変数（フロント）

- [x] `NEXT_PUBLIC_SQUARE_APPLICATION_ID`
- [x] `NEXT_PUBLIC_SQUARE_LOCATION_ID`
- [x] `NEXT_PUBLIC_SQUARE_SCRIPT_URL`（Sandbox / 本番で正しい square.js URL）
- [x] `NEXT_PUBLIC_BACKEND_URL` が対象 API を向いている

## 動作確認

- [ ] （任意）API Explorer / curl で Customer → Card → Subscription が 1 件成功
- [ ] ステージング: Sandbox 購読の配信が **HTTP 200**（403 の場合は URL / Signature key を再確認）
- [ ] [Square 課金 E2E 手順](Square_課金_E2E_手順.md) の最低 1 シナリオ（例: パック購入）が通る。Webhook の到達確認はステージング
