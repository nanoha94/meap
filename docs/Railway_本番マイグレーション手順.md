# Railway 本番マイグレーション手順（Laravel）

この手順は、`meap` の Railway 環境で実際に実行して成功したフローをまとめたものです。

## 前提

- 対象サービス: `meap`（Laravel）
- 環境: `develop`（必要に応じて読み替え）
- 実行場所: Railway コンテナ内（`railway ssh`）

## 重要な設定方針

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
