# meap

## 環境構築
local-ssl-proxyのインストール
```
npm install -g local-ssl-proxy
```
mkcertのインストール([chocolatey](https://chocolatey.org/install)を使用する場合)
```
choco install mkcert
```
証明書のインストール
```
mkcert -install
```
証明書を作成するディレクトリを用意して移動
```
mkdir certificates
cd certificates
```
localhost用の鍵と証明書を作成
```
mkcert localhost
```

## 環境立ち上げ
### フロントエンド
```
cd client
npm run dev
npx local-ssl-proxy --key ..\certificates\localhost-key.pem --cert ..\certificates\localhost.pem  --source 3000 --target 3001 
```

### バックエンド
```
cd server
./vendor/bin/sail up -d
```

## Stripe 課金（Webhook）

サブスク・パック購入の反映には Webhook が必要です。

- **ローカル:** 課金テスト中は `stripe listen --forward-to http://localhost:8001/stripe/webhook` を起動
- **本番（Railway）:** `stripe listen` は**不要**。Stripe Dashboard で Webhook エンドポイントを登録する

詳細は [docs/Stripe_課金_Webhook_手順.md](docs/Stripe_課金_Webhook_手順.md) を参照。

## Railway 本番

- 本番 Docker デプロイでは **Start Command を設定しない**（`ENTRYPOINT` の `docker-entrypoint.sh` に任せる）。詳細は [docs/Railway_本番マイグレーション手順.md](docs/Railway_本番マイグレーション手順.md)。

### マイグレーション

本番環境でのマイグレーションは、必ず次のコマンドを使用してください。

```bash
php artisan migrate --force --no-interaction
```

- 本番では `--force` が必須
- `migrate:reset` / `migrate:refresh` / `migrate:fresh` は実行しない
- `--seed` は原則実行しない（必要時は対象 Seeder を限定）

詳細手順とトラブルシュートは以下を参照:

- [docs/Railway_本番マイグレーション手順.md](docs/Railway_本番マイグレーション手順.md)
