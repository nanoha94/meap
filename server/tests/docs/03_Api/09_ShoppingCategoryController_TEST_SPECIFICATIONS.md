# ShoppingCategoryController テストケース詳細仕様

## 概要

ShoppingCategoryController のテストケースの詳細仕様を示します。買い物カテゴリの一覧取得、作成、一括更新、一括削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                       | 種別   | 入力条件                           | 期待される出力                            | 該当メソッド                                  |
| ------ | -------------------------------------------------------------- | ------ | ---------------------------------- | ----------------------------------------- | --------------------------------------------- |
| 3-9-1  | 【一覧取得】 正常な買い物カテゴリ一覧取得                      | 正常系 | 認証済みユーザー                   | HTTP 200 JSON success                     | `ShoppingCategoryController::index()`         |
| 3-9-2  | 【一覧取得】 カテゴリ情報の並び順確認                          | 正常系 | 正常なカテゴリ一覧取得後           | order 順でカテゴリが取得される            | `ShoppingCategoryController::index()`         |
| 3-9-3  | 【一覧取得】 デフォルトカテゴリの確認                          | 正常系 | 正常なカテゴリ一覧取得後           | isDefault フラグが正しく設定される        | `ShoppingCategoryController::index()`         |
| 3-9-4  | 【一覧取得】 レスポンス形式確認                                | 正常系 | 正常なカテゴリ一覧取得後           | 正しい JSON 形式でレスポンスが返される    | `ShoppingCategoryController::index()`         |
| 3-9-5  | 【一覧取得】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `ShoppingCategoryController::index()`         |
| 3-9-6  | 【一覧取得】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `ShoppingCategoryController::index()`         |
| 3-9-7  | 【一覧取得】 データベース接続エラー                            | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `ShoppingCategoryController::index()`         |
| 3-9-8  | 【新規作成】 正常な買い物カテゴリ作成                          | 正常系 | 有効なカテゴリデータを提供         | HTTP 201 Created                          | `ShoppingCategoryController::store()`         |
| 3-9-9  | 【新規作成】 バリデーションエラー（カテゴリ名未入力）          | 異常系 | カテゴリ名が未入力                 | HTTP 422 Validation Error                 | `ShoppingCategoryStoreRequest::rules()`       |
| 3-9-10 | 【新規作成】 バリデーションエラー（カテゴリ名が 255 文字超過） | 異常系 | 256 文字以上のカテゴリ名を提供     | HTTP 422 Validation Error                 | `ShoppingCategoryStoreRequest::rules()`       |
| 3-9-11 | 【新規作成】 バリデーションエラー（order 値が未入力）          | 異常系 | order 値が未入力                   | HTTP 422 Validation Error                 | `ShoppingCategoryStoreRequest::rules()`       |
| 3-9-12 | 【新規作成】 バリデーションエラー（order 値が数値以外）        | 異常系 | order 値が数値以外                 | HTTP 422 Validation Error                 | `ShoppingCategoryStoreRequest::rules()`       |
| 3-9-13 | 【新規作成】 バリデーションエラー（order 値が負の値）          | 異常系 | order 値が 0 未満の負の値          | HTTP 422 Validation Error                 | `ShoppingCategoryStoreRequest::rules()`       |
| 3-9-14 | 【新規作成】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `ShoppingCategoryController::store()`         |
| 3-9-15 | 【新規作成】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `ShoppingCategoryController::store()`         |
| 3-9-16 | 【新規作成】 データベース接続エラー                            | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `ShoppingCategoryController::store()`         |
| 3-9-17 | 【新規作成】 カテゴリ作成失敗                                  | 異常系 | ShoppingCategory::create() が失敗  | HTTP 500 Internal Server Error            | `ShoppingCategoryController::store()`         |
| 3-9-18 | 【一括更新】 正常な買い物カテゴリ一括更新                      | 正常系 | 有効なカテゴリデータ配列を提供     | HTTP 200 JSON success                     | `ShoppingCategoryController::bulkUpdate()`    |
| 3-9-19 | 【一括更新】 一括更新成功メッセージの確認                      | 正常系 | 正常な一括更新後                   | 更新件数を含む適切なメッセージが返される  | `ShoppingCategoryController::bulkUpdate()`    |
| 3-9-20 | 【一括更新】 存在しないカテゴリの更新                          | 異常系 | 存在しない ID を含むデータ配列     | HTTP 404 Not Found                        | `ShoppingCategoryController::bulkUpdate()`    |
| 3-9-21 | 【一括更新】 他グループのカテゴリ更新                          | 異常系 | 他グループの ID を含むデータ配列   | HTTP 404 Not Found                        | `ShoppingCategoryController::bulkUpdate()`    |
| 3-9-22 | 【一括更新】 バリデーションエラー（data 未入力）               | 異常系 | data フィールドが未入力            | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-23 | 【一括更新】 バリデーションエラー（data が配列でない）         | 異常系 | data が配列でない（文字列など）    | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-24 | 【一括更新】 バリデーションエラー（data が空配列）             | 異常系 | data が空配列                      | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-25 | 【一括更新】 バリデーションエラー（id が未入力）               | 異常系 | data.\*.id が未入力                | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-26 | 【一括更新】 バリデーションエラー（id が UUID 形式でない）     | 異常系 | data.\*.id が UUID 形式でない      | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-27 | 【一括更新】 バリデーションエラー（name が未入力）             | 異常系 | data.\*.name が未入力              | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-28 | 【一括更新】 バリデーションエラー（name が文字列でない）       | 異常系 | data.\*.name が文字列でない        | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-29 | 【一括更新】 バリデーションエラー（name が 255 文字超過）      | 異常系 | data.\*.name が 256 文字以上       | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-30 | 【一括更新】 バリデーションエラー（order が未入力）            | 異常系 | data.\*.order が未入力             | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-31 | 【一括更新】 バリデーションエラー（order が数値でない）        | 異常系 | data.\*.order が数値でない         | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-32 | 【一括更新】 バリデーションエラー（order が負の値）            | 異常系 | data.\*.order が 0 未満の負の値    | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-9-33 | 【一括更新】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `ShoppingCategoryController::bulkUpdate()`    |
| 3-9-34 | 【一括更新】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `ShoppingCategoryController::bulkUpdate()`    |
| 3-9-35 | 【一括更新】 データベース接続エラー                            | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkUpdate()`    |
| 3-9-36 | 【一括更新】 カテゴリ更新失敗                                  | 異常系 | ShoppingCategory::update() が失敗  | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkUpdate()`    |
| 3-9-37 | 【一括削除】 正常な買い物カテゴリ一括削除                      | 正常系 | 有効なカテゴリ ID 配列を提供       | HTTP 200 JSON success                     | `ShoppingCategoryController::bulkDestroy()`   |
| 3-9-38 | 【一括削除】 削除後の order 整理確認                           | 正常系 | カテゴリ削除後                     | 残りのカテゴリの order が正しく整理される | `ShoppingCategoryController::bulkDestroy()`   |
| 3-9-39 | 【一括削除】 一括削除成功メッセージの確認                      | 正常系 | 正常な一括削除後                   | 削除件数を含む適切なメッセージが返される  | `ShoppingCategoryController::bulkDestroy()`   |
| 3-9-40 | 【一括削除】 デフォルトカテゴリの保護確認                      | 正常系 | デフォルトカテゴリの削除を試行     | デフォルトカテゴリは削除できない          | `ShoppingCategoryController::bulkDestroy()`   |
| 3-9-41 | 【一括削除】 存在しないカテゴリの削除                          | 異常系 | 存在しない ID を含む配列を提供     | HTTP 404 Not Found                        | `ShoppingCategoryController::bulkDestroy()`   |
| 3-9-42 | 【一括削除】 他グループのカテゴリ削除                          | 異常系 | 他グループの ID を含む配列を提供   | HTTP 404 Not Found                        | `ShoppingCategoryController::bulkDestroy()`   |
| 3-9-43 | 【一括削除】 バリデーションエラー（IDs 未入力）                | 異常系 | ids フィールドが未入力             | HTTP 422 Validation Error                 | `ShoppingCategoryBulkDestroyRequest::rules()` |
| 3-9-44 | 【一括削除】 バリデーションエラー（IDs が配列でない）          | 異常系 | ids が配列でない（文字列など）     | HTTP 422 Validation Error                 | `ShoppingCategoryBulkDestroyRequest::rules()` |
| 3-9-45 | 【一括削除】 バリデーションエラー（IDs が空配列）              | 異常系 | ids が空配列（min:1 違反）         | HTTP 422 Validation Error                 | `ShoppingCategoryBulkDestroyRequest::rules()` |
| 3-9-46 | 【一括削除】 バリデーションエラー（ID が UUID 形式でない）     | 異常系 | ids.\* が UUID 形式でない          | HTTP 422 Validation Error                 | `ShoppingCategoryBulkDestroyRequest::rules()` |
| 3-9-47 | 【一括削除】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `ShoppingCategoryController::bulkDestroy()`   |
| 3-9-48 | 【一括削除】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `ShoppingCategoryController::bulkDestroy()`   |
| 3-9-49 | 【一括削除】 データベース接続エラー                            | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkDestroy()`   |
| 3-9-50 | 【一括削除】 カテゴリ削除失敗                                  | 異常系 | ShoppingCategory::delete() が失敗  | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkDestroy()`   |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
