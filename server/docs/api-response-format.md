# API レスポンス形式の統一化

## 概要

このプロジェクトでは、JSON API 制御を統一するために以下の改善を行いました：

1. **ApiResponse Trait** - 統一されたレスポンス形式を提供
2. **Controller 基底クラス** - 基本的なレスポンス機能を提供
3. **ApiController 基底クラス** - API 機能が必要な場合の共通機能を提供
4. **統一されたエラーハンドリング** - 一貫性のあるエラーレスポンス

## レスポンス形式

### 成功レスポンス

```json
{
    "success": true,
    "message": "操作が完了しました。",
    "data": {
        // 実際のデータ
    }
}
```

### エラーレスポンス

```json
{
    "success": false,
    "message": "エラーメッセージ",
    "errors": {
        // バリデーションエラーの詳細（オプション）
    }
}
```

## 使用可能なメソッド

### ApiResponse Trait

-   `successResponse($data, $message, $statusCode)` - 成功レスポンス
-   `errorResponse($message, $statusCode, $errors)` - エラーレスポンス
-   `createdResponse($data, $message)` - 作成成功（201）
-   `updatedResponse($data, $message)` - 更新成功（200）
-   `deletedResponse($message)` - 削除成功（200）
-   `indexResponse($data, $total, $message)` - 一覧取得
-   `showResponse($data, $message)` - 詳細取得
-   `validationErrorResponse($exception)` - バリデーションエラー
-   `handleException($e, $defaultMessage)` - 例外ハンドリング

### Controller 基底クラス

すべてのコントローラーで使用可能な基本的なレスポンス機能を提供します。

### ApiController 基底クラス

API 機能が必要な場合のみ使用する基底クラスです：

-   `getUserGroup($request)` - ユーザーのグループ取得
-   `handleValidationError($exception)` - バリデーションエラーハンドリング
-   `handleGeneralException($exception, $defaultMessage)` - 一般的な例外ハンドリング
-   `notFoundResponse($message)` - 404 エラー
-   `forbiddenResponse($message)` - 403 エラー
-   `unauthorizedResponse($message)` - 401 エラー

## 実装例

### API コントローラーの実装

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\Example;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExampleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $group = $this->getUserGroup($request);
            $items = $group->examples()->get();

            return $this->indexResponse($items, $items->count(), 'データ一覧を取得しました。');
        } catch (Exception $e) {
            return $this->handleGeneralException($e, 'データの取得中にエラーが発生しました。');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $group = $this->getUserGroup($request);
            $item = Example::create([
                'group_id' => $group->id,
                'name' => $validated['name'],
            ]);

            return $this->createdResponse($item, 'データを作成しました。');
        } catch (ValidationException $e) {
            return $this->handleValidationError($e);
        } catch (Exception $e) {
            return $this->handleGeneralException($e, 'データの作成中にエラーが発生しました。');
        }
    }
}
```

### 認証コントローラーの実装

認証関連のコントローラーは `Controller` を直接継承し、`ApiResponse` トレイトの機能を使用します。

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // ログイン状態チェック
        if (Auth::check()) {
            return $this->errorResponse('既にログインしています。', 409);
        }

        // ... 処理 ...

        return $this->successResponse($user, 'ユーザー登録が完了しました。');
    }
}
```

## メリット

1. **一貫性** - すべての API エンドポイントで統一されたレスポンス形式
2. **保守性** - レスポンス形式の変更が容易
3. **開発効率** - 共通機能の再利用
4. **エラーハンドリング** - 統一されたエラー処理
5. **型安全性** - JsonResponse の型指定
6. **適切な継承** - 必要に応じて適切な基底クラスを選択

## 移行ガイド

既存のコントローラーを新しい形式に移行する場合：

### API 機能が必要な場合

1. `Controller` を `ApiController` に変更
2. `use ApiResponse;` は不要（Controller 基底クラスで提供）
3. `response()->json()` を適切なメソッドに変更
4. 例外処理を `try-catch` で囲む
5. バリデーションエラーを `ValidationException` でキャッチ

### 基本的な機能のみ必要な場合

1. `Controller` を継承したまま
2. `use ApiResponse;` は不要（Controller 基底クラスで提供）
3. `response()->json()` を適切なメソッドに変更
4. 必要に応じて例外処理を追加

## 継承関係

```
Controller (ApiResponseトレイトを含む)
├── 認証コントローラー (RegisteredUserController, LoginController等)
├── その他の基本コントローラー
└── ApiController
    └── API機能が必要なコントローラー (RecipeController, ShoppingController等)
```

この設計により、必要最小限の機能のみを継承し、コードの重複を避けることができます。
