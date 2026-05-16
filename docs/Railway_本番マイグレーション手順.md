# Railway 本番マイグレーション手順（Laravel）

この手順は、`meap` の Railway 環境で実際に実行して成功したフローをまとめたものです。

## 前提

- 対象サービス: `meap`（Laravel）
- 環境: `develop`（必要に応じて読み替え）
- 実行場所: Railway コンテナ内（`railway ssh`）

## 重要な設定方針

### デプロイ（Start Command）

Railway サービス設定の **Start Command は空のまま**にしてください。

本番イメージは `docker/production/Dockerfile` の `ENTRYPOINT`（`docker-entrypoint.sh`）で次を行います。

- `PORT` を nginx 設定に反映
- `php artisan config:cache` / `route:cache`
- supervisord（nginx + php-fpm）起動

ダッシュボードや `railway.json` で Start Command（例: `supervisord ...`）を指定すると、**ENTRYPOINT が実行されず** nginx 設定が生成されないため、ヘルスチェックが `service unavailable` になります。

### データベース

- `DB_CONNECTION=pgsql`
- private 接続を使う場合:
  - `DB_HOST=postgres.railway.internal`（または `RAILWAY_PRIVATE_DOMAIN`）
  - `DB_PORT=5432`
- `RAILWAY_TCP_PROXY_PORT` は public 接続向け。private host と混在させない
- 本番で `migrate:reset` / `migrate:fresh` は実行しない

## 1. SSH 接続

### 鍵作成（未作成の場合）

```powershell
ssh-keygen -t ed25519 -C "railway-migrate" -f "$env:USERPROFILE\.ssh\railway_ed25519"
```

### 接続

```powershell
railway ssh -i C:\Users\nanoha\.ssh\railway_ed25519
```

接続成功時の例:

```bash
root@xxxxxxxxxxxx:/var/www/html#
```

## 2. 接続情報の確認

SSH 内で以下を実行:

```bash
printenv | grep -E '^(DB_CONNECTION|DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_URL|DATABASE_URL)='
```

確認ポイント:

- `DB_CONNECTION=pgsql`
- `DB_HOST=postgres.railway.internal`
- `DB_PORT=5432`（整数）

## 3. マイグレーション実行

```bash
php artisan config:clear
php artisan migrate:status --no-interaction
php artisan migrate --force --no-interaction
php artisan migrate:status --no-interaction
```

初回は `Migration table not found.` が出ることがあるが、`migrate --force` 実行で作成されるため問題ない。

### 本番運用の原則

- 本番では毎回 `php artisan migrate --force --no-interaction` を使う
- `--force` は production 環境での確認プロンプト回避に必須
- テーブル追加・カラム追加など通常のスキーマ変更でも同様
- `migrate:reset` / `migrate:refresh` / `migrate:fresh` は本番で実行しない
- `--seed` は原則使わない（意図しないデータ投入を防ぐため）
- Seed が必要な場合は、対象 Seeder を限定して個別実行する
  - 例: `php artisan db:seed --class=ColorSeeder --force`

## 4. 完了条件

- `migrate --force --no-interaction` がエラーなく完了
- 最後の `migrate:status` で対象 migration がすべて `Ran`

## トラブルシュート

### `Permission denied (publickey)`

- `railway ssh -i <秘密鍵パス>` で秘密鍵を明示する
- `railway whoami` で想定アカウントか確認
- `railway ssh keys` で登録鍵を確認

### `could not find driver`

- ローカルで `railway run` する場合、ローカル PHP 側に `pdo_pgsql` が必要
- `php -m | findstr /I pgsql` で `pdo_pgsql` / `pgsql` を確認

### `invalid integer value "...internal" for connection option "port"`

- `DB_PORT` にホスト文字列が入っている
- `DB_PORT=5432` に修正する

### `server closed the connection unexpectedly`

- private host と public port の混在が主因になりやすい
- `DB_HOST=postgres.railway.internal` と `DB_PORT=5432` の組み合わせを使用する

### ルート URL が「Welcome to nginx!」のまま

`https://dev.api.meap.blog/` などで Laravel ではなく **nginx の初期ページ** が出る場合、Laravel 用 nginx 設定が有効になっていない。

Dashboard の設定（Root Directory=`server`、Dockerfile=`docker/production/Dockerfile`、Start Command 空）が合っていても、次を確認する。

1. **デプロイログ**に `[meap] docker-entrypoint:` が出ているか（出ない＝ENTRYPOINT 未実行または別イメージ）
2. **ビルドログ**が Dockerfile ビルドか（Nixpacks のみになっていないか）
3. **カスタムドメイン**が正しい Railway サービスを向いているか
4. コード変更後に **Redeploy** したか

`railway ssh` 内:

```bash
head -5 /etc/nginx/conf.d/default.conf
# → root /var/www/html/public; であること

tr '\0' ' ' < /proc/1/cmdline; echo
# → docker-entrypoint.sh または supervisord
```

再デプロイ後（PowerShell）:

```powershell
curl.exe -s -o NUL -w "%{http_code}" https://dev.api.meap.blog/up
```

`200` になれば API 側は復旧。

### SSH で localhost は 200 だが、カスタムドメインだけ 404 / Welcome to nginx

コンテナ内の Laravel・nginx は正常で、**外からのルーティングだけが別先を向いている**状態。

1. Railway ダッシュボードの **Settings → Networking → Public URL**（`*.up.railway.app`）で確認:

```powershell
curl.exe -s -o NUL -w "%{http_code}" https://<Public-URL>/up
```

Public URL が `200` で `dev.api.meap.blog` だけ `404` なら、**カスタムドメインの紐付け先サービス**を見直す。

2. Cloudflare 等を使っている場合、DNS の CNAME 先が正しい Railway サービスか、プロキシキャッシュを疑う。

3. SSH 内で Host ヘッダ付き確認:

```bash
curl -s -o /dev/null -w "%{http_code}\n" -H "Host: dev.api.meap.blog" "http://127.0.0.1:${PORT:-8080}/up"
```

### ログイン画面の 404 / CSRF エラー

API の `/up` が `200` になってから、Vercel の `NEXT_PUBLIC_BACKEND_URL=https://dev.api.meap.blog`（末尾スラッシュなし）を確認し再デプロイする。Railway では `APP_URL` / `FRONTEND_URL` / `SANCTUM_STATEFUL_DOMAINS` / `SESSION_SECURE_COOKIE=true` を設定する。
