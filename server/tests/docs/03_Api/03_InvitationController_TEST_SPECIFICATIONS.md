# InvitationController テストケース詳細仕様

## 概要

InvitationController のテストケースの詳細仕様を示します。グループへの招待トークン生成、詳細取得、グループ参加機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                        | 種別   | 入力条件                                       | 期待される出力                           | 該当メソッド                    |
| ------ | ----------------------------------------------- | ------ | ---------------------------------------------- | ---------------------------------------- | ------------------------------- |
| 3-3-1  | 【トークン生成】 正常な招待トークン生成         | 正常系 | 認証済みユーザー                               | HTTP 201 Created                         | `InvitationController::store()` |
| 3-3-2  | 【トークン生成】 トークン有効期限設定確認       | 正常系 | 正常なトークン生成後                           | 1 時間後の有効期限が設定される           | `InvitationController::store()` |
| 3-3-3  | 【トークン生成】 未認証ユーザー                 | 異常系 | 認証されていないユーザー                       | HTTP 401 Unauthorized                    | `InvitationController::store()` |
| 3-3-4  | 【トークン生成】 グループが存在しない           | 異常系 | ユーザーにグループが紐づいていない             | HTTP 422 Unprocessable Entity            | `InvitationController::store()` |
| 3-3-5  | 【トークン生成】 データベース接続エラー         | 異常系 | データベース接続が失敗                         | HTTP 500 Internal Server Error           | `InvitationController::store()` |
| 3-3-6  | 【トークン生成】 トークン生成失敗               | 異常系 | InvitationToken::createWithExpiration() が失敗 | HTTP 500 Internal Server Error           | `InvitationController::store()` |
| 3-3-7  | 【トークン生成】 トークン衝突時の再試行成功     | 正常系 | 5 回以内の試行で重複しないトークンを生成       | HTTP 201 Created                         | `InvitationController::store()` |
| 3-3-8  | 【トークン生成】 最大試行回数超過による失敗     | 異常系 | 5 回試行してもすべてトークンが衝突             | HTTP 500 Internal Server Error           | `InvitationController::store()` |
| 3-3-9  | 【トークン詳細取得】 正常な招待トークン詳細取得 | 正常系 | 有効なトークンを提供                           | HTTP 200 JSON success                    | `InvitationController::show()`  |
| 3-3-10 | 【トークン詳細取得】 招待者情報の取得確認       | 正常系 | 有効なトークンで招待者情報を取得               | 招待者の情報が正しく取得される           | `InvitationController::show()`  |
| 3-3-11 | 【トークン詳細取得】 トークン詳細レスポンス確認 | 正常系 | 有効なトークンでレスポンス確認                 | トークン、有効期限、招待者情報が含まれる | `InvitationController::show()`  |
| 3-3-12 | 【トークン詳細取得】 未認証ユーザー             | 異常系 | 認証されていないユーザー                       | HTTP 401 Unauthorized                    | `InvitationController::show()`  |
| 3-3-13 | 【トークン詳細取得】 無効なトークンでの詳細取得 | 異常系 | 無効なトークンを提供                           | HTTP 404 Not Found                       | `InvitationController::show()`  |
| 3-3-14 | 【トークン詳細取得】 ハッシュチェック失敗       | 異常系 | トークンのハッシュチェックが失敗               | HTTP 404 Not Found                       | `InvitationController::show()`  |
| 3-3-15 | 【トークン詳細取得】 データベース接続エラー     | 異常系 | データベース接続が失敗                         | HTTP 500 Internal Server Error           | `InvitationController::show()`  |
| 3-3-16 | 【グループ参加】 正常なグループ参加             | 正常系 | 有効なトークンで参加                           | HTTP 200 JSON success                    | `InvitationController::join()`  |
| 3-3-17 | 【グループ参加】 グループサイズ更新確認         | 正常系 | 正常なグループ参加後                           | グループサイズが正しく更新される         | `InvitationController::join()`  |
| 3-3-18 | 【グループ参加】 空グループの削除確認           | 正常系 | 最後のユーザーが参加後（元グループが 1 人）    | 元のグループが削除される                 | `InvitationController::join()`  |
| 3-3-19 | 【グループ参加】 元グループの保持確認           | 正常系 | 元のグループに複数人が所属                     | 元のグループは削除されない               | `InvitationController::join()`  |
| 3-3-20 | 【グループ参加】 未認証ユーザー                 | 異常系 | 認証されていないユーザー                       | HTTP 401 Unauthorized                    | `InvitationController::join()`  |
| 3-3-21 | 【グループ参加】 無効なトークンでの参加         | 異常系 | DB に存在しないトークンを提供                  | HTTP 404 Not Found                       | `InvitationController::join()`  |
| 3-3-22 | 【グループ参加】 ハッシュチェック失敗           | 異常系 | トークンのハッシュチェックが失敗               | HTTP 404 Not Found                       | `InvitationController::join()`  |
| 3-3-23 | 【グループ参加】 有効期限切れトークンでの参加   | 異常系 | 有効期限切れのトークンを提供                   | HTTP 410 Gone                            | `InvitationController::join()`  |
| 3-3-24 | 【グループ参加】 自分自身のトークンでの参加     | 異常系 | 招待者本人が参加を試行                         | HTTP 403 Forbidden                       | `InvitationController::join()`  |
| 3-3-25 | 【グループ参加】 既に同じグループにいる場合     | 異常系 | 既に同じグループに所属                         | HTTP 409 Conflict                        | `InvitationController::join()`  |
| 3-3-26 | 【グループ参加】 他のグループに所属している場合 | 異常系 | 他のグループに所属している                     | HTTP 409 Conflict                        | `InvitationController::join()`  |
| 3-3-27 | 【グループ参加】 既存データがある場合の参加     | 異常系 | 既存のデータがある状態で参加                   | HTTP 409 Conflict                        | `InvitationController::join()`  |
| 3-3-28 | 【グループ参加】 データベース接続エラー         | 異常系 | データベース接続が失敗                         | HTTP 500 Internal Server Error           | `InvitationController::join()`  |
| 3-3-29 | 【グループ参加】 GroupUserMapping 作成失敗      | 異常系 | GroupUserMapping::create() が失敗              | HTTP 500 Internal Server Error           | `InvitationController::join()`  |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
