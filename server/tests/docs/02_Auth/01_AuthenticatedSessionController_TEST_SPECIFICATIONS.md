# AuthenticatedSessionController テストケース詳細仕様

## 概要

Auth 関連のコントローラーテストファイル群で実装されている認証機能の包括的なテストスイートの詳細仕様です。ログイン、ログアウト、ユーザー登録、メール認証、パスワードリセット機能の動作を詳細に検証し、認証システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名               | 種別         | 入力条件                 | 期待される出力           | 対応するコードレスポンス                                                   | 検証ポイント               |
| ------ | ---------------------- | ------------ | ------------------------ | ------------------------ | -------------------------------------------------------------------------- | -------------------------- |
| 2-1-1  | 正常ログイン           | 正常系       | 有効な認証情報           | HTTP 200 JSON success    | `AuthenticatedSessionController.php`<br>store() メソッド (約 28-34 行目)   | 認証成功時の挙動           |
| 2-1-2  | Remember Me 機能       | 正常系       | remember=true            | 永続クッキー設定         | `AuthenticatedSessionController.php`<br>store() メソッド (約 28-34 行目)   | セッション持続             |
| 2-1-3  | セッション再生成テスト | 正常系       | ログイン成功後           | セッションが再生成される | `AuthenticatedSessionController.php`<br>store() メソッド (約 28-34 行目)   | セッション管理の確認       |
| 2-1-4  | 無効な認証情報         | 異常系       | 存在しないメールアドレス | HTTP 302                 | `AuthenticatedSessionController.php`<br>store() メソッド (約 28-34 行目)   | エラーハンドリングの確認   |
| 2-1-5  | 間違ったパスワード     | 異常系       | 間違ったパスワード       | HTTP 302                 | `AuthenticatedSessionController.php`<br>store() メソッド (約 28-34 行目)   | エラーハンドリングの確認   |
| 2-1-6  | 認証情報不足           | 異常系       | 認証情報が不足           | HTTP 302                 | `AuthenticatedSessionController.php`<br>store() メソッド (約 28-34 行目)   | エラーハンドリングの確認   |
| 2-1-7  | 無効なメール形式       | 異常系       | 無効なメール形式         | HTTP 302                 | `AuthenticatedSessionController.php`<br>store() メソッド (約 28-34 行目)   | バリデーションエラーの確認 |
| 2-1-8  | レート制限             | セキュリティ | 連続 6 回失敗            | HTTP 429                 | `AuthenticatedSessionController.php`<br>store() メソッド (約 28-34 行目)   | アカウントロック           |
| 2-1-9  | レート制限クリア       | セキュリティ | ログイン成功後           | HTTP 200                 | `AuthenticatedSessionController.php`<br>store() メソッド (約 28-34 行目)   | レート制限解除の確認       |
| 2-1-10 | 正常ログアウト         | ログアウト   | 認証済み                 | HTTP 200                 | `AuthenticatedSessionController.php`<br>destroy() メソッド (約 49-94 行目) | セッション無効化           |
| 2-1-11 | 未認証ログアウト       | ログアウト   | 未認証                   | HTTP 302                 | `AuthenticatedSessionController.php`<br>destroy() メソッド (約 49-94 行目) | リダイレクト               |
| 2-1-12 | セッション無効化       | ログアウト   | ログアウト後             | HTTP 200                 | `AuthenticatedSessionController.php`<br>destroy() メソッド (約 49-94 行目) | セッション無効化の確認     |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/02_run_auth_tests.sh
```
