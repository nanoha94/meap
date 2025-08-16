# 多言語対応ガイド

## 概要

このプロジェクトでは、日本語と英語の多言語対応を実装しています。API レスポンスメッセージ、バリデーションメッセージ、操作ログメッセージなどが多言語化されています。

## 対応言語

-   **日本語 (ja)** - デフォルト言語
-   **英語 (en)** - 国際化対応

## 言語ファイルの構成

### 1. API メッセージ (`lang/{locale}/api.php`)

API レスポンスで使用されるメッセージを定義します。

```php
// 日本語版
'recipe' => [
    'created' => 'レシピを作成しました。',
    'updated' => 'レシピ(:name)を更新しました。',
    'deleted' => 'レシピを削除しました。',
],

// 英語版
'recipe' => [
    'created' => 'Recipe created successfully.',
    'updated' => 'Recipe (:name) updated successfully.',
    'deleted' => 'Recipe deleted successfully.',
],
```

### 2. 操作ログ (`lang/{locale}/operations.php`)

操作ログで使用されるメッセージを定義します。

```php
// 日本語版
'recipe' => [
    'store' => 'レシピ作成',
    'update' => 'レシピ更新',
],

// 英語版
'recipe' => [
    'store' => 'Recipe created',
    'update' => 'Recipe updated',
],
```

### 3. カスタムバリデーション (`lang/{locale}/validation_custom.php`)

カスタムバリデーションメッセージを定義します。

```php
// 日本語版
'recipe' => [
    'name' => [
        'required' => 'レシピ名は必須です。',
        'max' => 'レシピ名は255文字以内で入力してください。',
    ],
],

// 英語版
'recipe' => [
    'name' => [
        'required' => 'Recipe name is required.',
        'max' => 'Recipe name must not exceed 255 characters.',
    ],
],
```

## 使用方法

### 1. 基本的な使用方法

```php
// 言語ファイルからメッセージを取得
$message = __('api.recipe.created');

// パラメータ付きメッセージ
$message = __('api.recipe.updated', ['name' => $recipeName]);

// 削除時の詳細情報付きメッセージ
$message = __('api.recipe.deleted', ['name' => $recipeName]);

// 一覧取得時の件数付きメッセージ
$message = __('api.recipe.list_retrieved', ['count' => $count]);

// 操作ログメッセージ
$operation = __('operations.recipe.store');
```

### 2. コントローラーでの使用例

```php
public function store(Request $request): JsonResponse
{
    try {
        // 処理...

        return $this->successResponse($data, __('api.recipe.created'));
    } catch (Exception $e) {
        return $this->handleException($e, $request, __('api.recipe.creation_failed'));
    }
}

public function destroy(Request $request, string $id): JsonResponse
{
    try {
        // 削除処理...
        $recipeName = $recipe->name;

        return $this->deletedResponse(__('api.recipe.deleted', ['name' => $recipeName]));
    } catch (Exception $e) {
        return $this->handleException($e, $request, __('api.recipe.deletion_failed'));
    }
}

public function index(Request $request): JsonResponse
{
    try {
        // 一覧取得処理...
        $count = $recipes->count();

        return $this->indexResponse($data, $count, __('api.recipe.list_retrieved', ['count' => $count]));
    } catch (Exception $e) {
        return $this->handleException($e, $request, __('api.general.error'));
    }
}
```

### 3. バリデーションでの使用例

```php
$request->validate([
    'name' => 'required|string|max:255',
], [
    'name.required' => __('validation_custom.recipe.name.required'),
    'name.max' => __('validation_custom.recipe.name.max'),
]);
```

## ロケール設定

### 1. 自動検出

ミドルウェア `SetLocale` が自動的にロケールを設定します：

1. **ユーザー設定優先**: 認証済みユーザーの言語設定
2. **ヘッダー検出**: `Accept-Language` ヘッダーから検出
3. **デフォルト**: 日本語 (ja)

### 2. 手動設定

```php
use App\Helpers\LocalizationHelper;

// ロケールを設定
LocalizationHelper::setLocale('en');

// 現在のロケールを取得
$locale = LocalizationHelper::getCurrentLocale();
```

### 3. 環境変数での設定

```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=ja
```

**注意**: ロケール設定は `config/app.php` で管理されています。

## ヘルパー関数

### LocalizationHelper

```php
use App\Helpers\LocalizationHelper;

// ロケールを設定
LocalizationHelper::setLocale('en');

// 現在のロケールを取得
$locale = LocalizationHelper::getCurrentLocale();

// サポートされているロケールを取得
$locales = LocalizationHelper::getSupportedLocales();

// ロケールがサポートされているかチェック
$isSupported = LocalizationHelper::isLocaleSupported('en');

// デフォルトロケールを取得
$defaultLocale = LocalizationHelper::getDefaultLocale();
```

## 新しい言語の追加

### 1. 言語ディレクトリの作成

```bash
mkdir server/lang/fr
```

### 2. 言語ファイルの作成

各言語ファイル（`api.php`, `operations.php`, `validation_custom.php`など）をフランス語版で作成します。

### 3. 設定の更新

```php
// config/app.php にロケール設定を追加
'available_locales' => [
    'ja' => ['name' => '日本語', 'flag' => '🇯🇵'],
    'en' => ['name' => 'English', 'flag' => '🇺🇸'],
    'fr' => ['name' => 'Français', 'flag' => '🇫🇷'], // 追加
],
```

### 4. LocalizationHelper の更新

```php
// app/Helpers/LocalizationHelper.php
public static function getSupportedLocales(): array
{
    return ['ja', 'en', 'fr']; // フランス語を追加
}
```

## ベストプラクティス

### 1. メッセージの一貫性

-   同じ操作のメッセージは統一された形式を使用
-   パラメータのプレースホルダー（`:name`）を活用
-   **削除時は何を削除したかを明示**（例：`レシピ(カレー)を削除しました`）
-   **一覧取得時は件数を含める**（例：`レシピを15件取得しました`）

### 2. エラーメッセージ

-   ユーザーにとって分かりやすいメッセージ
-   技術的な詳細は避ける
-   解決方法のヒントを含める

### 3. ログメッセージ

-   開発者にとって有用な情報
-   デバッグに必要な詳細を含める

## テスト

### 1. 言語切り替えのテスト

```php
// テストでロケールを設定
App::setLocale('en');
$this->assertEquals('Recipe created successfully.', __('api.recipe.created'));

App::setLocale('ja');
$this->assertEquals('レシピを作成しました。', __('api.recipe.created'));
```

### 2. パラメータ付きメッセージのテスト

```php
$message = __('api.recipe.updated', ['name' => 'Test Recipe']);
$this->assertEquals('レシピ(Test Recipe)を更新しました。', $message);
```

## トラブルシューティング

### 1. メッセージが表示されない

-   言語ファイルが正しい場所にあるか確認
-   キーパスが正しいか確認
-   キャッシュをクリア: `php artisan config:clear`

### 2. ロケールが設定されない

-   ミドルウェアが正しく登録されているか確認
-   ユーザーの言語設定が正しいか確認
-   ヘッダーの形式が正しいか確認

### 3. 翻訳が不完全

-   不足している言語ファイルを確認
-   キーが正しく定義されているか確認
-   フォールバックロケールが設定されているか確認
