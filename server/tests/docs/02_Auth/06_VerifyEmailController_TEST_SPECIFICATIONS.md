# VerifyEmailController テストケース詳細仕様

## 概要

このドキュメントは、VerifyEmailController のテストケースの詳細仕様を示します。メールアドレス確認機能を検証し、システムの安定性と安全性を確保します。この機能は署名付き URL、レート制限、リダイレクト処理を含む包括的なメール確認フローを提供します。

## テストケース一覧表

| ID     | テスト名                                 | 種別         | 入力条件                                         | 期待される出力                                                                        | 対応するコードレスポンス                                          | 検証ポイント                                 |
| ------ | ---------------------------------------- | ------------ | ------------------------------------------------ | ------------------------------------------------------------------------------------- | ----------------------------------------------------------------- | -------------------------------------------- |
| 2-6-1  | 正常なメール確認                         | 正常系       | 有効な署名付き URL、未確認のユーザー             | メールが確認され、プランページにリダイレクトされる                                    | `VerifyEmailController.php`<br>\_\_invoke() メソッド (24-30 行目) | メール確認成功とリダイレクトの確認           |
| 2-6-2  | メール確認の冪等性確認                   | 正常系       | 既に確認済みのユーザーが再度確認リンクにアクセス | 2 回目以降は早期リターンでプランページにリダイレクトされる（verified パラメータなし） | `VerifyEmailController.php`<br>\_\_invoke() メソッド (19-22 行目) | 冪等性と重複確認処理のスキップ確認           |
| 2-6-3  | リダイレクトパラメータ確認（verified=1） | 正常系       | 正常なメール確認完了後                           | verified=1 パラメータ付きでプランページにリダイレクトされる                           | `VerifyEmailController.php`<br>\_\_invoke() メソッド (28-30 行目) | リダイレクトパラメータの確認                 |
| 2-6-4  | Verified イベント発火確認                | 正常系       | 正常なメール確認後                               | Verified イベントが発火される                                                         | `VerifyEmailController.php`<br>\_\_invoke() メソッド (25 行目)    | イベント発火の確認                           |
| 2-6-5  | 間違ったハッシュ値                       | セキュリティ | 正しくないハッシュ値を含む署名付き URL           | エラータイプ `invalid_link` でメール確認ページにリダイレクト                          | `EmailVerificationRequest.php`<br>authorize() メソッド            | ハッシュ検証とセキュアなエラー処理確認       |
| 2-6-6  | 未認証ユーザーのアクセス                 | 異常系       | ログインしていない状態でメール確認リンクアクセス | エラータイプ `unauthenticated` でメール確認ページにリダイレクト                       | `EmailVerificationRequest.php`<br>authorize() メソッド            | 未認証時のセキュアなエラー処理確認           |
| 2-6-7  | 無効なパラメータ形式                     | セキュリティ | 存在しないユーザー ID でアクセス                 | エラータイプ `invalid_link` でメール確認ページにリダイレクト                          | `EmailVerificationRequest.php`<br>authorize() メソッド            | パラメータ検証とセキュアなエラー処理確認     |
| 2-6-8  | 無効な署名                               | セキュリティ | 改ざんされた署名付き URL                         | 403 Forbidden エラー                                                                  | Laravel の signed middleware                                      | 署名検証の確認                               |
| 2-6-9  | 署名なしの URL                           | セキュリティ | 署名が含まれていない URL                         | 403 Forbidden エラー                                                                  | Laravel の signed middleware                                      | 署名必須の確認                               |
| 2-6-10 | 期限切れの署名                           | セキュリティ | 期限が切れた署名付き URL                         | 403 Forbidden エラー                                                                  | Laravel の signed middleware                                      | 署名期限の確認                               |
| 2-6-11 | レート制限（1 分間に 6 回超過）          | セキュリティ | 短時間に 7 回以上のリクエスト                    | 429 Too Many Requests エラー                                                          | Laravel の throttle:6,1 middleware                                | レート制限の確認                             |
| 2-6-12 | レート制限リセット                       | セキュリティ | 1 分経過後の正常リクエスト                       | 正常処理が実行される                                                                  | Laravel の throttle:6,1 middleware                                | レート制限解除の確認                         |
| 2-6-13 | markEmailAsVerified() 失敗               | 異常系       | データベースエラーによりメール確認が失敗         | エラータイプ `database_error` でメール確認ページにリダイレクト                        | `VerifyEmailController.php`<br>verifyEmailAndFireEvent() メソッド | markEmailAsVerified() 失敗時のセキュアな処理 |
| 2-6-14 | Verified イベント発火失敗                | 異常系       | イベント発火時に例外が発生                       | エラータイプ `verification_failed` でメール確認ページにリダイレクト                   | `VerifyEmailController.php`<br>verifyEmailAndFireEvent() メソッド | イベント失敗時のセキュアなエラーハンドリング |
| 2-6-15 | データベース接続エラー                   | 異常系       | データベース接続が失敗                           | エラータイプ `database_error` でメール確認ページにリダイレクト                        | `VerifyEmailController.php`<br>verifyEmailAndFireEvent() メソッド | データベースエラーのセキュアな処理           |
| 2-6-16 | ログ出力とエラーリダイレクト確認         | 異常系       | 例外発生時                                       | 詳細ログが内部記録され、エラータイプのみ外部送信                                      | `VerifyEmailController.php`<br>handleVerificationError() メソッド | セキュアなログ出力とリダイレクトの確認       |
| 2-6-17 | エラー時のリダイレクト URL 確認          | 異常系       | 例外発生時                                       | エラータイプのみでメール確認ページにリダイレクト                                      | `VerifyEmailController.php`<br>handleVerificationError() メソッド | セキュアなエラー時リダイレクトの確認         |
| 2-6-18 | フロントエンド URL 設定なし              | 設定エラー   | config('app.frontend_url') が未設定              | 設定エラーまたはデフォルトリダイレクト                                                | `VerifyEmailController.php`<br>\_\_invoke() メソッド (20-34 行目) | 設定値の確認                                 |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/02_run_auth_tests.sh
```

### 認証テストのみ実行

```bash
# 特定のテストファイルのみ実行
./vendor/bin/sail artisan test tests/Feature/Auth/VerifyEmailControllerTest.php

# 認証関連のテストのみ実行
./vendor/bin/sail artisan test --filter="VerifyEmailControllerTest"

# Authディレクトリ内の全テストを実行
./vendor/bin/sail artisan test tests/Feature/Auth/
```

## 注意事項

-   テスト実行時は、メール送信機能をモックまたはテスト用設定に切り替えてください
-   署名付き URL のテストでは、Laravel の URL::signedRoute() を使用して正しい署名を生成してください
-   レート制限のテストでは、Cache ファサードをモックして制限状態をシミュレートしてください
-   エラーハンドリングのテストでは、例外をモックして適切にスローさせてください
-   リダイレクトのテストでは、config('app.frontend_url') の設定値を確認してください
-   トランザクション処理のテストでは、データベースの状態を適切にリセットしてください

## 関連ファイル

-   `app/Http/Controllers/Auth/VerifyEmailController.php` - メインのコントローラー
-   `app/Http/Middleware/EnsureEmailIsVerified.php` - メール確認状態をチェックするミドルウェア
-   `routes/auth.php` - ルート定義（signed, throttle:6,1 ミドルウェア付き）
-   `lang/ja/operations.php` - 日本語操作メッセージ
-   `lang/ja/auth.php` - 日本語認証メッセージ

## セキュリティ考慮事項

-   署名付き URL の検証は Laravel の signed middleware に依存しています
-   レート制限により、1 分間に 6 回までのリクエストに制限されています
-   ユーザー認証とハッシュ値の検証は EmailVerificationRequest で行われます
-   エラー発生時もフロントエンドにリダイレクトして、バックエンドの内部情報を隠蔽しています
