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

## Railway 本番マイグレーション

本番環境でのマイグレーションは、必ず次のコマンドを使用してください。

```bash
php artisan migrate --force --no-interaction
```

- 本番では `--force` が必須
- `migrate:reset` / `migrate:refresh` / `migrate:fresh` は実行しない
- `--seed` は原則実行しない（必要時は対象 Seeder を限定）

詳細手順とトラブルシュートは以下を参照:

- [docs/Railway_本番マイグレーション手順.md](docs/Railway_本番マイグレーション手順.md)
