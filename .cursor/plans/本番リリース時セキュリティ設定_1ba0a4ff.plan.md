---
name: デプロイ時セキュリティ設定
overview: Google Cloud Vision API キーのステージング・本番デプロイ時に必要なセキュリティ設定をまとめた TODO リスト。
todos:
  - id: separate-keys
    content: ステージング用・本番用・開発用で別々の API キーを発行
    status: pending
  - id: restrict-api
    content: 各キーの API の制限で Cloud Vision API のみに絞る
    status: pending
  - id: restrict-ip-staging
    content: ステージング用キーに Railway サーバーの IP 制限を追加
    status: pending
  - id: restrict-ip-prod
    content: 本番用キーに本番サーバーの IP 制限を追加
    status: pending
  - id: budget-alert
    content: 予算アラートを設定（月額 $1～$5）
    status: pending
  - id: railway-env
    content: Railway の環境変数に GOOGLE_CLOUD_VISION_API_KEY を設定
    status: pending
isProject: false
---

# デプロイ時: Google Cloud Vision API キーのセキュリティ設定

## 対象

Google Cloud Console で発行した `GOOGLE_CLOUD_VISION_API_KEY` に対する制限設定。
ステージング（Railway）・本番の両環境でデプロイ前に実施する。

## 1. 環境ごとに API キーを分ける

| 環境 | キー | アプリケーション制限 |
|------|------|---------------------|
| ローカル開発 | 開発用キー | 制限なし |
| ステージング（Railway） | ステージング用キー | Railway サーバー IP |
| 本番 | 本番用キー | 本番サーバー IP |

Google Cloud Console →「API とサービス」→「認証情報」→「+ 認証情報を作成」→「API キー」で環境ごとに発行する。

## 2. 各キーに共通の制限を設定

### API の制限（全キー共通）

- 該当の API キーを選択 →「API の制限」→「キーを制限」→ **「Cloud Vision API」のみ** にチェック

### アプリケーション制限（ステージング・本番キーのみ）

- 「アプリケーションの制限」→ **「IP アドレス」** を選択
- サーバーのグローバル固定 IP を入力
- 注意: 「ウェブサイト」ではない（API はバックエンドから呼ぶため Referer ベースの制限は無意味）

#### Railway の IP 確認方法

Railway のサーバー IP は固定ではない場合がある。Railway ダッシュボードまたはデプロイログで確認し、変動する場合は IP 制限の代わりに API の制限（Cloud Vision API のみ）で対応する。

## 3. 予算アラートの設定

- Google Cloud Console →「お支払い」→「予算とアラート」
- 月額の上限を設定（例: $1 または $5）
- 閾値（50%, 90%, 100%）でメール通知が届く
- 意図しない大量呼び出しによる課金を防止

## 4. 環境変数の設定

### Railway（ステージング）

Railway ダッシュボード → 該当サービス →「Variables」に追加:

```
AI_VISION_PROVIDER=google
GOOGLE_CLOUD_VISION_API_KEY=（ステージング用キー）
```

### 本番

```env
AI_VISION_PROVIDER=google
GOOGLE_CLOUD_VISION_API_KEY=（本番用キー）
```
