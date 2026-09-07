---
name: swagger-response
description: サーバーサイド Swagger レスポンス定義（*Responses.php）の作成・更新ルール。Swagger の @OA\Response や @OA\Schema を追加・編集するとき、Responses ファイルを新規作成するときに使用する。
---

# Swagger レスポンス定義規約（Schema 分離パターン）

## ファイル構成

`server/app/Swagger/` 配下のファイルは役割ごとに分割する。

| サフィックス | 役割 | 例 |
|---|---|---|
| `*Schemas.php` | データ構造（エンティティ・値オブジェクト） | `RecipeSchemas.php` |
| `*Responses.php` | レスポンスエンベロープ定義 + Response ref | `RecipeResponses.php` |
| `*Requests.php` | リクエストボディ定義 | `RecipeRequests.php` |
| `*Parameters.php` | パス・クエリパラメータ | `RecipeParameters.php` |

## 必須パターン: Schema 分離

`@OA\Response` 内にプロパティをインライン記述しない。必ず以下の 3 層構造にする。

### 1. データ Schema — レスポンスボディの `data` 部分

```php
@OA\Schema(
    schema="BillingStatus",
    required={"plan", "isSubscribed"},
    @OA\Property(property="plan", type="string", example="free"),
    @OA\Property(property="isSubscribed", type="boolean", example=false)
)
```

### 2. レスポンス Schema — `success` / `message` / `data` のエンベロープ

`data` は `ref` でデータ Schema を参照する。

```php
@OA\Schema(
    schema="BillingStatusResponse",
    required={"success", "message", "data"},
    @OA\Property(property="success", type="boolean", example=true),
    @OA\Property(property="message", type="string", example="課金状態を取得しました。"),
    @OA\Property(property="data", ref="#/components/schemas/BillingStatus")
)
```

### 3. Response 定義 — `@OA\JsonContent` は `ref` のみ

```php
@OA\Response(
    response="BillingStatusSuccess",
    description="課金状態を取得しました。",
    @OA\JsonContent(ref="#/components/schemas/BillingStatusResponse")
)
```

## 命名規則

| 層 | 命名パターン | 例 |
|---|---|---|
| データ Schema | `{Domain}{Entity}` | `BillingStatus`, `BillingCheckoutData` |
| レスポンス Schema | `{Domain}{Entity}Response` | `BillingStatusResponse` |
| Response 定義 | `{Domain}{Action}Success` | `BillingStatusSuccess` |

## クライアント型との対応

サーバー側の Schema はクライアント側の TypeScript 型と 1:1 で対応させる。

| サーバー (Swagger) | クライアント (TypeScript) |
|---|---|
| データ Schema (`BillingStatus`) | データ型 (`IBillingStatus`) |
| レスポンス Schema (`BillingStatusResponse`) | レスポンス型 (`IGetBillingStatusResponse = IBaseApiResponseWithData<IBillingStatus>`) |

## `data` を持たないレスポンスの扱い

`data: null` や `data` なし（`BaseApiResponse` のみ）のレスポンスは、Schema 分離せず `BaseApiResponse` を直接 `ref` する。

```php
@OA\Response(
    response="RecipeUpdateSuccess",
    description="レシピを更新しました。",
    @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
)
```

## 一覧レスポンス（Index）の扱い

一覧系は `allOf` で `BaseApiIndexResponse` を拡張する。

```php
@OA\Schema(
    schema="RecipeIndexResponse",
    allOf={
        @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
        @OA\Schema(
            required={"data"},
            @OA\Property(
                property="data",
                type="array",
                @OA\Items(ref="#/components/schemas/RecipeListItem")
            )
        )
    }
)
```

## エラーレスポンス（oneOf）の扱い

複数のエラーパターンを表現する場合は `oneOf` をインラインで記述してよい（Schema 分離不要）。

## 変更後の検証

Swagger 定義を変更したら以下を実行する。

```bash
cd server
./vendor/bin/sail artisan l5-swagger:generate
```

`api-docs.json` の `components/schemas` に新規 Schema が正しく出力されていることを確認する。
