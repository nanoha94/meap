# VerifyEmailController リファクタリング計画

## 概要

`VerifyEmailController`の`__invoke`メソッドにおけるセキュリティリスクとユーザー体験の改善を目的としたリファクタリング計画。

## 現在の問題点

### 1. セキュリティリスク

```php
// 問題のあるコード
'?error=' . urlencode($e->getMessage())
```

-   **内部情報漏洩**: 例外メッセージがそのまま URL パラメータとして外部に露出
-   **システム情報の推測**: データベース接続エラー、ファイルパスなどの内部構造が推測可能
-   **攻撃ベクトルの拡大**: エラーメッセージから脆弱性を特定されるリスク

### 2. ユーザー体験の悪化

-   技術的なエラーメッセージが一般ユーザーに表示される
-   適切なガイダンスや解決方法が提供されない
-   エラーの原因が不明でユーザーが困惑する

### 3. 保守性の問題

-   TODO コメントが残っている
-   エラーハンドリングの統一性が不十分
-   設定値のハードコーディング

## 修正方針

### Phase 1: セキュリティ改善（最優先）

#### 1.1 エラーメッセージの外部露出防止

```php
// Before
'?error=' . urlencode($e->getMessage())

// After
'?error=verification_failed'
```

#### 1.2 エラータイプベースの実装

```php
private function determineErrorType(\Exception $e): string
{
    if ($e instanceof DatabaseException) {
        return 'database_error';
    }
    if ($e instanceof ValidationException) {
        return 'validation_error';
    }
    return 'verification_failed';
}
```

### Phase 2: ユーザー体験の改善

#### 2.1 ユーザーフレンドリーなエラーメッセージ

-   フロントエンド側でエラータイプに応じた適切なメッセージを表示
-   解決方法やサポート情報の提供

#### 2.2 エラーページの設計

-   エラーの種類に応じた適切なガイダンス
-   再試行ボタンやサポートへの問い合わせ方法

### Phase 3: コード品質の向上

#### 3.1 定数の定義

```php
private const FRONTEND_PLAN_URL = '/plan';
private const FRONTEND_VERIFY_URL = '/email/verify';
private const VERIFIED_PARAM = 'verified=1';
```

#### 3.2 メソッドの分割

```php
private function redirectToPlan(): RedirectResponse
private function redirectToVerifyWithError(string $errorType): RedirectResponse
```

#### 3.3 ログ記録の強化

```php
Log::error('Email verification failed', [
    'user_id' => $user->id,
    'error_type' => $errorType,
    'exception' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

## 実装手順

### Step 1: セキュリティ修正

1. 例外メッセージの外部露出を停止
2. エラータイプベースの実装
3. セキュリティテストの実行

### Step 2: フロントエンド連携

1. エラータイプに応じたメッセージ定義
2. エラーページの UI 改善
3. ユーザビリティテスト

### Step 3: コードリファクタリング

1. 定数の定義
2. メソッドの分割
3. ログ記録の改善
4. 単体テストの追加

## テスト計画

### セキュリティテスト

-   [ ] 例外メッセージが外部に露出しないことを確認
-   [ ] エラータイプのみが URL パラメータとして渡されることを確認
-   [ ] 内部情報がログに適切に記録されることを確認

### 機能テスト

-   [ ] 正常なメール認証フロー
-   [ ] エラー時の適切なリダイレクト
-   [ ] エラータイプの正確性

### ユーザビリティテスト

-   [ ] エラーページでの適切なガイダンス表示
-   [ ] ユーザーが理解しやすいメッセージ
-   [ ] 解決方法の明確性

## 影響範囲

### バックエンド

-   `VerifyEmailController`
-   ログ設定
-   エラーハンドリング

### フロントエンド

-   エラーページ（`/email/verify`）
-   エラーメッセージの表示ロジック
-   ユーザーガイダンス

### 設定

-   `config/app.php`のフロントエンド URL 設定
-   ログ設定

## リスクと対策

### リスク

1. **既存ユーザーの混乱**: エラーメッセージが変わることによる混乱
2. **デバッグの困難化**: 詳細なエラー情報が外部に表示されなくなる

### 対策

1. **段階的な移行**: エラーログの充実化でデバッグ情報を補完
2. **ユーザー教育**: 新しいエラーページでの適切なガイダンス
3. **サポート体制**: エラー発生時の適切なサポート提供

## 完了基準

-   [ ] 例外メッセージの外部露出が完全に停止
-   [ ] エラータイプベースのエラーハンドリングが実装
-   [ ] ユーザーフレンドリーなエラーページが完成
-   [ ] セキュリティテストが全て通過
-   [ ] 単体テストのカバレッジが 90%以上
-   [ ] ユーザビリティテストが完了

## 参考資料

-   [Laravel セキュリティベストプラクティス](https://laravel.com/docs/security)
-   [OWASP エラーハンドリングガイドライン](https://owasp.org/www-project-web-security-testing-guide/)
-   [ユーザビリティガイドライン](https://www.usability.gov/)
