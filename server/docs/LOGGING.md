# ログ設定ガイド

## 概要

このドキュメントでは、本プロジェクトでのログ設定と使用方法について説明します。

## ログ設定

### ログレベル

#### error

-   アプリケーションエラー
-   データベースエラー
-   外部 API エラー

#### warning

-   非致命的な問題
-   処理は続行可能だが注意が必要

#### info

-   重要な操作の記録
-   監査ログ

#### debug

-   開発・デバッグ用の詳細情報

### ログチャンネル

本プロジェクトでは以下のログチャンネルが利用可能です。`.env`ファイルで設定できます：

#### 利用可能なログチャンネル

-   **stack**: 複数のチャンネルを組み合わせる（デフォルト）
-   **single**: 単一ファイルに記録
-   **daily**: 日次ローテーション
-   **slack**: Slack に通知
-   **papertrail**: Papertrail サービスに送信
-   **syslog**: システムログ
-   **errorlog**: PHP の error_log()を使用
-   **stderr**: 標準エラー出力
-   **null**: ログを記録しない
-   **emergency**: 緊急時のログ

#### .env での設定例

```env
# デフォルトのログチャンネル
LOG_CHANNEL=stack

# スタックチャンネルの構成
LOG_STACK=single,daily

# 日次ログの保持期間
LOG_DAILY_DAYS=30

# エラーログの保持期間
LOG_ERROR_DAYS=90

# Slack通知の設定
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
LOG_SLACK_USERNAME=Laravel Log
LOG_SLACK_EMOJI=:boom:

# Papertrailの設定
PAPERTRAIL_URL=logs.papertrailapp.com
PAPERTRAIL_PORT=12345
```

## LoggingTrait

プロジェクト固有の統一されたログ記録のためのトレイトを提供します。

### 記録される情報

-   **操作名**: どの操作でログが記録されたか
-   **コントローラー**: ログが記録されたコントローラークラス
-   **メソッド**: ログが記録されたメソッド名
-   **ユーザー情報**: ユーザー ID、グループ ID
-   **リクエスト情報**: HTTP メソッド、URL、IP アドレス、ユーザーエージェント
-   **リクエストデータ**: フィルタリングされたリクエストデータ（機密情報は除外）

### ログメソッド

#### logMessage（汎用ログ記録）

通常のログ記録用の汎用メソッドです。

```php
// 基本使用法（ログレベルはデフォルトでinfo）
$this->logMessage('info', '操作名', 'メッセージ', $request);

// ログレベルを明示的に指定
$this->logMessage('info', '操作名', 'メッセージ', $request);     // 情報
$this->logMessage('warning', '操作名', 'メッセージ', $request);  // 警告
$this->logMessage('debug', '操作名', 'メッセージ', $request);    // デバッグ

// 追加コンテキスト付き
$this->logMessage('info', 'レシピ作成', '処理を開始します', $request, [
    'recipe_name' => $request->name,
    'category_ids' => $request->categoryIds
]);
```

#### logError（エラーログ記録）

例外が発生した場合のエラーログ記録用です。

```php
try {
    // 処理
} catch (Exception $e) {
    // 基本的な使用方法（追加コンテキストなし）
    $this->logError('レシピ作成', $e, $request);

    // 追加コンテキスト付き
    $this->logError('レシピ作成', $e, $request, [
        'recipe_name' => $request->name,
        'category_ids' => $request->categoryIds
    ]);
}
```

## 使用方法

### 基本的な使用方法

```php
use App\Traits\LoggingTrait;

class YourController extends Controller
{
    use LoggingTrait;

    public function someMethod(Request $request)
    {
        try {
            // 処理
        } catch (Exception $e) {
            $this->logError('操作名', $e, $request, [
                'additional_context' => '追加情報'
            ]);
        }
    }
}
```

### エラーログの記録

```php
// 基本的な使用方法（追加コンテキストなし）
$this->logError('レシピ作成', $e, $request);

// 追加コンテキスト付き
$this->logError('レシピ作成', $e, $request, [
    'operation' => 'store',
    'recipe_name' => $request->name,
    'category_ids' => $request->categoryIds
]);

// 悪い例
Log::error('エラーが発生しました: ' . $e->getMessage());
```

### 通常のログ記録

```php
// 情報ログ
$this->logMessage('info', 'レシピ作成', 'レシピが正常に作成されました', $request, [
    'recipe_id' => $recipe->id
]);

// 警告ログ
$this->logMessage('warning', '画像アップロード', 'ファイルサイズが大きすぎます', $request, [
    'file_size' => $request->file('image')->getSize()
]);

// デバッグログ
$this->logMessage('debug', 'レシピ検索', '検索条件を処理中', $request, [
    'search_params' => $request->all()
]);
```

## ログ記録のポイント

### 機密情報の保護

以下の情報は自動的にフィルタリングされます：

-   パスワード
-   パスワード確認
-   トークン
-   API キー
-   シークレット

### コンテキスト情報の追加

ログの原因を特定しやすくするために、関連する情報を追加してください：

```php
$this->logError('画像アップロード', $e, $request, [
    'file_size' => $request->file('image')->getSize(),
    'file_type' => $request->file('image')->getMimeType(),
    'upload_directory' => $directory
]);
```

### ログ記録の最適化

1. 本番環境ではログレベルを error 以上に制限
2. 大量のデータを含むログは避ける
3. 非同期ログ記録の検討

## ログの確認

### ログファイルの場所

```
storage/logs/
├── laravel.log          # 現在のログ
├── laravel-2024-01-15.log  # 日次ログ
├── error.log            # 現在のエラーログ
└── error-2024-01-15.log    # 日次エラーログ
```

### ログの検索

```bash
# エラーログの確認
tail -f storage/logs/error.log

# 特定の操作のログを検索
grep "レシピ作成" storage/logs/laravel.log

# JSONログの解析
tail -f storage/logs/laravel.log | jq .
```

## トラブルシューティング

### ログが記録されない場合

1. ログディレクトリの権限を確認
2. ディスク容量を確認
3. ログレベル設定を確認

### ログファイルが大きすぎる場合

1. ローテーション設定を調整
2. ログレベルを上げる
3. 不要なログを削除
