---
name: Stripe課金基盤連携
overview: 1st リリース（AI画像読み込み機能）と同時に提供する Stripe 課金基盤の実装計画。サブスクリプション管理、Webhook によるリセット連携、追加パック購入、およびクライアント側の料金プランUIを含む。
todos:
  - id: cashier-setup
    content: Laravel Cashier 導入 + groups テーブルに Stripe 関連カラム追加マイグレーション + Billable トレイト追加
    status: pending
  - id: stripe-products
    content: Stripe ダッシュボードで商品・価格を作成、.env に ID を設定
    status: pending
  - id: billing-endpoints
    content: "API エンドポイント作成: subscribe / portal / packs / status"
    status: pending
  - id: webhook-handler
    content: "Webhook ハンドラー作成: invoice.paid / subscription.updated / subscription.deleted / checkout.session.completed"
    status: pending
  - id: reset-path-fix
    content: resetUsageIfNeeded をフリープラン専用に修正（有料は renewBillingPeriod のみ）
    status: pending
  - id: bonus-pack
    content: "追加パック実装: ai_bonus_count カラム追加、assertWithinLimits 修正、購入制限"
    status: pending
  - id: client-billing-ui
    content: "クライアント: 料金プランページ、アップグレード/追加パック購入ボタン、Checkout コールバック"
    status: pending
  - id: client-usage-display
    content: "クライアント: 利用状況表示（残回数、上限到達時の追加パック導線）"
    status: pending
  - id: tests
    content: "サーバーテスト: Webhook / リセット経路 / 追加パック + Stripe CLI でローカル手動テスト"
    status: pending
isProject: false
---

# Stripe 課金基盤連携

## 実行タイミング

**1st リリース（AI画像読み込み機能）と同時に提供する。** AI読み込みプランの残タスク（クライアント UI、テスト、LP更新）と並行して進める。

## 前提: 既存の実装状況

以下は AI 読み込みプランで実装済み:

- `groups` テーブルに `plan` / `ai_usage_count` / `ai_usage_reset_at` カラム追加済み
- [server/app/Enums/GroupPlan.php](server/app/Enums/GroupPlan.php): `FREE` / `STANDARD` / `PRO` / `PRO_PLUS` 定義済み
- [server/app/Services/AiUsageService.php](server/app/Services/AiUsageService.php): `consumeUsage` / `refundUsage` / `getUsageStatus` / `renewBillingPeriod` 実装済み
- [server/config/ai.php](server/config/ai.php): プランごとの月次上限設定済み

## 1st リリースで提供するプラン

- **フリー**: 月3回まで（課金不要、既存の仕組みで動作）
- **スタンダード**: 月額 480円 / 月30回まで
- **追加パック**: 10回 200円 / 30回 500円（都度購入）

※ プロ / プロ+ は 3rd / 4th リリースで追加

---

## サーバー側

### 1. Laravel Cashier 導入

- `composer require laravel/cashier` で導入
- `groups` テーブルに Cashier 必須カラムを追加するマイグレーション作成
  - `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`
  - 課金主体はユーザーではなく **Group（世帯）単位**
- [server/app/Models/Group.php](server/app/Models/Group.php) に `Billable` トレイトを追加

### 2. Stripe 商品・価格の設定

Stripe ダッシュボード or Stripe CLI で作成し、ID を `.env` に記載:

```env
STRIPE_KEY=pk_...
STRIPE_SECRET=sk_...
STRIPE_WEBHOOK_SECRET=whsec_...

# サブスクリプション
STRIPE_PRICE_STANDARD=price_xxx

# 追加パック（one-time）
STRIPE_PRICE_PACK_10=price_yyy
STRIPE_PRICE_PACK_30=price_zzz
```

### 3. API エンドポイント

| メソッド | パス                            | 用途                                                      |
| -------- | ------------------------------- | --------------------------------------------------------- |
| POST     | `/api/billing/subscribe`        | サブスクリプション開始（Checkout Session 作成）           |
| POST     | `/api/billing/portal`           | Stripe Customer Portal セッション作成（プラン変更・解約） |
| POST     | `/api/billing/packs/{packType}` | 追加パック購入（Checkout Session 作成）                   |
| GET      | `/api/billing/status`           | 現在のプラン・サブスク状態取得                            |

- Stripe Checkout を使用し、カード情報はサーバーに一切持たない
- Customer Portal でプラン変更・解約をユーザーに委譲

### 4. Webhook ハンドラー

`POST /stripe/webhook` で受信。Laravel Cashier のミドルウェア (`VerifyWebhookSignature`) を使用。

| イベント                        | 処理                                                                           |
| ------------------------------- | ------------------------------------------------------------------------------ |
| `invoice.paid`                  | `Group.plan` を更新、`AiUsageService::renewBillingPeriod()` で利用回数リセット |
| `customer.subscription.updated` | プラン変更時に `Group.plan` を更新                                             |
| `customer.subscription.deleted` | `Group.plan` を `FREE` に戻す                                                  |
| `checkout.session.completed`    | 追加パック購入の場合、`ai_usage_count` を購入回数分だけ減算（上限拡張）        |

### 5. リセット経路の競合解消（重要）

現在の `AiUsageService` には AI 利用回数をリセットする経路が2つある。有料ユーザーに対して両方が動くと二重リセットが発生する。

**問題:**

- `resetUsageIfNeeded`（アプリの `addMonth()` で自前計算）と `renewBillingPeriod`（Stripe の `current_period_end`）の2経路が競合
- 有料ユーザーで `ai_usage_reset_at` を過ぎた後、Webhook 到着前にユーザーが AI を使うと `resetUsageIfNeeded` が先に発火
- その後 Webhook で `renewBillingPeriod` が発火し、二重リセットでカウントが消える
- `addMonth()` と Stripe の `current_period_end` で日付がずれ、以降の周期も不整合が蓄積する

**解決:**

[server/app/Services/AiUsageService.php](server/app/Services/AiUsageService.php) の `resetUsageIfNeeded` を修正し、有料プランはスキップする:

```php
public function resetUsageIfNeeded(Group $group): void
{
    if ($this->getPlan($group) !== GroupPlan::FREE) {
        return;
    }
    // ... 以下はフリープラン用のリセット処理（変更なし）
}
```

- 有料ユーザーのリセットは Stripe Webhook（`renewBillingPeriod`）を唯一の source of truth とする
- Webhook 遅延時は有料ユーザーが一時的に上限到達のまま使えないが、「使えすぎる」より安全

### 6. 追加パックの実装

- `groups` テーブルに `ai_bonus_count`（追加パック残数）カラムを追加
- `AiUsageService::assertWithinLimits` を修正: `ai_usage_count >= plan.monthlyLimit() + ai_bonus_count` で判定
- 追加パック購入時に `ai_bonus_count` を加算
- `renewBillingPeriod` で `ai_bonus_count` も 0 にリセット（翌月に繰り越さない）
- 月3パックまでの購入制限

---

## クライアント側

### 7. 料金プランページ / コンポーネント

- 料金プラン比較表コンポーネント（フリー vs スタンダード）
- 「アップグレード」ボタン → Stripe Checkout にリダイレクト
- 「追加パック購入」ボタン → Stripe Checkout にリダイレクト
- 購入完了後のコールバックページ（`/billing/success`, `/billing/cancel`）

### 8. 利用状況の表示

- 既存の `GET /api/ai/usage` のレスポンスにプラン情報を追加表示
- 「残り N 回 / 月30回」のような UI
- 上限到達時は追加パック購入への導線を表示

---

## テスト

### サーバー

- Webhook ハンドラーのテスト（各イベントの処理を検証）
- `resetUsageIfNeeded` のフリープラン限定化テスト
- 追加パック購入・消費のテスト
- Checkout Session 作成のテスト

### 手動確認

- Stripe CLI (`stripe listen --forward-to`) でローカル Webhook テスト
- Stripe テストモードでのサブスクリプション開始・解約フロー確認
