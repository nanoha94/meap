---
name: server-testing
description: サーバーサイドのテストケース一覧（TEST_SPECIFICATIONS.md）およびテストコード（*Test.php）の作成・更新ルール。テスト追加・修正、テストケース一覧の作成・更新時に使用する。
---

# サーバーサイド テスト規約

## 技術スタック

- **テストフレームワーク**: Pest PHP（Laravel）
- **テスト種別**: Feature テスト（`server/tests/Feature/`）
- **テストケース一覧**: `server/tests/docs/` 配下の Markdown ファイル

## ディレクトリ構成

```
server/tests/
├── docs/                          # テストケース一覧（Markdown）
│   ├── 01_Traits/                 # 大分類 1: Traits
│   │   ├── 01_ApiResponse_TEST_SPECIFICATIONS.md
│   │   ├── 02_AutoComplement_TEST_SPECIFICATIONS.md
│   │   └── ...
│   ├── 02_Auth/                   # 大分類 2: Auth
│   │   ├── 01_AuthenticatedSessionController_TEST_SPECIFICATIONS.md
│   │   └── ...
│   └── 03_Api/                    # 大分類 3: Api
│       ├── 01_ImageController_TEST_SPECIFICATIONS.md
│       ├── 07_RecipeController_TEST_SPECIFICATIONS.md
│       └── ...
├── Feature/                       # テストコード
│   ├── Traits/                    # 大分類 1 に対応
│   ├── Auth/                      # 大分類 2 に対応
│   └── Api/                       # 大分類 3 に対応
├── sh/                            # テスト実行シェルスクリプト
├── Pest.php
└── TestCase.php
```

## テストケース ID 体系

テストケース ID は **X-Y-Z** の連番形式とする。

| 要素 | 説明 | 例 |
|------|------|----|
| **X** | 大分類番号。`server/tests/docs/` 配下のディレクトリ名先頭番号と一致させる | `01_Traits` → 1, `02_Auth` → 2, `03_Api` → 3 |
| **Y** | 中分類番号。テストケース一覧ファイル名の先頭番号と一致させる | `07_RecipeController_TEST_SPECIFICATIONS.md` → 7 |
| **Z** | テストケース一覧ファイル内での連番（1 始まり） | 1, 2, 3, ... |

**例**: `3-7-1` = 大分類 `03_Api` / 中分類 `07_RecipeController` / 1 番目のテストケース

## テストケースの並び順

**コントローラの公開メソッド（ルートに対応する処理）ごとにブロックを分ける。** `TEST_SPECIFICATIONS.md` の行順・`Z` の連番・`*Test.php` 内の `test(...)` の並びは、このセクションと **同一の順序** に揃える。

1. **メソッドごとにグループ化** する（`index` → `store` → `show` … や、ルート定義・コントローラの並びに合わせる）
2. **各メソッド内では、種別どおり「正常系」をすべて先に、「異常系」をすべて後に** 並べる（一覧表の「種別」列と矛盾させない）
3. **異常系**の並びは、**実際の処理が拒否されるおおよその順**（ミドルウェア → `FormRequest` → コントローラ本体 → サービス）に合わせる。種類ごとにまとめ、ブロック内では次の順とする。
   1. **未認証**（例: 401。認証ミドルウェアで落ちるケース）
   2. **`FormRequest::rules()` に基づくバリデーションエラー**（422）。コントローラに入る前に確定する。**項目どうしの順序は `rules()` のキー定義順**（およびその配下の `*` ルール）
   3. **認証済みかつ入力は通過したが、コントローラ内の業務チェック等で失敗**（バリデーション以外の 4xx。`getUserGroup` でのグループ未所属など）
   4. **サーバー・サービス・インフラ起因の失敗**（例: 500）
4. `rules()` 由来の 422 が複数ある場合は、上記 3. の **2.** のブロック内だけで `rules()` の順を守る

`// ===== {method}() メソッドのテストケース =====` の直下から、上記の順で `test(...)` を並べる。

## テストケース一覧（TEST_SPECIFICATIONS.md）の書式

ファイル名: `{番号}_{コントローラー名}_TEST_SPECIFICATIONS.md`

### テスト名の書式

- メソッドの種類を **【】** で囲んで先頭に付ける: `【一覧取得】`, `【新規作成】`, `【更新】`, `【削除】` 等
- バリデーションエラーの場合: `【{メソッド名}】 バリデーションエラー（{フィールド名} {エラー内容}）`

### 該当メソッド列の書式

- **コントローラー単位でメソッドをまとめる。** 一覧表では、そのテストが検証するルートと対応する **`{Controller}::{method}()` を記載する**（同一エンドポイントにぶら下がる **FormRequest のバリデーションもこの列ではコントローラメソッドで統一**する。入力検証であることはテスト名の「バリデーションエラー」と `rules()` の並び順ルールで追う）。
- FormRequest だけを単体で一覧する別ドキュメントなど、エンドポイントと切り離して書く場合のみ `{Request}::rules()` を **該当メソッド** に書いてよい。

### ファイル構成テンプレート

```markdown
# {コントローラー名} テストケース詳細仕様

## 概要

{テスト対象の概要説明}

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| X-Y-Z | 【{メソッド名}】 {テスト名} | 正常系/異常系 | {入力条件} | {期待される出力} | `{Controller}::{method}()` |

## テスト実行方法

### Sail 環境での実行

\`\`\`bash
cd server
./vendor/bin/sail test tests/Feature/{Category}/{Name}Test.php --stop-on-failure
\`\`\`

- `{Category}` は `Traits` / `Auth` / `Api` / `Services` のいずれか（`server/tests/Feature/` 配下のディレクトリ名）
- `{Name}` は仕様書ファイル名の `{コントローラー名}` 部分と一致させる（例: `07_RecipeController` → `RecipeControllerTest.php`）
```

## テストコード（*Test.php）の書式

### 基本構造

```php
<?php

use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト共通のセットアップ
    $this->user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $this->group = Group::create(['group_size' => 1]);
    $this->group->users()->attach($this->user->id);
    $this->user->refresh();
    $this->user->load('groups');
});

// ===== {method}() メソッドのテストケース =====

test('X-Y-Z: 【{メソッド名}】 {テスト名}', function () {
    // テストコード
});
```

### メソッドグループ間のセパレータ

```php
// ===== {method}() メソッドのテストケース =====
```

セクション内の `test(...)` の並びは、上記 **「テストケースの並び順」** と同じにする（正常系を先に並べ、その後に異常系を **未認証 → `rules()` 由来の 422 → コントローラの業務 4xx → 500** の順で並べる）。

### テスト関数の命名規則

- `test('X-Y-Z: {テストケース一覧のテスト名}', function () { ... });`
- テストケース ID とテスト名は、テストケース一覧と **完全に一致** させる

### テストコード内のパターン

#### 認証済みリクエスト

```php
$response = $this->actingAs($this->user)->get('/recipes');
$response = $this->actingAs($this->user)->post('/recipes', [...]);
$response = $this->actingAs($this->user)->put("/recipes/{$id}", [...]);
$response = $this->actingAs($this->user)->delete("/recipes/{$id}");
```

#### 未認証テスト

```php
$response = $this->get('/recipes');
$response->assertStatus(401);
$response->assertJson(['success' => false, 'message' => '認証が必要です。']);
```

#### グループ未所属テスト

```php
$user = User::factory()->create(['email_verified_at' => now()]);
$response = $this->actingAs($user)->get('/recipes');
$response->assertStatus(422);
$response->assertJson(['success' => false, 'message' => 'ユーザーはグループに所属していません。']);
```

#### バリデーションエラーテスト

```php
$response = $this->actingAs($this->user)->postJson('/recipes', [
    'name' => '',  // バリデーション違反
]);
$response->assertStatus(422);
$response->assertJsonValidationErrors(['name']);
```

#### DB エラーテスト（Service モック）

```php
$this->mock(\App\Services\RecipeService::class, function ($mock) {
    $mock->shouldReceive('index')
        ->once()
        ->andThrow(new \Exception('Database connection failed'));
});
$response = $this->actingAs($this->user)->get('/recipes');
$response->assertStatus(500);
```

#### レスポンス検証

```php
$response->assertStatus(200);
$response->assertJson(['success' => true]);
$response->assertJsonStructure(['success', 'message', 'data' => [...]]);
$response->assertHeader('Content-Type', 'application/json');
```

## テスト実行

```bash
cd server

# 個別ファイル実行（TEST_SPECIFICATIONS.md の「テスト実行方法」と同じ形式）
./vendor/bin/sail test tests/Feature/Api/RecipeControllerTest.php --stop-on-failure

# カテゴリ一括実行（任意）
./tests/sh/00_run_all_tests.sh          # 全テスト
./tests/sh/01_run_traits_tests.sh       # Traits テスト
./tests/sh/02_run_auth_tests.sh         # Auth テスト
./tests/sh/03_run_api_tests.sh          # API テスト
./tests/sh/04_run_services_tests.sh     # Services テスト
./tests/sh/05_run_helpers_tests.sh      # Helpers テスト
```
