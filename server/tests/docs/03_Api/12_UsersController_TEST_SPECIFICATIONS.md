# UserController テストケース詳細仕様

## 概要

UserController のテストケースの詳細仕様を示します。認証ユーザーと同じグループに属するユーザー一覧取得機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                  | 種別   | 入力条件                             | 期待される出力                                                   | 該当メソッド              |
| ------ | ----------------------------------------- | ------ | ------------------------------------ | ---------------------------------------------------------------- | ------------------------- |
| 3-12-1 | 【一覧取得】 正常なユーザー一覧取得       | 正常系 | 認証済みユーザー                     | HTTP 200 JSON success                                            | `UserController::index()` |
| 3-12-2 | 【一覧取得】 グループ内ユーザー情報の確認 | 正常系 | 正常なユーザー一覧取得後             | 同じグループに属するユーザー情報が正しく取得される               | `UserController::index()` |
| 3-12-3 | 【一覧取得】 ユーザー情報フォーマット確認 | 正常系 | 正常なユーザー一覧取得後             | UserService::formatUserInfo() でフォーマットされた情報が返される | `UserController::index()` |
| 3-12-4 | 【一覧取得】 未認証ユーザー               | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                            | `UserController::index()` |
| 3-12-5 | 【一覧取得】 グループが存在しない         | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                                    | `UserController::index()` |
| 3-12-6 | 【一覧取得】 データベース接続エラー       | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                                   | `UserController::index()` |
| 3-12-7 | 【一覧取得】 UserService 例外             | 異常系 | UserService::formatUserInfo() で例外 | HTTP 500 Internal Server Error                                   | `UserController::index()` |
| 3-12-8 | 【一覧取得】 グループに 1 人のみの場合    | 正常系 | グループにユーザーが 1 人のみ        | 自分自身の情報のみが返される                                     | `UserController::index()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
