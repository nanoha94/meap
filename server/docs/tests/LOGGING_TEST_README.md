# ログテスト実行ガイド

このドキュメントでは、`LoggingTrait`と`ExceptionHandlerTrait`のテスト実行方法と、実際のログ出力の確認方法について説明します。

## 作成されたテストファイル

### 1. LoggingTraitTest.php

-   `LoggingTrait`の基本動作をテスト
-   各ログレベル（info, error, warning）の出力確認
-   機密情報のフィルタリング動作確認
-   リクエスト情報の記録確認

### 2. ExceptionHandlerTraitTest.php

-   各種例外の処理をテスト
-   バリデーション例外、モデル未発見例外、クエリ例外の処理確認
-   例外処理時のログ出力確認

### 3. LoggingIntegrationTest.php

-   実際のログファイルへの出力を確認
-   統合的な動作テスト
-   機密情報フィルタリングの実際の動作確認

## テストの実行方法

### 方法 1: コマンドラインから直接実行

```bash
# 特定のテストファイルのみ実行
php artisan test tests/Feature/LoggingTraitTest.php

# 複数のテストファイルを実行
php artisan test --filter="LoggingTraitTest|ExceptionHandlerTraitTest|LoggingIntegrationTest"

# 全テストを実行
php artisan test
```

### 方法 2: スクリプトを使用（推奨）

#### Linux/Mac 環境

```bash
chmod +x test-logging.sh
./test-logging.sh
```

#### Windows 環境

```cmd
test-logging.bat
```

## ログ出力の確認方法

### 1. リアルタイムでのログ確認

```bash
# ログファイルの末尾を監視
tail -f storage/logs/laravel.log

# Windows環境
powershell "Get-Content 'storage\logs\laravel.log' -Wait"
```

### 2. 特定のログレベルの確認

```bash
# エラーログのみ表示
grep 'ERROR' storage/logs/laravel.log

# 情報ログのみ表示
grep 'INFO' storage/logs/laravel.log

# 警告ログのみ表示
grep 'WARNING' storage/logs/laravel.log
```

### 3. 特定の操作のログ確認

```bash
# ユーザー作成操作のログを確認
grep 'ユーザー作成' storage/logs/laravel.log

# バリデーションエラーのログを確認
grep 'バリデーション' storage/logs/laravel.log
```

## 期待されるログ出力例

### Info ログの例

```
[2024-01-15 10:30:15] local.INFO: 操作「ユーザー作成」: 新しいユーザーが作成されました {
    "operation": "ユーザー作成",
    "controller": "TestLoggingController",
    "method": "testInfoLogging",
    "user_id": 1,
    "group_id": 100,
    "request_method": "POST",
    "request_url": "http://localhost/api/users",
    "request_ip": "127.0.0.1",
    "user_agent": "Mozilla/5.0...",
    "request_data": {
        "email": "test@example.com",
        "password": "*****"
    },
    "user_email": "test@example.com",
    "user_role": "admin"
}
```

### Error ログの例

```
[2024-01-15 10:30:16] local.ERROR: 操作「ユーザー作成」: エラーが発生しました {
    "operation": "ユーザー作成",
    "controller": "TestLoggingController",
    "method": "testErrorLogging",
    "user_id": null,
    "group_id": null,
    "request_method": "POST",
    "request_url": "http://localhost/api/users",
    "request_ip": "127.0.0.1",
    "user_agent": "Mozilla/5.0...",
    "request_data": {
        "email": "test@example.com",
        "password": "*****"
    },
    "error_message": "テスト用のエラーです",
    "error_code": 0,
    "file": "/path/to/TestLoggingController.php",
    "line": 35,
    "trace": "#0...",
    "status_code": 500,
    "model": null,
    "attempted_email": "test@example.com"
}
```

## テストで確認できるポイント

### 1. ログの構造

-   操作名、コントローラー名、メソッド名の記録
-   ユーザー情報とグループ情報の記録
-   リクエスト情報（メソッド、URL、IP、ユーザーエージェント）の記録

### 2. 機密情報のフィルタリング

-   パスワード、トークン、API キーなどの機密情報が`[*****]`に置換される
-   非機密情報（メールアドレス、ユーザー名など）はそのまま記録される

### 3. 例外処理の統合

-   例外発生時の適切なログ記録
-   例外の種類に応じた適切なレスポンス返却
-   エラーコンテキストの詳細な記録

## トラブルシューティング

### ログファイルが作成されない場合

1. `storage/logs`ディレクトリの権限を確認
2. Laravel のログ設定を確認（`config/logging.php`）
3. ディスク容量を確認

### テストが失敗する場合

1. 必要なトレイトやクラスが正しく use されているか確認
2. 依存関係が正しく解決されているか確認
3. ログのモック設定を確認

### ログの内容が期待と異なる場合

1. ログ設定ファイルの確認
2. トレイトの実装内容の確認
3. テストデータの設定確認

## カスタマイズのヒント

### 新しいログレベルの追加

```php
protected function logDebug(string $operation, string $message, Request $request, array $additionalContext = []): void
{
    $this->logMessage('debug', $operation, $message, $request, $additionalContext);
}
```

### 新しい機密フィールドの追加

```php
private function filterSensitiveData(array $context, Request $request): array
{
    $sensitiveFields = [
        'password', 'password_confirmation', 'token', 'api_key', 'secret',
        'credit_card', 'ssn', 'phone_number' // 新しいフィールドを追加
    ];
    // ... 既存の処理
}
```

### カスタム例外の対応

```php
if ($e instanceof CustomBusinessException) {
    $message = $defaultMessage ?? __('api.general.business_error');
    $this->logError($operation, $e, $request, array_merge($additionalContext, [
        'business_code' => $e->getBusinessCode(),
        'message' => $message,
    ]));
    return $this->errorResponse($message, HttpStatusCode::BAD_REQUEST);
}
```

このテストスイートを使用することで、ログ機能の動作を詳細に確認し、本番環境での動作を事前に検証できます。
