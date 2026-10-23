---
name: server-testing
description: サーバーサイドのテストケース一覧（TEST_SPECIFICATIONS.md）およびテストコード（*Test.php）の作成・更新ルール。テスト追加・修正、テストケース一覧の作成・更新時に使用する。
---

# サーバーサイド テスト規約

## 技術スタック

- **テストフレームワーク**: Pest PHP（Laravel）
- **テスト種別**
  - **Feature**（`server/tests/Feature/`）: HTTP 経由のコントローラ、DB を伴う Service / Provider / Console 等
  - **Unit**（`server/tests/Unit/`）: 外部 I/O をモックした Service（AI 系）、Helpers、Data 等
- **テストケース一覧**: `server/tests/docs/` 配下の Markdown（大分類ディレクトリ + 番号付き仕様ファイル）

`tests/Pest.php` は Feature 配下に `TestCase` を自動適用する。Unit で Laravel コンテナが必要なファイルは `uses(TestCase::class);` をファイル先頭で明示する（例: `QuantityTest.php`）。

## ディレクトリ構成

```
server/tests/
├── docs/                          # テストケース一覧（Markdown）
│   ├── 01_Traits/
│   ├── 02_Auth/
│   ├── 03_Api/
│   ├── 04_Services/
│   ├── 05_Helpers/
│   ├── 06_Providers/
│   ├── 07_Data/
│   └── 08_Console/
├── Feature/                       # Feature テストコード
│   ├── Traits/
│   ├── Auth/
│   ├── Api/
│   ├── Services/
│   ├── Providers/
│   └── Console/
├── Unit/                          # Unit テストコード
│   ├── Helpers/
│   ├── Data/
│   └── Services/Ai/               # AI 関連 Service（実 API なし）
├── sh/                            # カテゴリ別実行スクリプト（01〜05）
├── Pest.php
└── TestCase.php
```

### 仕様書とテストファイルの対応

| docs 大分類 | 典型のテスト配置 | 仕様ファイル名の `{Name}` 例 |
|-------------|------------------|------------------------------|
| `01_Traits` | `Feature/Traits/{Name}Test.php` | `ApiResponse` |
| `02_Auth` | `Feature/Auth/{Name}Test.php` | `AuthenticatedSessionController` |
| `03_Api` | `Feature/Api/{Name}Test.php` | `RecipeController` |
| `04_Services` | 多くは `Feature/Services/`。AI 系 3 件は `Unit/Services/Ai/` | `BillingService` / `OpenAiRecipeParser` |
| `05_Helpers` | `Unit/Helpers/{Name}Test.php` | `Quantity` |
| `06_Providers` | `Feature/Providers/{Name}Test.php` | `AppServiceProvider` |
| `07_Data` | `Unit/Data/{Name}Test.php` | `ParsedRecipe` |
| `08_Console` | `Feature/Console/{Name}Test.php` | `InvitationTokenCleanup` |

- 仕様ファイル名: `{番号}_{テスト対象クラス名}_TEST_SPECIFICATIONS.md`
- テストファイル名: 仕様の `{テスト対象クラス名}` + `Test.php`（例: `03_BillingService` → `BillingServiceTest.php`）
- 新規追加時は **同種の既存ファイルと同じ Feature / Unit の置き場** に合わせる。`tests/sh/` に未登録のクラス（`GroupPlanService` 等）は仕様書の「テスト実行方法」に `./vendor/bin/sail test ...` を記載する

## テストケース ID 体系

テストケース ID は **X-Y-Z** の形式とする。

| 要素 | 説明 | 例 |
|------|------|----|
| **X** | 大分類番号。`docs/` ディレクトリ名先頭番号（先頭ゼロは除く） | `04_Services` → 4 |
| **Y** | 中分類番号。仕様ファイル名の先頭番号 | `07_RecipeController` → 7 |
| **Z** | 仕様ファイル内の連番（1 始まり）。**一覧表・`test()` の並び順と一致**させる | 1, 2, 3, … |

**例**: `3-7-1` = `03_Api` / `07_RecipeController` / そのファイルの 1 番目のケース

### Z の付け方

- ケースを **途中に挿入** したら、挿入位置以降の Z を **1 つずつ繰り下げて振り直す**（仕様表と `*Test.php` の両方）
- ケースを **削除** したら、後続の Z を **詰めて欠番をなくす**
- 並び替え（正常系・異常系の整理など）したら、**新しい行順に合わせて Z を 1 から付け直す**
- **仕様とコード** の ID 列・`test('X-Y-Z: …')` の順序は常に **完全一致**

## テストケースの並び順

`TEST_SPECIFICATIONS.md` の行順・`*Test.php` 内の `test(...)` の並びは **同一** にする。グループ化の単位は次のとおり。

### A. HTTP（Controller）テスト — `02_Auth` / `03_Api`

**公開メソッド（ルート）ごとにブロック**を分ける。

1. メソッドごとにグループ化（`index` → `store` → …、ルート定義・コントローラの並びに合わせる）
2. 各メソッド内: **正常系をすべて先**、**異常系をすべて後**
3. 異常系の順（処理が拒否されるおおよその順）:
   1. 未認証（401）
   2. `FormRequest::rules()` 由来の 422（**項目順は `rules()` のキー定義順**）
   3. 認証済み・入力通過後の業務 4xx（グループ未所属等）
   4. サーバー・サービス起因（500 等）
4. 422 が複数あるときは 3 の **2.** ブロック内だけ `rules()` 順を守る

```php
// ===== index() メソッドのテストケース =====
```

### B. Service / Helper / Trait / Provider / Console 等

**public メソッド（または検証対象の static メソッド）ごとにブロック**を分ける。

1. クラス内の **public API の並び**（ソース上のメソッド定義順、または仕様書で採用しているドメイン順）にセクションを並べる
2. 各メソッド内: **正常系 → 異常系**
3. 異常系の順（HTTP ミドルウェアは無い）:
   1. **前提条件・業務ルール違反**（422 / 429 等、`HttpException` や早期 return）
   2. **外部 API・インフラ失敗**（Square 例外 → `HttpException` 等）
   3. その他の例外（`ValidationException` 等、Helper の場合）

Webhook ハンドラ（`handleInvoicePaymentMade()` 等）は **1 メソッド = 1 セクション** として A と同様にセパレータを付ける。

## テストケース一覧（TEST_SPECIFICATIONS.md）の書式

### 必須セクション

- `# {クラス名} テストケース詳細仕様`
- `## 概要`
- `## テストケース一覧表`（下表）
- `## テスト実行方法`（Sail コマンド。Helpers は `tests/sh/05_run_helpers_tests.sh` を案内してよい）

### 任意セクション

複雑な Service では **仕様表の前** に次を書いてよい。

- `## テスト方針`（モック方針、config 固定値、Factory ヘルパ名）
- ドメインルール表（例: `AiUsageService` のプラン変更と月間残数）

### テスト名の書式

| 対象 | 【】内の例 |
|------|-----------|
| Controller | 操作の日本語名: `【一覧取得】`, `【新規作成】` |
| Service | 機能ブロック名: `【カード更新】`, `【定期更新】` |
| Helper / static | **メソッド名**: `【parseQuantityDisplayToNumber】` |
| バリデーション（HTTP） | `【{操作名}】 バリデーションエラー（{フィールド} {内容}）` |

### 該当メソッド列

- 原則 `{Class}::{method}()`（Service / Helper 含む）
- Controller の FormRequest バリデーションも **同じ列は `{Controller}::{method}()`**（422 はテスト名と `rules()` 順で追う）
- FormRequest 単体の別仕様に限り `{Request}::rules()` を書いてよい

### 一覧表テンプレート

```markdown
| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| X-Y-Z | 【…】 … | 正常系/異常系 | … | … | `Class::method()` |
```

### テスト実行方法（記載例）

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/BillingServiceTest.php --stop-on-failure
```

- `{Category}` は `Feature/` または `Unit/` 以下のディレクトリ名（`Traits`, `Auth`, `Api`, `Services`, `Providers`, `Console`, `Helpers`, `Data` 等）
- カテゴリ一括は `tests/sh/01_run_traits_tests.sh` 〜 `05_run_helpers_tests.sh`（`00_run_all_tests.sh` は 01〜05 を順実行）。**全仕様が sh に載っているわけではない**ので、未掲載クラスは個別 `sail test` を仕様に書く

## テストコード（*Test.php）の書式

### 基本（HTTP / DB 付き Feature）

```php
<?php

use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->group = Group::create(['group_size' => 1]);
    $this->group->users()->attach($this->user->id);
    $this->user->refresh();
    $this->user->load('groups');
});

// ===== index() メソッドのテストケース =====

test('3-7-1: 【一覧取得】 正常な料理一覧取得', function () {
    // ...
});
```

- 多くの Api / Auth / Services は **ファイルスコープ**（`namespace` なし）
- Traits の一部は `namespace Tests\Feature\Traits;` — **既存ファイルに合わせる**

### Service（Feature）の追加パターン

- `beforeEach` で `config([...])` を固定
- 外部クライアントは `$this->mock(SquareBillingClient::class, ...)` 等
- 同一ファイル内の **ファイルスコープ関数** で Factory / Square スタブを共通化してよい（`BillingServiceTest.php` 参照）
- 例外: `expect(fn () => billingService()->updateCard(...))->toThrow(HttpException::class, '日本語メッセージ');`

### Unit（Helper 等）

```php
<?php

use App\Helpers\Quantity;
use Tests\TestCase;

uses(TestCase::class);

// ===== parseQuantityDisplayToNumber() メソッドのテストケース =====

test('5-1-1: 【parseQuantityDisplayToNumber】 分数をパースする', function () {
    expect(Quantity::parseQuantityDisplayToNumber('1/2'))->toBe(0.5);
});
```

- データ駆動は `->with([...])` を使用可
- `ValidationException` は `expect(fn () => ...)->toThrow(ValidationException::class)` 等

### テスト関数の命名

- `test('X-Y-Z: {仕様表のテスト名と完全一致}', function () { ... });`

### HTTP テストの定番パターン

#### 認証済みリクエスト

```php
$response = $this->actingAs($this->user)->get('/recipes');
```

#### 未認証

```php
$response = $this->get('/recipes');
$response->assertStatus(401);
$response->assertJson(['success' => false, 'message' => '認証が必要です。']);
```

#### グループ未所属

```php
$user = User::factory()->create(['email_verified_at' => now()]);
$response = $this->actingAs($user)->get('/recipes');
$response->assertStatus(422);
$response->assertJson(['success' => false, 'message' => 'ユーザーはグループに所属していません。']);
```

#### バリデーション 422

```php
$response = $this->actingAs($this->user)->postJson('/recipes', ['name' => '']);
$response->assertStatus(422);
$response->assertJsonValidationErrors(['name']);
```

#### Service 500（Controller テストで Service モック）

```php
$this->mock(\App\Services\RecipeService::class, function ($mock) {
    $mock->shouldReceive('index')->once()->andThrow(new \Exception('Database connection failed'));
});
$response = $this->actingAs($this->user)->get('/recipes');
$response->assertStatus(500);
```

#### レスポンス検証

```php
$response->assertStatus(200);
$response->assertJson(['success' => true]);
$response->assertJsonStructure(['success', 'message', 'data' => [...]]);
```

### レスポンス `message` の検証

API の `message` やバリデーション文言、`toThrow` の第2引数、モックの例外メッセージは **`__()` で lang キーを参照せず、日本語リテラル** で書く（文言と lang の乖離を検知するため）。

## テスト実行

```bash
cd server

# 個別（仕様書と同じ形式を推奨）
./vendor/bin/sail test tests/Feature/Api/RecipeControllerTest.php --stop-on-failure

# カテゴリ一括
./tests/sh/00_run_all_tests.sh
./tests/sh/01_run_traits_tests.sh
./tests/sh/02_run_auth_tests.sh
./tests/sh/03_run_api_tests.sh
./tests/sh/04_run_services_tests.sh    # Feature Services + Unit/Services/Ai の一部
./tests/sh/05_run_helpers_tests.sh   # Unit/Helpers
```

Providers / Console / Data / 未掲載の Service は仕様書記載の `sail test` パスで実行する。
