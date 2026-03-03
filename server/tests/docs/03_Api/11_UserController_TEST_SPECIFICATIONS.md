# UserController テストケース詳細仕様

## 概要

UserController のテストケースの詳細仕様を示します。認証ユーザーと同じグループに属するユーザー一覧取得機能、認証ユーザー情報取得機能、および認証ユーザーのプロフィール更新機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID      | テスト名                                                            | 種別   | 入力条件                                             | 期待される出力                                                       | 該当メソッド                   |
| ------- | ------------------------------------------------------------------- | ------ | ---------------------------------------------------- | -------------------------------------------------------------------- | ------------------------------ |
| 3-11-1  | 【一覧取得】 正常なユーザー一覧取得                                 | 正常系 | 認証済みユーザー                                     | HTTP 200 JSON success                                                | `UserController::index()`      |
| 3-11-2  | 【一覧取得】 グループ内ユーザー情報の確認                           | 正常系 | 正常なユーザー一覧取得後                             | 同じグループに属するユーザー情報が正しく取得される                   | `UserController::index()`      |
| 3-11-3  | 【一覧取得】 ユーザー情報フォーマット確認                           | 正常系 | 正常なユーザー一覧取得後                             | UserService::formatUserInfo() でフォーマットされた情報が返される     | `UserController::index()`      |
| 3-11-4  | 【一覧取得】 グループに 1 人のみの場合                              | 正常系 | グループにユーザーが 1 人のみ                        | 自分自身の情報のみが返される                                         | `UserController::index()`      |
| 3-11-5  | 【一覧取得】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                             | HTTP 401 Unauthorized                                                | `UserController::index()`      |
| 3-11-6  | 【一覧取得】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない                   | HTTP 422 Unprocessable Entity                                        | `UserController::index()`      |
| 3-11-7  | 【一覧取得】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                               | HTTP 500 Internal Server Error                                       | `UserController::index()`      |
| 3-11-8  | 【一覧取得】 UserService 例外                                       | 異常系 | UserService::formatUserInfo() で例外                 | HTTP 500 Internal Server Error                                       | `UserController::index()`      |
| 3-11-9  | 【詳細取得】 正常なユーザー情報取得                                 | 正常系 | 認証済みユーザー（メール認証済み）                   | HTTP 200 JSON success/message/data 構造                              | `UserController::show()`       |
| 3-11-10 | 【詳細取得】 メール未認証ユーザー                                   | 正常系 | 認証済みユーザー（メール未認証）                     | HTTP 200 JSON success/message/data 構造（email_verified_at は null） | `UserController::show()`       |
| 3-11-11 | 【詳細取得】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                             | HTTP 401 Unauthorized                                                | `UserController::show()`       |
| 3-11-12 | 【更新】 名前のみ更新                                               | 正常系 | 認証済みユーザー、name を指定                        | HTTP 200 JSON success、name が更新される。avatar_image_id は送信されないため null になる | `UserController::update()`     |
| 3-11-13 | 【更新】 アバター画像IDのみ更新                                     | 正常系 | 認証済みユーザー、avatar_image_id を指定             | HTTP 200 JSON success、avatar_image_id が更新される                  | `UserController::update()`     |
| 3-11-14 | 【更新】 名前とアバター画像IDを同時に更新                           | 正常系 | 認証済みユーザー、name と avatar_image_id を指定     | HTTP 200 JSON success、両方が更新される                              | `UserController::update()`     |
| 3-11-15 | 【更新】 アバター画像IDをnullに設定（削除）                         | 正常系 | 認証済みユーザー、avatar_image_id に null を指定     | HTTP 200 JSON success、avatar_image_id が null になる                | `UserController::update()`     |
| 3-11-16 | 【更新】 アバター画像IDキーを省略した場合、nullになる               | 正常系 | 認証済みユーザー、アバターありで avatar_image_id を送らない | HTTP 200 JSON success、avatar_image_id が null になる                | `UserController::update()`     |
| 3-11-17 | 【更新】 バリデーションエラー（name が文字列でない）                | 異常系 | 認証済みユーザー、name が文字列でない                | HTTP 422 Validation Error                                            | `UserUpdateRequest::rules()`   |
| 3-11-18 | 【更新】 バリデーションエラー（name が 255 文字超過）               | 異常系 | 認証済みユーザー、name が 256 文字以上               | HTTP 422 Validation Error                                            | `UserUpdateRequest::rules()`   |
| 3-11-19 | 【更新】 バリデーションエラー（avatar_image_id が UUID 形式でない） | 異常系 | 認証済みユーザー、avatar_image_id が UUID 形式でない | HTTP 422 Validation Error                                            | `UserUpdateRequest::rules()`   |
| 3-11-20 | 【更新】 avatar_image_id が存在しない画像ID                         | 異常系 | 認証済みユーザー、存在しない画像ID を指定            | HTTP 404 Not Found                                                   | `UserService::updateProfile()` |
| 3-11-21 | 【更新】 未認証ユーザー                                             | 異常系 | 認証されていないユーザー                             | HTTP 401 Unauthorized                                                | `UserController::update()`     |
| 3-11-22 | 【更新】 UserService 例外                                           | 異常系 | UserService::updateProfile() で例外                  | HTTP 500 Internal Server Error                                       | `UserController::update()`     |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
