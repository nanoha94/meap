# RecipeCategoryController テストケース詳細仕様

## 概要

RecipeCategoryController のテストケースの詳細仕様を示します。料理カテゴリの一括作成、一括更新、一括削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                           | 種別   | 入力条件                              | 期待される出力                                 | 該当メソッド                                |
| ------ | ------------------------------------------------------------------ | ------ | ------------------------------------- | ---------------------------------------------- | ------------------------------------------- |
| 3-6-1  | 【一覧取得】 正常な料理カテゴリ一覧取得                            | 正常系 | 認証済みユーザー                      | HTTP 200 料理カテゴリ一覧を取得                | `RecipeCategoryController::index()`         |
| 3-6-2  | 【一覧取得】 レスポンス形式確認                                    | 正常系 | 正常な一覧取得後                      | 正しい JSON 形式でレスポンスが返される         | `RecipeCategoryController::index()`         |
| 3-6-3  | 【一覧取得】 order 順での取得確認                                  | 正常系 | 複数の料理カテゴリが存在              | order フィールド順に料理カテゴリが取得される   | `RecipeCategoryController::index()`         |
| 3-6-4  | 【一覧取得】 空のリスト取得                                        | 正常系 | 料理カテゴリが 0 件                   | HTTP 200 空の配列が返される                    | `RecipeCategoryController::index()`         |
| 3-6-5  | 【一覧取得】 他グループの料理カテゴリは取得されない                | 正常系 | 他グループの料理カテゴリが存在        | 自グループの料理カテゴリのみが取得される       | `RecipeCategoryController::index()`         |
| 3-6-6  | 【一覧取得】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー              | HTTP 401 Unauthorized                          | `RecipeCategoryController::index()`         |
| 3-6-7  | 【一覧取得】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない    | HTTP 422 Unprocessable Entity                  | `RecipeCategoryController::index()`         |
| 3-6-8  | 【一覧取得】 データベース接続エラー                                | 異常系 | データベース接続が失敗                | HTTP 500 Internal Server Error                 | `RecipeCategoryController::index()`         |
| 3-6-9  | 【一括作成】 正常な料理カテゴリ一括作成                            | 正常系 | 有効な data 配列（name, order）を提供 | HTTP 201 Created                               | `RecipeCategoryController::bulkStore()`     |
| 3-6-10 | 【一括作成】 レスポンス形式確認                                    | 正常系 | 正常な一括作成後                      | 正しい JSON 形式でレスポンスが返される         | `RecipeCategoryController::bulkStore()`     |
| 3-6-11 | 【一括作成】 バリデーションエラー（料理カテゴリ名未入力）          | 異常系 | data.\*.name が未入力                 | HTTP 422 Validation Error                      | `RecipeCategoryBulkStoreRequest::rules()`   |
| 3-6-12 | 【一括作成】 バリデーションエラー（料理カテゴリ名が 255 文字超過） | 異常系 | data.\*.name が 256 文字以上          | HTTP 422 Validation Error                      | `RecipeCategoryBulkStoreRequest::rules()`   |
| 3-6-13 | 【一括作成】 バリデーションエラー（order 値が未入力）              | 異常系 | data.\*.order が未入力                | HTTP 422 Validation Error                      | `RecipeCategoryBulkStoreRequest::rules()`   |
| 3-6-14 | 【一括作成】 バリデーションエラー（order 値が数値以外）            | 異常系 | data.\*.order が数値以外              | HTTP 422 Validation Error                      | `RecipeCategoryBulkStoreRequest::rules()`   |
| 3-6-15 | 【一括作成】 バリデーションエラー（order 値が負の値）              | 異常系 | data.\*.order が 0 未満の負の値       | HTTP 422 Validation Error                      | `RecipeCategoryBulkStoreRequest::rules()`   |
| 3-6-16 | 【一括作成】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー              | HTTP 401 Unauthorized                          | `RecipeCategoryController::bulkStore()`     |
| 3-6-17 | 【一括作成】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない    | HTTP 422 Unprocessable Entity                  | `RecipeCategoryController::bulkStore()`     |
| 3-6-18 | 【一括作成】 データベース接続エラー                                | 異常系 | データベース接続が失敗                | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkStore()`     |
| 3-6-19 | 【一括作成】 料理カテゴリ作成失敗                                  | 異常系 | 一括作成処理が失敗                    | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkStore()`     |
| 3-6-20 | 【一括更新】 正常な料理カテゴリ一括更新                            | 正常系 | 有効な料理カテゴリデータ配列を提供    | HTTP 200 JSON success                          | `RecipeCategoryController::bulkUpdate()`    |
| 3-6-21 | 【一括更新】 一括更新成功メッセージの確認                          | 正常系 | 正常な一括更新後                      | 更新件数を含む適切なメッセージが返される       | `RecipeCategoryController::bulkUpdate()`    |
| 3-6-22 | 【一括更新】 一括更新後のデータ取得確認                            | 正常系 | 正常な一括更新後                      | 更新された料理カテゴリデータが正しく取得される | `RecipeCategoryController::bulkUpdate()`    |
| 3-6-23 | 【一括更新】 存在しない料理カテゴリの更新                          | 異常系 | 存在しない ID を含むデータ配列を提供  | HTTP 404 Not Found                             | `RecipeCategoryController::bulkUpdate()`    |
| 3-6-24 | 【一括更新】 他グループの料理カテゴリ更新                          | 異常系 | 他グループの料理カテゴリ ID を提供    | HTTP 404 Not Found                             | `RecipeCategoryController::bulkUpdate()`    |
| 3-6-25 | 【一括更新】 バリデーションエラー（data 未入力）                   | 異常系 | data フィールドが未入力               | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-26 | 【一括更新】 バリデーションエラー（data が配列でない）             | 異常系 | data が配列でない（文字列など）       | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-27 | 【一括更新】 バリデーションエラー（data が空配列）                 | 異常系 | data が空配列                         | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-28 | 【一括更新】 バリデーションエラー（id が未入力）                   | 異常系 | data.\*.id が未入力                   | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-29 | 【一括更新】 バリデーションエラー（id が UUID 形式でない）         | 異常系 | data.\*.id が UUID 形式でない         | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-30 | 【一括更新】 バリデーションエラー（name が未入力）                 | 異常系 | data.\*.name が未入力                 | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-31 | 【一括更新】 バリデーションエラー（name が文字列でない）           | 異常系 | data.\*.name が文字列でない（数値等） | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-32 | 【一括更新】 バリデーションエラー（order が未入力）                | 異常系 | data.\*.order が未入力                | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-33 | 【一括更新】 バリデーションエラー（order が数値でない）            | 異常系 | data.\*.order が数値でない            | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-34 | 【一括更新】 バリデーションエラー（order が負の値）                | 異常系 | data.\*.order が 0 未満の負の値       | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-6-35 | 【一括更新】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー              | HTTP 401 Unauthorized                          | `RecipeCategoryController::bulkUpdate()`    |
| 3-6-36 | 【一括更新】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない    | HTTP 422 Unprocessable Entity                  | `RecipeCategoryController::bulkUpdate()`    |
| 3-6-37 | 【一括更新】 データベース接続エラー                                | 異常系 | データベース接続が失敗                | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkUpdate()`    |
| 3-6-38 | 【一括更新】 料理カテゴリ更新失敗                                  | 異常系 | RecipeCategory::update() が失敗       | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkUpdate()`    |
| 3-6-39 | 【一括削除】 正常な料理カテゴリ一括削除                            | 正常系 | 有効な料理カテゴリ ID 配列を提供      | HTTP 200 JSON success                          | `RecipeCategoryController::bulkDestroy()`   |
| 3-6-40 | 【一括削除】 一括削除成功メッセージの確認                          | 正常系 | 正常な一括削除後                      | 削除件数を含む適切なメッセージが返される       | `RecipeCategoryController::bulkDestroy()`   |
| 3-6-41 | 【一括削除】 削除後の order 整理確認                               | 正常系 | 料理カテゴリ削除後                    | 残りの料理カテゴリの order が正しく整理される  | `RecipeCategoryController::bulkDestroy()`   |
| 3-6-42 | 【一括削除】 存在しない料理カテゴリの削除                          | 異常系 | 存在しない ID を含む配列を提供        | HTTP 404 Not Found                             | `RecipeCategoryController::bulkDestroy()`   |
| 3-6-43 | 【一括削除】 他グループの料理カテゴリ削除                          | 異常系 | 他グループの ID を含む配列を提供      | HTTP 404 Not Found                             | `RecipeCategoryController::bulkDestroy()`   |
| 3-6-44 | 【一括削除】 バリデーションエラー（IDs 未入力）                    | 異常系 | ids フィールドが未入力                | HTTP 422 Validation Error                      | `RecipeCategoryBulkDestroyRequest::rules()` |
| 3-6-45 | 【一括削除】 バリデーションエラー（IDs が配列でない）              | 異常系 | ids が配列でない（文字列など）        | HTTP 422 Validation Error                      | `RecipeCategoryBulkDestroyRequest::rules()` |
| 3-6-46 | 【一括削除】 バリデーションエラー（IDs が空配列）                  | 異常系 | ids が空配列（min:1 違反）            | HTTP 422 Validation Error                      | `RecipeCategoryBulkDestroyRequest::rules()` |
| 3-6-47 | 【一括削除】 バリデーションエラー（ID が UUID 形式でない）         | 異常系 | ids.\* が UUID 形式でない             | HTTP 422 Validation Error                      | `RecipeCategoryBulkDestroyRequest::rules()` |
| 3-6-48 | 【一括削除】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー              | HTTP 401 Unauthorized                          | `RecipeCategoryController::bulkDestroy()`   |
| 3-6-49 | 【一括削除】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない    | HTTP 422 Unprocessable Entity                  | `RecipeCategoryController::bulkDestroy()`   |
| 3-6-50 | 【一括削除】 データベース接続エラー                                | 異常系 | データベース接続が失敗                | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkDestroy()`   |
| 3-6-51 | 【一括削除】 料理カテゴリ削除失敗                                  | 異常系 | RecipeCategory::delete() が失敗       | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkDestroy()`   |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
