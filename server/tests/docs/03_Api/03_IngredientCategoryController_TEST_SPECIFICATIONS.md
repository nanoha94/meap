# IngredientCategoryController テストケース詳細仕様

## 概要

IngredientCategoryController のテストケースの詳細仕様を示します。食材カテゴリの一覧取得、作成、一括更新、一括削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                       | 種別   | 入力条件                           | 期待される出力                            | 該当メソッド                                    |
| ------ | -------------------------------------------------------------- | ------ | ---------------------------------- | ----------------------------------------- | ----------------------------------------------- |
| 3-3-1  | 【一覧取得】 正常な食材カテゴリ一覧取得                        | 正常系 | 認証済みユーザー                   | HTTP 200 JSON success                     | `IngredientCategoryController::index()`         |
| 3-3-2  | 【一覧取得】 カテゴリ情報の並び順確認                          | 正常系 | 正常なカテゴリ一覧取得後           | order 順でカテゴリが取得される            | `IngredientCategoryController::index()`         |
| 3-3-3  | 【一覧取得】 空のカテゴリー一覧                                | 正常系 | カテゴリーが存在しない             | HTTP 200 JSON success                     | `IngredientCategoryController::index()`         |
| 3-3-4  | 【一覧取得】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `IngredientCategoryController::index()`         |
| 3-3-5  | 【一覧取得】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `IngredientCategoryController::index()`         |
| 3-3-6  | 【新規作成】 正常な食材カテゴリ作成                            | 正常系 | 有効な名前と order を提供          | HTTP 201 Created                          | `IngredientCategoryController::store()`         |
| 3-3-7  | 【新規作成】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `IngredientCategoryController::store()`         |
| 3-3-8  | 【新規作成】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `IngredientCategoryController::store()`         |
| 3-3-9  | 【新規作成】 データベースエラー                                | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `IngredientCategoryController::store()`         |
| 3-3-10 | 【新規作成】 バリデーションエラー（カテゴリ名未入力）          | 異常系 | カテゴリ名が未入力                 | HTTP 422 Validation Error                 | `IngredientCategoryStoreRequest::rules()`       |
| 3-3-11 | 【新規作成】 バリデーションエラー（カテゴリ名が文字列以外）    | 異常系 | カテゴリ名が文字列以外             | HTTP 422 Validation Error                 | `IngredientCategoryStoreRequest::rules()`       |
| 3-3-12 | 【新規作成】 バリデーションエラー（カテゴリ名が 255 文字超過） | 異常系 | 256 文字以上のカテゴリ名を提供     | HTTP 422 Validation Error                 | `IngredientCategoryStoreRequest::rules()`       |
| 3-3-13 | 【新規作成】 バリデーションエラー（order 値が未入力）          | 異常系 | order 値が未入力                   | HTTP 422 Validation Error                 | `IngredientCategoryStoreRequest::rules()`       |
| 3-3-14 | 【新規作成】 バリデーションエラー（order 値が数値以外）        | 異常系 | order 値が数値以外                 | HTTP 422 Validation Error                 | `IngredientCategoryStoreRequest::rules()`       |
| 3-3-15 | 【新規作成】 バリデーションエラー（order 値が負の数）          | 異常系 | order 値が負の数                   | HTTP 422 Validation Error                 | `IngredientCategoryStoreRequest::rules()`       |
| 3-3-16 | 【一括更新】 正常な食材カテゴリ一括更新                        | 正常系 | 有効なカテゴリデータ配列を提供     | HTTP 200 JSON success                     | `IngredientCategoryController::bulkUpdate()`    |
| 3-3-17 | 【一括更新】 部分的な一括更新失敗                              | 異常系 | 存在しない ID を含むデータを提供   | HTTP 404 Not Found                        | `IngredientCategoryController::bulkUpdate()`    |
| 3-3-18 | 【一括更新】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `IngredientCategoryController::bulkUpdate()`    |
| 3-3-19 | 【一括更新】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `IngredientCategoryController::bulkUpdate()`    |
| 3-3-20 | 【一括更新】 データベース接続エラー                            | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `IngredientCategoryController::bulkUpdate()`    |
| 3-3-21 | 【一括更新】 ID が null（nullable 許可）                       | 異常系 | ID が null のカテゴリデータ        | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-22 | 【一括更新】 グループ外のカテゴリ更新試行                      | 異常系 | 他のグループのカテゴリ ID を提供   | HTTP 404 Not Found                        | `IngredientCategoryController::bulkUpdate()`    |
| 3-3-23 | 【一括更新】 バリデーションエラー（データ配列未入力）          | 異常系 | data 配列が未入力                  | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-24 | 【一括更新】 バリデーションエラー（データ配列が配列以外）      | 異常系 | data が配列以外                    | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-25 | 【一括更新】 バリデーションエラー（データ配列が空）            | 異常系 | data 配列が空                      | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-26 | 【一括更新】 バリデーションエラー（ID が UUID 以外）           | 異常系 | 無効な UUID 形式の ID を提供       | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-27 | 【一括更新】 ID が存在しない                                   | 異常系 | 存在しない ID を提供               | HTTP 404 Not Found                        | `IngredientCategoryController::bulkUpdate()`    |
| 3-3-28 | 【一括更新】 バリデーションエラー（カテゴリ名未入力）          | 異常系 | カテゴリ名が未入力                 | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-29 | 【一括更新】 バリデーションエラー（カテゴリ名が文字列以外）    | 異常系 | カテゴリ名が文字列以外             | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-30 | 【一括更新】 バリデーションエラー（カテゴリ名が 255 文字超過） | 異常系 | 256 文字以上のカテゴリ名を提供     | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-31 | 【一括更新】 バリデーションエラー（order 値が未入力）          | 異常系 | order 値が未入力                   | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-32 | 【一括更新】 バリデーションエラー（order 値が数値以外）        | 異常系 | order 値が数値以外                 | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-33 | 【一括更新】 バリデーションエラー（order 値が負の数）          | 異常系 | order 値が負の数                   | HTTP 422 Validation Error                 | `IngredientCategoryBulkUpdateRequest::rules()`  |
| 3-3-34 | 【一括削除】 正常な食材カテゴリ一括削除                        | 正常系 | 有効なカテゴリ ID 配列を提供       | HTTP 200 JSON success                     | `IngredientCategoryController::bulkDestroy()`   |
| 3-3-35 | 【一括削除】 削除後の order 整理確認                           | 正常系 | カテゴリ削除後                     | 残りのカテゴリの order が正しく整理される | `IngredientCategoryController::bulkDestroy()`   |
| 3-3-36 | 【一括削除】 部分的な一括削除失敗                              | 異常系 | 存在しない ID を含むデータを提供   | HTTP 404 Not Found                        | `IngredientCategoryController::bulkDestroy()`   |
| 3-3-37 | 【一括削除】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `IngredientCategoryController::bulkDestroy()`   |
| 3-3-38 | 【一括削除】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `IngredientCategoryController::bulkDestroy()`   |
| 3-3-39 | 【一括削除】 データベース接続エラー                            | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `IngredientCategoryController::bulkDestroy()`   |
| 3-3-40 | 【一括削除】 グループ外のカテゴリ削除試行                      | 異常系 | 他のグループのカテゴリ ID を提供   | HTTP 404 Not Found                        | `IngredientCategoryController::bulkDestroy()`   |
| 3-3-41 | 【一括削除】 バリデーションエラー（ID 配列未入力）             | 異常系 | ids 配列が未入力                   | HTTP 422 Validation Error                 | `IngredientCategoryBulkDestroyRequest::rules()` |
| 3-3-42 | 【一括削除】 バリデーションエラー（ID 配列が配列以外）         | 異常系 | ids が配列以外                     | HTTP 422 Validation Error                 | `IngredientCategoryBulkDestroyRequest::rules()` |
| 3-3-43 | 【一括削除】 バリデーションエラー（ID 配列が空）               | 異常系 | ids 配列が空                       | HTTP 422 Validation Error                 | `IngredientCategoryBulkDestroyRequest::rules()` |
| 3-3-44 | 【一括削除】 バリデーションエラー（ID が UUID 以外）           | 異常系 | 無効な UUID 形式の ID を提供       | HTTP 422 Validation Error                 | `IngredientCategoryBulkDestroyRequest::rules()` |
| 3-3-45 | 【一括削除】 ID が存在しない                                   | 異常系 | 存在しない ID を提供               | HTTP 404 Not Found                        | `IngredientCategoryController::bulkDestroy()`   |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
