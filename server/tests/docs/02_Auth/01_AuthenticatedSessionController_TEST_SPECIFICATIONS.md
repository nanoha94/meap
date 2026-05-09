# AuthenticatedSessionController テストケース詳細仕様

## 概要

Auth 関連のコントローラーテストファイル群で実装されている認証機能の包括的なテストスイートの詳細仕様です。ログイン、ログアウト、ユーザー登録、メール認証、パスワードリセット機能の動作を詳細に検証し、認証システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                               | 種別         | 入力条件                 | 期待される出力           | 該当メソッド                                  |
| ------ | ------------------------------------------------------ | ------------ | ------------------------ | ------------------------ | --------------------------------------------- |
| 2-1-1  | 【ログイン】 正常ログイン                               | 正常系       | 有効な認証情報           | HTTP 200 JSON success    | `AuthenticatedSessionController::store()`     |
| 2-1-2  | 【ログイン】 Remember Me 機能                           | 正常系       | remember=true            | 永続クッキー設定         | `AuthenticatedSessionController::store()`     |
| 2-1-3  | 【ログイン】 セッション再生成テスト                     | 正常系       | ログイン成功後           | セッションが再生成される | `AuthenticatedSessionController::store()`     |
| 2-1-4  | 【ログイン】 無効な認証情報                             | 異常系       | 存在しないメールアドレス | HTTP 302                 | `AuthenticatedSessionController::store()`     |
| 2-1-5  | 【ログイン】 間違ったパスワード                         | 異常系       | 間違ったパスワード       | HTTP 302                 | `AuthenticatedSessionController::store()`     |
| 2-1-6  | 【ログイン】 認証情報不足                               | 異常系       | 認証情報が不足           | HTTP 302                 | `AuthenticatedSessionController::store()`     |
| 2-1-7  | 【ログイン】 無効なメール形式                           | 異常系       | 無効なメール形式         | HTTP 302                 | `AuthenticatedSessionController::store()`     |
| 2-1-8  | 【ログイン】 バリデーションエラー（メールアドレス未入力） | 異常系       | email が空               | HTTP 422 JSON errors     | `LoginRequest::rules()`                       |
| 2-1-9  | 【ログイン】 バリデーションエラー（パスワード未入力）   | 異常系       | password が空            | HTTP 422 JSON errors     | `LoginRequest::rules()`                       |
| 2-1-10 | 【ログイン】 バリデーションエラー（両方の項目未入力）   | 異常系       | email, password が空     | HTTP 422 JSON errors     | `LoginRequest::rules()`                       |
| 2-1-11 | 【ログイン】 カスタムバリデーションメッセージ          | 異常系       | バリデーションエラー時   | 国際化メッセージ         | `LoginRequest::messages()`                    |
| 2-1-12 | 【ログイン】 レート制限                                 | セキュリティ | 連続 5 回失敗            | HTTP 302 throttle msg    | `LoginRequest::ensureIsNotRateLimited()`      |
| 2-1-13 | 【ログイン】 レート制限クリア                           | セキュリティ | ログイン成功後           | HTTP 200                 | `LoginRequest::authenticate()`                |
| 2-1-14 | 【ログイン】 Lockout イベント発火                       | セキュリティ | レート制限時             | Lockout イベント         | `LoginRequest::ensureIsNotRateLimited()`      |
| 2-1-15 | 【ログアウト】 正常ログアウト                           | ログアウト   | 認証済み                 | HTTP 200                 | `AuthenticatedSessionController::destroy()`  |
| 2-1-16 | 【ログアウト】 未認証ログアウト                         | ログアウト   | 未認証                   | HTTP 302                 | `AuthenticatedSessionController::destroy()`  |
| 2-1-17 | 【ログアウト】 セッション無効化                         | ログアウト   | ログアウト後             | HTTP 200                 | `AuthenticatedSessionController::destroy()`  |
| 2-1-18 | 【ログアウト】 クッキー削除確認                         | ログアウト   | ログアウト後             | クッキー削除             | `AuthenticatedSessionController::destroy()`  |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/02_run_auth_tests.sh
```
