# RegisteredUserController テストケース詳細仕様

## 概要

このドキュメントは、RegisteredUserController のテストケースの詳細仕様を示します。ユーザー登録機能を検証し、システムの安定性と安全性を確保します。登録時には、ユーザー作成、グループ作成、デフォルトデータの自動設定、自動ログインまでの一連の処理を包括的にテストします。

## テストケース一覧表

| ID     | テスト名                                                             | 種別   | 入力条件                             | 期待される出力                                                  | 該当メソッド                         |
| ------ | -------------------------------------------------------------------- | ------ | ------------------------------------ | --------------------------------------------------------------- | ------------------------------------ |
| 2-5-1  | 【store】 正常なユーザー登録                                         | 正常系 | 有効な名前、メール、パスワードを提供 | ユーザーが作成され、グループが作成され、自動ログインされる      | `RegisteredUserController::store()` |
| 2-5-2  | 【store】 グループとユーザーの関連付け確認                           | 正常系 | 正常なユーザー登録後                 | GroupUserMapping が作成され、ユーザーとグループが関連付けられる | `RegisteredUserController::store()` |
| 2-5-3  | 【store】 デフォルトデータの自動作成確認                             | 正常系 | 正常なユーザー登録後                 | グループに関連するデフォルトデータが作成される                  | `RegisteredUserController::store()` |
| 2-5-4  | 【store】 自動ログイン処理確認                                       | 正常系 | 正常なユーザー登録後                 | ユーザーが自動的にログイン状態になる                            | `RegisteredUserController::store()` |
| 2-5-5  | 【store】 セッション再生成確認                                       | 正常系 | 正常なユーザー登録後                 | セッション ID が再生成される                                    | `RegisteredUserController::store()` |
| 2-5-6  | 【store】 メール認証イベント発火確認                                 | 正常系 | 正常なユーザー登録後                 | Registered イベントが発火される                                 | `RegisteredUserController::store()` |
| 2-5-7  | 【store】 アバターシード生成確認                                     | 正常系 | 正常なユーザー登録後                 | ユニークなアバターシードが生成される                            | `RegisteredUserController::store()` |
| 2-5-8  | 【store】 レスポンス形式確認                                         | 正常系 | 正常なユーザー登録後                 | 正しい JSON 形式でレスポンスが返される                          | `RegisteredUserController::store()` |
| 2-5-9  | 【store】 成功メッセージの国際化確認                                 | 正常系 | 正常なユーザー登録後                 | 適切な言語の成功メッセージが返される                            | `RegisteredUserController::store()` |
| 2-5-10 | 【store】 レート制限（1 分間に 6 回超過）                            | 異常系 | 短時間に 7 回以上の登録リクエスト    | 429 Too Many Requests エラー（7 回目）                          | `RegisteredUserController::store()` |
| 2-5-11 | 【store】 バリデーションエラー（名前未入力）                         | 異常系 | 名前が未入力                         | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-12 | 【store】 バリデーションエラー（メールアドレス未入力）               | 異常系 | メールアドレスが未入力               | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-13 | 【store】 バリデーションエラー（パスワード未入力）                   | 異常系 | パスワードが未入力                   | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-14 | 【store】 バリデーションエラー（パスワード確認未入力）               | 異常系 | パスワード確認が未入力               | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-15 | 【store】 バリデーションエラー（パスワード確認不一致）               | 異常系 | パスワードと確認用パスワードが不一致 | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-16 | 【store】 バリデーションエラー（無効なメール形式）                   | 異常系 | 無効な形式のメールアドレスを提供     | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-17 | 【store】 バリデーションエラー（メールアドレスが大文字）             | 異常系 | 大文字を含むメールアドレスを提供     | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-18 | 【store】 バリデーションエラー（名前が 255 文字超過）                | 異常系 | 256 文字以上の名前を提供             | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-19 | 【store】 バリデーションエラー（メールアドレスが 255 文字超過）      | 異常系 | 256 文字以上のメールアドレスを提供   | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-20 | 【store】 バリデーションエラー（パスワードが短すぎる）               | 異常系 | 8 文字未満のパスワードを提供         | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-21 | 【store】 バリデーションエラー（パスワードに英字が含まれない）       | 異常系 | 英字を含まないパスワードを提供       | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-22 | 【store】 バリデーションエラー（パスワードに数字が含まれない）       | 異常系 | 数字を含まないパスワードを提供       | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-23 | 【store】 バリデーションエラー（パスワードに記号が含まれない）      | 異常系 | 記号を含まないパスワードを提供       | バリデーションエラーが返される (422)                            | `RegisteredUserController::store()` |
| 2-5-24 | 【store】 バリデーションエラー（重複メールアドレス）                | 異常系 | 既に存在するメールアドレスを提供     | 422。メール列挙を防ぐ汎用メッセージが返される                 | `RegisteredUserController::store()` |
| 2-5-25 | 【store】 既にログイン済みのユーザー                                 | 異常系 | 認証済みユーザーが登録を試行         | エラーメッセージが返される (409)                                | `RegisteredUserController::store()` |
| 2-5-26 | 【store】 データベース接続エラー                                     | 異常系 | データベース接続が失敗               | エラーメッセージが返される (500)                                | `RegisteredUserController::store()` |
| 2-5-27 | 【store】 トランザクション処理中の例外                               | 異常系 | ユーザー作成中に例外が発生           | ロールバックされ、エラーメッセージが返される (500)              | `RegisteredUserController::store()` |
| 2-5-28 | 【store】 グループ作成失敗                                           | 異常系 | Group::createGroup() が失敗          | エラーメッセージが返される (500)                                | `RegisteredUserController::store()` |
| 2-5-29 | 【store】 GroupUserMapping 作成失敗                                  | 異常系 | GroupUserMapping::create() が失敗    | エラーメッセージが返される (500)                                | `RegisteredUserController::store()` |
| 2-5-30 | 【store】 アバターシード生成失敗                                     | 異常系 | generateUniqueCustomId() が失敗      | エラーメッセージが返される (500)                                | `RegisteredUserController::store()` |
| 2-5-31 | 【store】 メール認証イベント発火失敗                                 | 異常系 | Registered イベントの発火が失敗      | エラーメッセージが返される (500)                                | `RegisteredUserController::store()` |
| 2-5-32 | 【store】 自動ログイン失敗                                           | 異常系 | Auth::login() が失敗                 | エラーメッセージが返される (500)                                | `RegisteredUserController::store()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Auth/RegisteredUserControllerTest.php --stop-on-failure
```
