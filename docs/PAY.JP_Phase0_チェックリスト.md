# PAY.JP Phase 0 チェックリスト

コード実装前〜初回 E2E 前に、PAY.JP 管理画面と環境変数が揃っているか確認します。詳細は [PAY.JP アカウント設定手順](PAY.JP_アカウント設定_手順.md) を参照してください。

## アカウント・キー

- [x] PAY.JP アカウント作成済み
- [x] テストモードの `pk_test_...` / `sk_test_...` を取得済み
- [x] （本番公開前）本番利用申請を提出済み（必要書類・特商法 URL 等）
- [ ] （本番公開時）ライブモードの `pk_live_...` / `sk_live_...` を取得済み

## プラン・料金

- [x] スタンダード用サブスクプランを 1 件作成（例: 580 円/月、`pln_standard`）
- [x] `PAYJP_PLAN_SUBSCRIPTION_STANDARD` が作成したプラン ID と一致
- [x] `PAYJP_AMOUNT_SUBSCRIPTION_STANDARD=580`
- [x] `PAYJP_PRICE_PACK_LIGHT=400` / `PAYJP_PRICE_PACK_VALUE=800`

## Webhook

- [x] 管理画面のアカウント設定から `PAYJP_WEBHOOK_TOKEN`（`whook_...`）を控えた
- [x] ステージング: test モードで Webhook URL を登録（例: `https://dev.api.meap-app.com/payjp/webhook`）
- [x] 本番: live モードで Webhook URL を登録（例: `https://api.meap-app.com/payjp/webhook`）
- [x] ローカル開発用に [payjp-cli](https://docs.pay.jp/v2/guide/developers/payjp-cli) をインストール済み

## 環境変数（サーバー）

- [x] `PAYJP_SECRET_KEY`
- [x] `PAYJP_PUBLIC_KEY`（必要に応じて）
- [x] `PAYJP_WEBHOOK_TOKEN`
- [x] 上記プラン・パック金額の各変数
- [x] ステージングは test キー、本番は live キー（混在なし）

## 環境変数（フロント）

- [x] `NEXT_PUBLIC_PAYJP_PUBLIC_KEY`（test / live を環境に合わせる）
- [x] `NEXT_PUBLIC_BACKEND_URL` が対象 API を向いている

## 動作確認

- [ ] ローカル: `payjp-cli listen --forward-to http://localhost:8001/payjp/webhook` で転送成功
- [ ] [PAY.JP 課金 E2E 手順](PAY.JP_課金_E2E_手順.md) の最低 1 シナリオ（例: パック購入）が通る
