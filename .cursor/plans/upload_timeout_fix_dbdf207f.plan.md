---
name: upload timeout fix
overview: 画像アップロードと AI 解析のタイムアウトを30秒から120秒に延ばし、それが実効となるようサーバー側（PHP 実行時間上限・nginx のタイムアウトとボディ上限）も揃える。あわせてローカルの Laravel 起動が毎リクエスト3〜8秒かかる原因（CLI で OPcache 無効）を恒久設定として解消する。
todos:
  - id: client-constants
    content: client/src/constants/api.ts に UPLOAD_TIMEOUT_MS と AI_TIMEOUT_MS（各120秒）を追加する
    status: pending
  - id: client-apply
    content: useImageApi の upload-bulk を UPLOAD_TIMEOUT_MS に、useRecipeAiApi の parse-img / parse-url を AI_TIMEOUT_MS に差し替える
    status: pending
  - id: server-php-exec
    content: docker/production/php/zz-production.ini に max_execution_time = 120 を明示する
    status: pending
  - id: server-nginx
    content: docker/nginx/conf.d/laravel.conf の client_max_body_size を 100M にし、proxy_read_timeout / proxy_send_timeout 120s を追加する
    status: pending
  - id: opcache-ini
    content: docker/8.3/php.ini に OPcache 設定（enable_cli=1, revalidate_freq=10 ほか）を追記する
    status: pending
  - id: opcache-mount
    content: docker-compose.yml の laravel.test に php.ini のバインドマウントを追加し、再ビルド不要にする
    status: pending
  - id: apply-verify
    content: 一時的な 98-perf.ini を削除してコンテナを再作成し、/up の応答速度・ini 値・画像付きレシピ更新・nginx ログを検証する
    status: pending
isProject: false
---

_Fix bulk image upload timeouts_

# 画像アップロードのタイムアウト対応と開発環境の高速化

## 背景（実測で確認した事実）

- nginx アクセスログで、失敗した `POST /images/upload-bulk` は `499`（クライアント切断）。axios の30秒 abort と時刻が一致。前後の同一アップロードは `200` で 8〜11秒。
- 原因はコードのバグではなく所要時間。ローカルは `php artisan serve`（PHP CLI）で OPcache が無効（`opcache.enable_cli=0`）のため、毎リクエストで数千ファイルを stat・再コンパイルする。Docker Desktop の Windows バインドマウントが遅く、コンテナ内で vendor 配下の PHP 15,101 件を数えるだけで23秒。結果として `/up` ですら 3〜8秒。
- CLI OPcache を有効化した検証で `/up` は 3〜8秒 → 0.15〜0.31秒（アイドル後の初回のみ 2〜5秒）。

## 1. クライアント: 用途別タイムアウトの追加

[client/src/constants/api.ts](client/src/constants/api.ts) の `TIMEOUT_MS = 30 * 1000` は据え置き、長時間かかる用途向けに2つ追加する。

```ts
/** 画像アップロード用。モバイル回線での大きめの multipart 送信を許容する */
export const UPLOAD_TIMEOUT_MS = 120 * 1000;

/** AI 解析用。OCR → 構造化の2段処理を許容する */
export const AI_TIMEOUT_MS = 120 * 1000;
```

差し替え箇所は3つ。`@/constants` からの named import に追加するだけで、インポート順のルールに影響しない。

- [client/src/models/image/hooks/useImageApi.ts](client/src/models/image/hooks/useImageApi.ts) の `POST /images/upload-bulk`（57行目）を `UPLOAD_TIMEOUT_MS` に。これで `useRecipeApi` のサムネイル・手順画像、`useUserApi` のアバターがすべて対象になる。
- [client/src/models/recipe/hooks/useRecipeAiApi.ts](client/src/models/recipe/hooks/useRecipeAiApi.ts) の `parse-img`（51行目）と `parse-url`（108行目）を `AI_TIMEOUT_MS` に。

`useRecipeApi` の `PUT /recipes/{id}` など画像を含まない API は30秒のまま維持する。

## 2. サーバー: 120秒が実効になるよう上限を揃える

クライアントだけ延ばしても途中の層で切られると意味がないため、3箇所を直す。

- [server/docker/production/php/zz-production.ini](server/docker/production/php/zz-production.ini): `max_execution_time` が未指定で PHP 既定の30秒になっている。`max_execution_time = 120` を明示する。本番の nginx は `fastcgi_read_timeout 300` なので追加対応は不要。
- [server/docker/nginx/conf.d/laravel.conf](server/docker/nginx/conf.d/laravel.conf): `client_max_body_size` を `10M` → `100M`（本番と同値）。手順画像を1リクエストにまとめる設計＋1枚10MBまで許容のため、ローカルだけ413で落ちる状態を解消する。
- 同ファイルの `location /` に `proxy_read_timeout 120s;` と `proxy_send_timeout 120s;` を追加する。nginx の既定は60秒で、これが無いとローカルでは120秒待たずに504になる。

## 3. ローカル: OPcache 設定の恒久化

[server/docker/8.3/php.ini](server/docker/8.3/php.ini) に追記する。

```ini
[opcache]
opcache.enable = 1
opcache.enable_cli = 1
opcache.validate_timestamps = 1
opcache.revalidate_freq = 10
opcache.memory_consumption = 512
opcache.interned_strings_buffer = 32
opcache.max_accelerated_files = 30000
```

このファイルは Dockerfile で `/etc/php/8.3/cli/conf.d/99-sail.ini` に COPY されているため、そのままだとイメージ再ビルドが必要になる。再ビルドを避けるため [server/docker-compose.yml](server/docker-compose.yml) の `laravel.test` の volumes に1行足し、以後は再起動だけで反映されるようにする。

```yaml
volumes:
  - ".:/var/www/html"
  - "./docker/8.3/php.ini:/etc/php/8.3/cli/conf.d/99-sail.ini"
```

トレードオフとして、PHP の編集が反映されるまで最大10秒かかる。また `opcache.enable_cli = 1` は `artisan` や `phpunit` などの単発コマンドにも効くが、単発プロセスではキャッシュが持続しないため速度は概ね変わらない。

## 4. 適用と検証

検証時にコンテナ内へ一時的に置いた `/etc/php/8.3/cli/conf.d/98-perf.ini` を削除し、`docker compose up -d laravel.test nginx` でコンテナを作り直す（volumes を追加したため再作成が必要で、これにより一時ファイルも消える）。

その後の確認内容:

- コンテナ内から `/up` を連続実行し、ウォーム時が 1秒未満であること
- `php -r` で `opcache.enable_cli` と `max_execution_time` が意図した値であること
- ブラウザでレシピを更新し、手順画像を複数枚含めても保存が完了すること
- nginx アクセスログで `POST /images/upload-bulk` が `200` で終わり、`499` や `413` が出ていないこと

## 対応しない項目（今回の決定に含まれないもの）

- WSL2 への移行（バインドマウントの遅さ自体の根本解決）
- `bulkUploadImage` が `handleApiError` 後に再 throw し、呼び出し側でも処理されるためスナックバーが2回出る問題
- `app/Traits/LoggingTrait.php` の24・44・116行目で出ている PHP deprecation 警告
