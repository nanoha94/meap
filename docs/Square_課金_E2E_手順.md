# Square 課金 E2E 手順

ブラウザから課金を通し確認する手順です。リリース前の確認は **当日と翌日** で終わります。月額プランの 2 回目請求まで待つ必要はありません。

関連:

- [アカウント設定（Phase 0）](Square_アカウント設定_手順.md)
- [Webhook 手順](Square_課金_Webhook_手順.md)
- [買い切りパック返金手順](Square_買い切りパック返金_手順.md)
- [ステージングと本番公開の方針](ステージングと本番公開_方針.md)

画面はどれも **プラン管理** `/settings/billing` です。ユーザーはメール認証済みにしてください。未認証だと API が 409 になることがあります。

---

## どこで何を見るか

| 確認すること | 場所 | いつ |
| ------------ | ---- | ---- |
| カード登録・変更、プラン申込、パック購入、解約予約、解約取り消し、次回請求の表示 | ローカル | 当日 |
| Webhook が届き、サブスクの請求履歴がプラン名になる | ステージング | 当日（申込の直後） |
| 枠を使ったあと、次の請求で月間枠が満タンに戻る | ステージング（日次プラン） | 翌日 |
| 解約したあと、期間が終わるとフリーに戻る | ステージング（日次プラン） | 翌日 |
| 本番の初回申込または少額パック | 本番 | 公開前。実カード。月次は待たない |

ローカルは Webhook を受信しません。`SQUARE_WEBHOOK_SIGNATURE_KEY` と `SQUARE_WEBHOOK_NOTIFICATION_URL` は空のままで構いません。請求履歴の行は Square の決済一覧から出すため、ローカルの申込でも初回決済は出ます。表示名は `billing_histories` を参照し、未登録なら「その他のお支払い」です。サブスクのプラン名は Webhook だけが保存するため、**ローカルでは「その他のお支払い」**になります。パックの表示名は購入 API が保存します。

Sandbox の購読 URL はステージングに 1 件です。

```text
https://dev.api.meap-app.com/square/webhook
```

Signature key と `SQUARE_WEBHOOK_NOTIFICATION_URL`（上の URL と同一文字列）は Railway ステージングに入れます。`example.com` や ngrok の URL にはしません。

---

## テストカード（Sandbox）

[Square Sandbox テストカード](https://developer.squareup.com/docs/devtools/sandbox/payments) に従い、**将来の有効期限**と **任意の CVV** を入力します。

| 用途 | カード番号 |
| ---- | ---------- |
| Visa 成功 | `4111 1111 1111 1111` |
| Mastercard 成功 | `5105 1051 0510 5100` |

3DS の画面が出たら、Sandbox の指示に従って完了します。本番ではこのカードは使えません。

---

## 1. ローカル（当日）

ローカルの Square キーは Sandbox、`NEXT_PUBLIC_SQUARE_SCRIPT_URL` は `https://sandbox.web.squarecdn.com/v1/square.js` です。`SQUARE_PLAN_VARIATION_STANDARD` は月額 580 円の Variation ID のままで構いません。

1. `https://localhost:3000/settings/billing` を開く
2. **プランを変更** → スタンダードの **このプランを選択** → カード（4111…）→ **申し込む**
3. 次を確認する
   - 現在のプランが「スタンダード」
   - 支払い設定に下 4 桁と有効期限
   - 月間枠残りが `30 / 30 回` に近い値
   - 次回のお支払い予定に更新日と 580 円
   - 請求履歴に初回決済の行がある。内容は **その他のお支払い**（Webhook 未受信のためプラン名は付かない）
4. **買い切りパックを購入** → ライト → 購入
   - 買い切りパック残りが +10（バリューなら +30）
   - 請求履歴にパックの行が追加される
5. **支払い方法を変更** → Mastercard（5105…）→ **カードを更新**
   - 下 4 桁が変わる。プランはスタンダードのまま
6. **プランを変更** → フリーの **ダウングレード** → 確認ダイアログで **ダウングレード**
   - 「プラン変更予定」が出る。プラン表示はスタンダードのまま
   - 次回のお支払い予定は「次回請求はありません」
7. **プランを変更** → スタンダードの **変更を取り消す** → **取り消す**
   - プラン変更予定が消え、スタンダードのまま
   - 次回請求が再び表示される

ここまでがローカルで見られる範囲です。

---

## 2. ステージング（当日）

事前に Sandbox 購読 URL と Railway ステージングの Webhook 用環境変数を揃えます。イベントは `invoice.payment_made`、`subscription.updated`、`payment.updated` です。ステージングの Access token / Location / Plan Variation / square.js は Sandbox のまま、Variation は月額の ID です。

1. `https://dev.meap-app.com/settings/billing` で、ローカルとは別のユーザーとしてスタンダードに申し込む（カードは 4111…）
2. Developer Console → Sandbox → Webhooks → **View logs** で `POST /square/webhook` が **200** であることを確認する（403 は Signature key か URL の不一致）
3. 同じ画面で次を確認する
   - プランがスタンダード、月間枠が 30 回前後
   - **請求履歴のサブスク行の内容がプラン名**（「その他のお支払い」ではない。この表示名が出ていれば、月次更新と同じ `invoice.payment_made` の経路が動いている）
4. ライトパックを 1 件購入し、残りが +10、請求履歴にパックの行が増えることを確認する
5. 解約予約と取り消しは、ローカルと同じ操作で確認する（この 2 つは API で反映される）

初回申込の時点で Webhook の到達確認は終わります。月額のまま 1 か月後の 2 回目請求は待ちません。

---

## 3. ステージング（翌日・日次プラン）

Sandbox は月次の請求日を早送りできません。枠のリセットと、期間終了後にフリーへ戻る処理は、**Sandbox 専用の日次プラン**で翌日に見ます。月額 Variation は残したまま、確認用の Variation を別に作ります。

1. Sandbox の Catalog に `SUBSCRIPTION_PLAN_VARIATION` を 1 件追加する（cadence は `DAILY`、金額は少額、通貨は JPY、価格タイプは STATIC）。作り方は月額プランと同じです。手順は [アカウント設定](Square_アカウント設定_手順.md) のプラン作成を参照
2. その Variation ID を、Railway ステージングの `SQUARE_PLAN_VARIATION_STANDARD` に一時的に入れる。アプリが新しい値を読んでから次へ進む（再デプロイが必要な場合がある）
3. 画面の「580 円」は表示用の `SQUARE_AMOUNT_SUBSCRIPTION_STANDARD` です。実際に Sandbox が請求するのは日次 Variation の金額です

日次プランの確認は、**未契約のユーザーを 2 人**使うと翌日に両方終わります。すでに月額契約中のユーザーは、解約が期間末まで残っているあいだは新規申込できません。

### 3-A. 次の請求で枠が戻る

1. ユーザー A でスタンダードに申し込む
2. AI を数回使い、月間枠残りが 30 より減ったことを控える
3. 翌日、同じ画面を開く
   - 月間枠残りが `30 / 30 回` に戻っている
   - リセット日が新しい請求周期の終了日になっている
   - Webhook ログの `invoice.payment_made` が **200**
   - 請求履歴にプラン名のサブスク行がもう 1 件ある

### 3-B. 期間終了後にフリーへ戻る

1. ユーザー B でスタンダードに申し込む
2. すぐに **プランを変更** → フリーの **ダウングレード**
   - 当日は「プラン変更予定」で、プランはスタンダードのまま
3. 翌日、同じ画面を開く
   - 現在のプランが「フリー」
   - 月間枠がフリーの上限（3 回）になっている
   - Webhook ログの `subscription.updated` が **200**

同じユーザーで 3-A のあとに解約すると、フリーへ戻るのはその次の日になります。

確認が終わったら、Railway ステージングの `SQUARE_PLAN_VARIATION_STANDARD` を **月額 580 円の Variation ID** に戻します。

---

## 4. 本番（公開前）

Console 上部を **Production** にし、購読・キー・Location・Plan Variation を本番用にします。Webhook URL は次です。

```text
https://api.meap-app.com/square/webhook
```

`SQUARE_WEBHOOK_NOTIFICATION_URL` と Signature key は Railway 本番に、この購読の値を入れます。square.js は `https://web.squarecdn.com/v1/square.js` です。

実カードで次のどちらかを 1 件行い、Webhook ログが **200** であることを確認します。

- ライトパック（400 円）の購入。残り +10 と請求履歴
- またはスタンダード申込。プランがスタンダードになり、請求履歴の内容がプラン名になる

本番で 2 回目の月次請求と、期間終了後のフリー戻しは待ちません。その経路はステージングの日次プランで確認済みとします。不要な契約は解約し、必要なら Square ダッシュボードで返金します。

---

## うまくいかないとき

| 症状 | 確認 |
| ---- | ---- |
| 申込直後なのにプランがフリーのまま | Sandbox の Access token / Location / Plan Variation。初回のプラン反映は API であり、Webhook ではない |
| ローカルのサブスク行が「その他のお支払い」 | 想定どおり。プラン名はステージングの Webhook で確認する |
| ステージングでもサブスク行が「その他のお支払い」 / 月次更新や解約反映がされない | Sandbox 購読 URL、Railway の `SQUARE_WEBHOOK_NOTIFICATION_URL`、Signature key、Sandbox の token |
| Webhook 403 | 購読 URL と env の完全一致（末尾スラッシュ、`https`）。URL 変更後の古い Signature key |
| カードフォームが出ない | `NEXT_PUBLIC_SQUARE_*`、square.js の読み込み、Location ID |
| tokenize 失敗 | Sandbox の script URL、3DS のキャンセル、通貨 JPY |
| API 409 | メール未認証 |
| Sandbox カードが拒否 | Production のキーや `web.squarecdn.com` を使っていないか |
| 日次プランなのに翌日も枠が戻らない | Variation の cadence が `DAILY` か。ステージングの `SQUARE_PLAN_VARIATION_STANDARD` がその ID か。購読ログが 200 か |

Webhook の設定詳細は [Square 課金・Webhook 手順](Square_課金_Webhook_手順.md) を参照してください。
