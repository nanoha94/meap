# PAY.JP アカウント設定（Phase 0）

meap の課金（サブスクリプション・買い切りパック）を動かす前に、PAY.JP 管理画面と環境変数の準備を行います。

関連:

- [Phase 0 チェックリスト](PAY.JP_Phase0_チェックリスト.md)
- [Webhook 手順](PAY.JP_課金_Webhook_手順.md)
- [ブラウザ E2E 手順](PAY.JP_課金_E2E_手順.md)

---

## 1. アカウント作成・本番利用申請

1. [PAY.JP](https://pay.jp/) でアカウントを作成する
2. **テストモード**で API キーを取得する（公開鍵 `pk_test_...` / 秘密鍵 `sk_test_...`）
3. 本番公開前に **本番利用申請**を提出する（審査: Visa/Mastercard おおむね 3〜4 営業日、全ブランドは最大約 1 ヶ月）
   - 商材: ソフトウェアの販売・提供（SaaS の利用料金）
   - サービス URL: 本番フロント URL（例: `https://meap-app.com`）
   - 特商法ページ URL（例: `https://meap-app.com/legal/commercial`）
   - 利用規約・プライバシーポリシー URL

審査通過後、**ライブモード**の `pk_live_...` / `sk_live_...` を取得します。テストキーとライブキーは混在させないでください。

---

## 2. サブスクリプション用プランの作成

PAY.JP 管理画面または API で、スタンダードプラン用の **定期課金プラン**を 1 件作成します。

| 項目      | meap の値（例）                      |
| --------- | ------------------------------------ |
| プラン ID | `pln_standard`（任意。固定 ID 推奨） |
| 名前      | スタンダード                         |
| 金額      | 580 円（税込）                       |
| 間隔      | 月次                                 |

作成したプラン ID を `PAYJP_PLAN_SUBSCRIPTION_STANDARD` に設定します（`.env.example` では `pln_standard` となっている場合は、実際に作成した ID に合わせてください）。

**買い切りパック（ライト / バリュー）**は都度 `Charge` 課金のため、PAY.JP 側でのプラン作成は不要です。金額は `PAYJP_PRICE_PACK_LIGHT` / `PAYJP_PRICE_PACK_VALUE` で指定します。

---

## 3. Webhook Token の控え

管理画面 → **アカウント設定** に **Webhook Token**（`whook_...`）があります。これを `PAYJP_WEBHOOK_TOKEN` に設定し、`X-Payjp-Webhook-Token` ヘッダーの検証に使います。

Webhook **URL の登録**（ローカル転送・ステージング・本番）は [PAY.JP 課金・Webhook 手順](PAY.JP_課金_Webhook_手順.md) を参照してください。

---

## 4. 環境変数の設定

### サーバー（`server/.env` / Railway Variables）

[server/.env.example](../server/.env.example) の PAY.JP セクションをコピーし、値を埋めます。

| 変数                                 | 説明                                    |
| ------------------------------------ | --------------------------------------- |
| `PAYJP_SECRET_KEY`                   | 秘密鍵（`sk_test_...` / `sk_live_...`） |
| `PAYJP_PUBLIC_KEY`                   | 公開鍵（サーバー側で参照する場合）      |
| `PAYJP_WEBHOOK_TOKEN`                | Webhook Token（`whook_...`）            |
| `PAYJP_PLAN_SUBSCRIPTION_STANDARD`   | サブスク用プラン ID                     |
| `PAYJP_AMOUNT_SUBSCRIPTION_STANDARD` | サブスク月額（表示用・580）             |
| `PAYJP_PRICE_PACK_LIGHT`             | ライトパック金額（400）                 |
| `PAYJP_PRICE_PACK_VALUE`             | バリューパック金額（800）               |

秘密鍵はフロントエンドや公開リポジトリに含めないこと。

### フロント（`client/.env` / Vercel）

| 変数                           | 説明                                                                 |
| ------------------------------ | -------------------------------------------------------------------- |
| `NEXT_PUBLIC_PAYJP_PUBLIC_KEY` | 公開鍵（`pk_test_...` / `pk_live_...`）。payjp.js のトークン化に使用 |

---

## 5. モードの対応関係

| 環境                   | API キー                | Webhook                                                      |
| ---------------------- | ----------------------- | ------------------------------------------------------------ |
| ローカル・ステージング | `pk_test_` / `sk_test_` | ローカルは payjp-cli 転送、ステージングは Dashboard（test）  |
| 本番                   | `pk_live_` / `sk_live_` | Dashboard（live）で `https://api.meap-app.com/payjp/webhook` |

テストカードは **テストモードのみ** 使用可能です。ライブモードでテストカードを使うとエラーになります。

---

## 6. 設定後の確認

1. [PAY.JP Phase 0 チェックリスト](PAY.JP_Phase0_チェックリスト.md) をすべてチェック
2. ローカルで [Webhook 手順](PAY.JP_課金_Webhook_手順.md) に従い `payjp-cli listen` を起動
3. [PAY.JP 課金 E2E 手順](PAY.JP_課金_E2E_手順.md) でブラウザから 1 回通す
