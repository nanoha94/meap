# Square 課金・Webhook 手順

サブスクリプションの月次更新や解約をアプリ側に反映するには **Square Webhook の受信が必須** です。カード登録 API だけでは、周期更新後の AI 月間枠リセット（`invoice.payment_made` → `renewBillingPeriod`）や、期間終了後の解約反映（`subscription.updated`）は完了しません。

プランの初回申込と買い切りパックの購入は、その場の API（`BillingService`）が Square を呼び、DB も更新します。Webhook が空でもこの2つは完了します。

Webhook は `POST /square/webhook` に届き、`x-square-hmacsha256-signature` ヘッダーで正当性を検証します（`SquareWebhookController` → `BillingWebhookService`）。検証には **リクエスト生 body** と **`SQUARE_WEBHOOK_NOTIFICATION_URL`（Dashboard 購読 URL と完全一致）** を使います。どちらかが空、または URL が一致しない通知は **403** になります。

アプリ・Location・Plan Variation・Dashboard 上の Webhook 購読は先に [Square アカウント設定（Phase 0）](Square_アカウント設定_手順.md) を完了してください。

## 環境の対応

Square の環境は **Sandbox** と **Production** の2つです。アプリのローカル / ステージング / 本番との対応は次のとおりです。

| アプリ | Square | Webhook |
| ------ | ------ | ------- |
| ローカル | Sandbox（API 呼び出しのみ） | 受信しない。`SQUARE_WEBHOOK_SIGNATURE_KEY` と `SQUARE_WEBHOOK_NOTIFICATION_URL` は空でよい |
| ステージング | Sandbox | `https://dev.api.meap-app.com/square/webhook` |
| 本番 | Production | `https://api.meap-app.com/square/webhook` |

Sandbox の購読 URL は **ステージングに1件** 固定します。ローカル向けの購読は作りません。`dev.api.meap-app.com` は Railway のステージングを指すため、同じホストをローカルの受信先には使えません。プレースホルダ（`example.com` など）や ngrok の URL を Sandbox 購読に登録すると、通知はステージングに届きません。

ローカルで Sandbox 決済をしても、通知はステージングに届きます。ステージングの DB に同じ Square 顧客がいなければ、`BillingWebhookService` は何も更新せずに戻ります。

| 確認したいこと | 場所 |
| -------------- | ---- |
| プラン申込、買い切りパック購入 | ローカルでよい（API がその場で反映） |
| 月次更新、解約の反映、請求履歴の Webhook 保存 | ステージング（Sandbox 購読） |
| 本番の同じ経路 | 本番（Production 購読、実カード） |

---

## 環境変数

受信する環境の Railway Variables に設定します。雛形は [server/.env.example](../server/.env.example) の Square セクションを参照。ローカルの `server/.env` では Webhook の2変数は空のままにします。

| 変数 | 説明 |
| ---- | ---- |
| `SQUARE_ACCESS_TOKEN` | Access token（API 呼び出し用。ローカルとステージングは Sandbox、本番は Production） |
| `SQUARE_APPLICATION_ID` | Application ID |
| `SQUARE_LOCATION_ID` | Location ID |
| `SQUARE_PLAN_VARIATION_STANDARD` | スタンダード Plan Variation ID |
| `SQUARE_WEBHOOK_SIGNATURE_KEY` | その環境の Webhook 購読の Signature key（Railway のみ） |
| `SQUARE_WEBHOOK_NOTIFICATION_URL` | 購読に登録した URL と同一（末尾スラッシュ含め一致。Railway のみ） |
| `SQUARE_PRICE_PACK_LIGHT` / `SQUARE_PRICE_PACK_VALUE` | パック金額 |

フロント: `NEXT_PUBLIC_SQUARE_APPLICATION_ID` / `NEXT_PUBLIC_SQUARE_LOCATION_ID` / `NEXT_PUBLIC_SQUARE_SCRIPT_URL`

URL を変えると Signature key も変わります。購読 URL と `SQUARE_WEBHOOK_NOTIFICATION_URL` と Signature key は、同じ購読の値で揃えてください。

---

## ステージング（Sandbox 購読）

1. Developer Console → アプリ `meap` → **Sandbox** → **Webhooks** → **Subscriptions**
2. 購読 URL を次にする（既存が別 URL なら更新する）

```text
https://dev.api.meap-app.com/square/webhook
```

3. イベントは次にチェックする
4. 購読の **Signature key** を Railway ステージングの `SQUARE_WEBHOOK_SIGNATURE_KEY` に入れる
5. 上の URL を一字一句同じ文字列で `SQUARE_WEBHOOK_NOTIFICATION_URL` に入れる

meap が処理するイベント種別（`SquareWebhookController`）:

| イベント | 用途 |
| -------- | ---- |
| `invoice.payment_made` | サブスク請求成功 → プラン維持・`renewBillingPeriod`・請求履歴 |
| `subscription.updated` | ステータス同期（解約予定・CANCELED 等） |
| `payment.updated` | 都度課金の失敗ログ（パック残数は購入 API で加算済み） |

### 動作確認

ブラウザのシナリオは [Square 課金 E2E 手順](Square_課金_E2E_手順.md) を参照してください。Webhook 自体はステージングで確認します。

1. ステージングでカード登録・サブスク開始またはパック購入を行う
2. Console の Webhook **Logs** で `POST /square/webhook` が **200** であること
3. `/settings/billing` でプラン・利用回数・請求履歴が反映されていること

---

## 本番（Production 購読）

**Sandbox の購読とは別に**、Console 上部トグルを **Production** にして購読を作ります。

| 環境 | URL |
| ---- | --- |
| ステージング | `https://dev.api.meap-app.com/square/webhook` |
| 本番 | `https://api.meap-app.com/square/webhook` |

Railway 本番の `SQUARE_WEBHOOK_NOTIFICATION_URL` は、Production 購読に登録した URL と **完全一致** させてください。Access token・Location・Plan Variation も Production の値にします。

1. Console の Webhook ログで配信結果を確認する
2. 本番でサブスクまたはパック購入を発生させる（実カードの少額）
3. HTTP 200 とアプリ側のプラン・残数反映を確認する

---

## トラブルシュート

### 月次更新で枠がリセットされない / 解約が反映されない

申込直後のプラン変更とパック残数は API で反映されます。ここは **その後の Webhook** が届いていないときの確認です。

| 確認項目 | ステージング | 本番 |
| -------- | ------------ | ---- |
| Webhook 到達 | Sandbox 購読 URL が `https://dev.api.meap-app.com/square/webhook` | Production 購読 URL が `https://api.meap-app.com/square/webhook` |
| 署名 | `SQUARE_WEBHOOK_NOTIFICATION_URL` が購読 URL と完全一致 | 同上 |
| Signature key | その購読の Signature key と Railway の値が一致 | 同上 |
| モード | Sandbox の token・購読 | Production の token・購読 |
| イベント | `invoice.payment_made` / `subscription.updated` が購読に含まれる | 同上 |
| デプロイ | `SquareWebhookController` がデプロイ済み | 同上 |

ローカルの `.env` に Webhook 用の値を入れても、購読 URL がステージングのままならローカルには届きません。

### 403（署名検証失敗）

- `SQUARE_WEBHOOK_SIGNATURE_KEY` が **その購読** の Signature key か（URL を変えたあと古い key のままになっていないか）
- `SQUARE_WEBHOOK_NOTIFICATION_URL` に余計なスラッシュや `http` / `https` の差がないか
- リバースプロキシが body を書き換えていないか

### 使い分け（まとめ）

| | ローカル | ステージング | 本番 |
| --- | --- | --- | --- |
| Square | Sandbox | Sandbox | Production |
| Webhook | 受信しない | Sandbox 購読 → ステージング URL | Production 購読 → 本番 URL |
| `SQUARE_WEBHOOK_*` | 空 | Railway に購読と一致する値 | Railway に購読と一致する値 |
