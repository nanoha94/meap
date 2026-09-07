# VerifyEmailController テストケース詳細仕様

## 概要

このドキュメントは、VerifyEmailController のテストケースの詳細仕様を示します。メールアドレス確認機能を検証し、システムの安定性と安全性を確保します。この機能は署名付き URL、レート制限、リダイレクト処理を含む包括的なメール確認フローを提供します。

## テストケース一覧表

| ID     | テスト名                                                             | 種別         | 入力条件                                         | 期待される出力                                                                        | 該当メソッド                                    |
| ------ | -------------------------------------------------------------------- | ------------ | ------------------------------------------------ | ------------------------------------------------------------------------------------- | ----------------------------------------------- |
| 2-6-1  | 【__invoke】 正常なメール確認                                         | 正常系       | 有効な署名付き URL、未確認のユーザー             | メールが確認され、プランページにリダイレクトされる                                    | `VerifyEmailController::__invoke()`             |
| 2-6-2  | 【__invoke】 メール確認の冪等性確認                                   | 正常系       | 既に確認済みのユーザーが再度確認リンクにアクセス | 2 回目以降は早期リターンでプランページにリダイレクトされる（verified パラメータなし） | `VerifyEmailController::__invoke()`             |
| 2-6-3  | 【__invoke】 リダイレクトパラメータ確認（verified=1）                 | 正常系       | 正常なメール確認完了後                           | verified=1 パラメータ付きでプランページにリダイレクトされる                           | `VerifyEmailController::__invoke()`             |
| 2-6-4  | 【__invoke】 Verified イベント発火確認                                | 正常系       | 正常なメール確認後                               | Verified イベントが発火される                                                         | `VerifyEmailController::__invoke()`             |
| 2-6-5  | 【__invoke】 間違ったハッシュ値                                       | セキュリティ | 正しくないハッシュ値を含む署名付き URL           | エラータイプ `invalid_link` でメール確認ページにリダイレクト                          | `EmailVerificationRequest::authorize()`         |
| 2-6-6  | 【__invoke】 未認証ユーザーのアクセス                                 | 異常系       | ログインしていない状態でメール確認リンクアクセス | エラータイプ `unauthenticated` でメール確認ページにリダイレクト                       | `EmailVerificationRequest::authorize()`         |
| 2-6-7  | 【__invoke】 無効なパラメータ形式                                     | セキュリティ | 存在しないユーザー ID でアクセス                 | エラータイプ `invalid_link` でメール確認ページにリダイレクト                          | `EmailVerificationRequest::authorize()`         |
| 2-6-8  | 【__invoke】 無効な署名                                               | セキュリティ | 改ざんされた署名付き URL                         | 403 Forbidden エラー                                                                  | `VerifyEmailController::__invoke()`             |
| 2-6-9  | 【__invoke】 署名なしの URL                                           | セキュリティ | 署名が含まれていない URL                         | 403 Forbidden エラー                                                                  | `VerifyEmailController::__invoke()`             |
| 2-6-10 | 【__invoke】 期限切れの署名                                           | セキュリティ | 期限が切れた署名付き URL                         | 403 Forbidden エラー                                                                  | `VerifyEmailController::__invoke()`             |
| 2-6-11 | 【__invoke】 レート制限（1 分間に 6 回超過）                          | セキュリティ | 短時間に 7 回以上のリクエスト                    | 429 Too Many Requests エラー                                                          | `VerifyEmailController::__invoke()`             |
| 2-6-12 | 【__invoke】 レート制限リセット                                       | セキュリティ | 1 分経過後の正常リクエスト                       | 正常処理が実行される                                                                  | `VerifyEmailController::__invoke()`             |
| 2-6-13 | 【__invoke】 markEmailAsVerified() 失敗                               | 異常系       | データベースエラーによりメール確認が失敗         | エラータイプ `database_error` でメール確認ページにリダイレクト                        | `VerifyEmailController::__invoke()`             |
| 2-6-14 | 【__invoke】 Verified イベント発火失敗                                | 異常系       | イベント発火時に例外が発生                       | エラータイプ `verification_failed` でメール確認ページにリダイレクト                   | `VerifyEmailController::__invoke()`             |
| 2-6-15 | 【__invoke】 データベース接続エラー                                   | 異常系       | データベース接続が失敗                           | エラータイプ `database_error` でメール確認ページにリダイレクト                        | `VerifyEmailController::__invoke()`             |
| 2-6-16 | 【__invoke】 ログ出力とエラーリダイレクト確認                         | 異常系       | 例外発生時                                       | 詳細ログが内部記録され、エラータイプのみ外部送信                                      | `VerifyEmailController::__invoke()`             |
| 2-6-17 | 【__invoke】 エラー時のリダイレクト URL 確認                          | 異常系       | 例外発生時                                       | エラータイプのみでメール確認ページにリダイレクト                                      | `VerifyEmailController::__invoke()`             |
| 2-6-18 | 【__invoke】 フロントエンド URL 設定なし                              | 設定エラー   | config('app.frontend_url') が未設定              | 設定エラーまたはデフォルトリダイレクト                                                | `VerifyEmailController::__invoke()`             |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Auth/VerifyEmailControllerTest.php --stop-on-failure
```
