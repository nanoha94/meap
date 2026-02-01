# ShoppingCategoryController テストケース詳細仕様

## 概要

ShoppingCategoryController のテストケースの詳細仕様を示します。買い物カテゴリの一覧取得、一括作成、一括更新、一括削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                          | 種別   | 入力条件                           | 期待される出力                            | 該当メソッド                                  |
| ------ | ----------------------------------------------------------------- | ------ | ---------------------------------- | ----------------------------------------- | --------------------------------------------- |
| 3-8-1  | 【一覧取得】 正常な買い物カテゴリ一覧取得                         | 正常系 | 認証済みユーザー                   | HTTP 200 JSON success                     | `ShoppingCategoryController::index()`         |
| 3-8-2  | 【一覧取得】 カテゴリ情報の並び順確認                             | 正常系 | 正常なカテゴリ一覧取得後           | order 順でカテゴリが取得される            | `ShoppingCategoryController::index()`         |
| 3-8-3  | 【一覧取得】 デフォルトカテゴリの確認                             | 正常系 | 正常なカテゴリ一覧取得後           | isDefault フラグが正しく設定される        | `ShoppingCategoryController::index()`         |
| 3-8-4  | 【一覧取得】 レスポンス形式確認                                   | 正常系 | 正常なカテゴリ一覧取得後           | 正しい JSON 形式でレスポンスが返される    | `ShoppingCategoryController::index()`         |
| 3-8-5  | 【一覧取得】 未認証ユーザー                                       | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `ShoppingCategoryController::index()`         |
| 3-8-6  | 【一覧取得】 グループが存在しない                                 | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `ShoppingCategoryController::index()`         |
| 3-8-7  | 【一覧取得】 データベース接続エラー                               | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `ShoppingCategoryController::index()`         |
| 3-8-8  | 【一括作成】 正常な買い物カテゴリ一括作成                         | 正常系 | 有効なカテゴリデータ配列を提供     | HTTP 201 Created                          | `ShoppingCategoryController::bulkStore()`     |
| 3-8-9  | 【一括作成】 バリデーションエラー（data 未入力）                  | 異常系 | data フィールドが未入力            | HTTP 422 Validation Error                 | `ShoppingCategoryBulkStoreRequest::rules()`   |
| 3-8-10 | 【一括作成】 バリデーションエラー（data が配列でない）            | 異常系 | data が配列でない（文字列など）    | HTTP 422 Validation Error                 | `ShoppingCategoryBulkStoreRequest::rules()`   |
| 3-8-11 | 【一括作成】 バリデーションエラー（data が空配列）                | 異常系 | data が空配列                      | HTTP 422 Validation Error                 | `ShoppingCategoryBulkStoreRequest::rules()`   |
| 3-8-12 | 【一括作成】 バリデーションエラー（data.\*.name 未入力）          | 異常系 | data.\*.name が未入力              | HTTP 422 Validation Error                 | `ShoppingCategoryBulkStoreRequest::rules()`   |
| 3-8-13 | 【一括作成】 バリデーションエラー（data.\*.name が文字列でない）  | 異常系 | data.\*.name が文字列でない        | HTTP 422 Validation Error                 | `ShoppingCategoryBulkStoreRequest::rules()`   |
| 3-8-14 | 【一括作成】 バリデーションエラー（data.\*.name が 255 文字超過） | 異常系 | data.\*.name が 256 文字以上       | HTTP 422 Validation Error                 | `ShoppingCategoryBulkStoreRequest::rules()`   |
| 3-8-15 | 【一括作成】 バリデーションエラー（data.\*.order 未入力）         | 異常系 | data.\*.order が未入力             | HTTP 422 Validation Error                 | `ShoppingCategoryBulkStoreRequest::rules()`   |
| 3-8-16 | 【一括作成】 バリデーションエラー（data.\*.order が整数でない）   | 異常系 | data.\*.order が整数でない         | HTTP 422 Validation Error                 | `ShoppingCategoryBulkStoreRequest::rules()`   |
| 3-8-17 | 【一括作成】 バリデーションエラー（data.\*.order が負の値）       | 異常系 | data.\*.order が 0 未満の負の値    | HTTP 422 Validation Error                 | `ShoppingCategoryBulkStoreRequest::rules()`   |
| 3-8-18 | 【一括作成】 未認証ユーザー                                       | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `ShoppingCategoryController::bulkStore()`     |
| 3-8-19 | 【一括作成】 グループが存在しない                                 | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `ShoppingCategoryController::bulkStore()`     |
| 3-8-20 | 【一括作成】 データベース接続エラー                               | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkStore()`     |
| 3-8-21 | 【一括作成】 カテゴリ作成失敗                                     | 異常系 | ShoppingCategory::create() が失敗  | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkStore()`     |
| 3-8-22 | 【一括更新】 正常な買い物カテゴリ一括更新                         | 正常系 | 有効なカテゴリデータ配列を提供     | HTTP 200 JSON success                     | `ShoppingCategoryController::bulkUpdate()`    |
| 3-8-23 | 【一括更新】 一括更新成功メッセージの確認                         | 正常系 | 正常な一括更新後                   | 更新件数を含む適切なメッセージが返される  | `ShoppingCategoryController::bulkUpdate()`    |
| 3-8-24 | 【一括更新】 存在しないカテゴリの更新                             | 異常系 | 存在しない ID を含むデータ配列     | HTTP 404 Not Found                        | `ShoppingCategoryController::bulkUpdate()`    |
| 3-8-25 | 【一括更新】 他グループのカテゴリ更新                             | 異常系 | 他グループの ID を含むデータ配列   | HTTP 404 Not Found                        | `ShoppingCategoryController::bulkUpdate()`    |
| 3-8-26 | 【一括更新】 バリデーションエラー（data 未入力）                  | 異常系 | data フィールドが未入力            | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-27 | 【一括更新】 バリデーションエラー（data が配列でない）            | 異常系 | data が配列でない（文字列など）    | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-28 | 【一括更新】 バリデーションエラー（data が空配列）                | 異常系 | data が空配列                      | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-29 | 【一括更新】 バリデーションエラー（id が未入力）                  | 異常系 | data.\*.id が未入力                | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-30 | 【一括更新】 バリデーションエラー（id が UUID 形式でない）        | 異常系 | data.\*.id が UUID 形式でない      | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-31 | 【一括更新】 バリデーションエラー（name が未入力）                | 異常系 | data.\*.name が未入力              | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-32 | 【一括更新】 バリデーションエラー（name が文字列でない）          | 異常系 | data.\*.name が文字列でない        | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-33 | 【一括更新】 バリデーションエラー（name が 255 文字超過）         | 異常系 | data.\*.name が 256 文字以上       | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-34 | 【一括更新】 バリデーションエラー（order が未入力）               | 異常系 | data.\*.order が未入力             | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-35 | 【一括更新】 バリデーションエラー（order が数値でない）           | 異常系 | data.\*.order が数値でない         | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-36 | 【一括更新】 バリデーションエラー（order が負の値）               | 異常系 | data.\*.order が 0 未満の負の値    | HTTP 422 Validation Error                 | `ShoppingCategoryBulkUpdateRequest::rules()`  |
| 3-8-37 | 【一括更新】 未認証ユーザー                                       | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `ShoppingCategoryController::bulkUpdate()`    |
| 3-8-38 | 【一括更新】 グループが存在しない                                 | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `ShoppingCategoryController::bulkUpdate()`    |
| 3-8-39 | 【一括更新】 データベース接続エラー                               | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkUpdate()`    |
| 3-8-40 | 【一括更新】 カテゴリ更新失敗                                     | 異常系 | ShoppingCategory::update() が失敗  | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkUpdate()`    |
| 3-8-41 | 【一括削除】 正常な買い物カテゴリ一括削除                         | 正常系 | 有効なカテゴリ ID 配列を提供       | HTTP 200 JSON success                     | `ShoppingCategoryController::bulkDestroy()`   |
| 3-8-42 | 【一括削除】 削除後の order 整理確認                              | 正常系 | カテゴリ削除後                     | 残りのカテゴリの order が正しく整理される | `ShoppingCategoryController::bulkDestroy()`   |
| 3-8-43 | 【一括削除】 一括削除成功メッセージの確認                         | 正常系 | 正常な一括削除後                   | 削除件数を含む適切なメッセージが返される  | `ShoppingCategoryController::bulkDestroy()`   |
| 3-8-44 | 【一括削除】 デフォルトカテゴリの保護確認                         | 正常系 | デフォルトカテゴリの削除を試行     | デフォルトカテゴリは削除できない          | `ShoppingCategoryController::bulkDestroy()`   |
| 3-8-45 | 【一括削除】 存在しないカテゴリの削除                             | 異常系 | 存在しない ID を含む配列を提供     | HTTP 404 Not Found                        | `ShoppingCategoryController::bulkDestroy()`   |
| 3-8-46 | 【一括削除】 他グループのカテゴリ削除                             | 異常系 | 他グループの ID を含む配列を提供   | HTTP 404 Not Found                        | `ShoppingCategoryController::bulkDestroy()`   |
| 3-8-47 | 【一括削除】 バリデーションエラー（IDs 未入力）                   | 異常系 | ids フィールドが未入力             | HTTP 422 Validation Error                 | `ShoppingCategoryBulkDestroyRequest::rules()` |
| 3-8-48 | 【一括削除】 バリデーションエラー（IDs が配列でない）             | 異常系 | ids が配列でない（文字列など）     | HTTP 422 Validation Error                 | `ShoppingCategoryBulkDestroyRequest::rules()` |
| 3-8-49 | 【一括削除】 バリデーションエラー（IDs が空配列）                 | 異常系 | ids が空配列（min:1 違反）         | HTTP 422 Validation Error                 | `ShoppingCategoryBulkDestroyRequest::rules()` |
| 3-8-50 | 【一括削除】 バリデーションエラー（ID が UUID 形式でない）        | 異常系 | ids.\* が UUID 形式でない          | HTTP 422 Validation Error                 | `ShoppingCategoryBulkDestroyRequest::rules()` |
| 3-8-51 | 【一括削除】 未認証ユーザー                                       | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `ShoppingCategoryController::bulkDestroy()`   |
| 3-8-52 | 【一括削除】 グループが存在しない                                 | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `ShoppingCategoryController::bulkDestroy()`   |
| 3-8-53 | 【一括削除】 データベース接続エラー                               | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkDestroy()`   |
| 3-8-54 | 【一括削除】 カテゴリ削除失敗                                     | 異常系 | ShoppingCategory::delete() が失敗  | HTTP 500 Internal Server Error            | `ShoppingCategoryController::bulkDestroy()`   |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
