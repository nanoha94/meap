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

## 課金（PAY.JP）

- **アカウント設定（Phase 0）:** [docs/PAY.JP_アカウント設定_手順.md](docs/PAY.JP_アカウント設定_手順.md) / [チェックリスト](docs/PAY.JP_Phase0_チェックリスト.md)
- **Webhook:** [docs/PAY.JP_課金_Webhook_手順.md](docs/PAY.JP_課金_Webhook_手順.md)
  - **ローカル:** 課金テスト中は `payjp-cli listen --forward-to http://localhost:8001/payjp/webhook` を起動
  - **本番（Railway）:** `payjp-cli` は**不要**。PAY.JP 管理画面で Webhook URL を登録
- **ブラウザ E2E:** [docs/PAY.JP_課金_E2E_手順.md](docs/PAY.JP_課金_E2E_手順.md)

## Railway 本番

- 本番 Docker デプロイでは **Start Command を設定しない**（`ENTRYPOINT` の `docker-entrypoint.sh` に任せる）。詳細は [docs/Railway_本番マイグレーション手順.md](docs/Railway_本番マイグレーション手順.md)。

### CORS

API の CORS 許可オリジンは `server/config/cors.php` で設定しています。

- **本番・開発共通:** `FRONTEND_URL` のみが許可されます。Railway の API 環境変数（本番）や `server/.env`（ローカル開発）にフロントの URL を設定してください。

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
