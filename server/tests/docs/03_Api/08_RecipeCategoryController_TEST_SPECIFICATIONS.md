# RecipeCategoryController テストケース詳細仕様

## 概要

RecipeCategoryController のテストケースの詳細仕様を示します。料理カテゴリの作成、一括更新、一括削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                           | 種別   | 入力条件                              | 期待される出力                                 | 該当メソッド                                |
| ------ | ------------------------------------------------------------------ | ------ | ------------------------------------- | ---------------------------------------------- | ------------------------------------------- |
| 3-8-1  | 【一覧取得】 正常な料理カテゴリ一覧取得                            | 正常系 | 認証済みユーザー                      | HTTP 200 料理カテゴリ一覧を取得                | `RecipeCategoryController::index()`         |
| 3-8-2  | 【一覧取得】 レスポンス形式確認                                    | 正常系 | 正常な一覧取得後                      | 正しい JSON 形式でレスポンスが返される         | `RecipeCategoryController::index()`         |
| 3-8-3  | 【一覧取得】 order 順での取得確認                                  | 正常系 | 複数の料理カテゴリが存在              | order フィールド順に料理カテゴリが取得される   | `RecipeCategoryController::index()`         |
| 3-8-4  | 【一覧取得】 空のリスト取得                                        | 正常系 | 料理カテゴリが 0 件                   | HTTP 200 空の配列が返される                    | `RecipeCategoryController::index()`         |
| 3-8-5  | 【一覧取得】 他グループの料理カテゴリは取得されない                | 正常系 | 他グループの料理カテゴリが存在        | 自グループの料理カテゴリのみが取得される       | `RecipeCategoryController::index()`         |
| 3-8-6  | 【一覧取得】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー              | HTTP 401 Unauthorized                          | `RecipeCategoryController::index()`         |
| 3-8-7  | 【一覧取得】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない    | HTTP 422 Unprocessable Entity                  | `RecipeCategoryController::index()`         |
| 3-8-8  | 【一覧取得】 データベース接続エラー                                | 異常系 | データベース接続が失敗                | HTTP 500 Internal Server Error                 | `RecipeCategoryController::index()`         |
| 3-8-9  | 【新規作成】 正常な料理カテゴリ作成                                | 正常系 | 有効な料理カテゴリデータを提供        | HTTP 201 Created                               | `RecipeCategoryController::store()`         |
| 3-8-10 | 【新規作成】 レスポンス形式確認                                    | 正常系 | 正常な料理カテゴリ作成後              | 正しい JSON 形式でレスポンスが返される         | `RecipeCategoryController::store()`         |
| 3-8-11 | 【新規作成】 バリデーションエラー（料理カテゴリ名未入力）          | 異常系 | 料理カテゴリ名が未入力                | HTTP 422 Validation Error                      | `RecipeCategoryStoreRequest::rules()`       |
| 3-8-12 | 【新規作成】 バリデーションエラー（料理カテゴリ名が 255 文字超過） | 異常系 | 256 文字以上の料理カテゴリ名を提供    | HTTP 422 Validation Error                      | `RecipeCategoryStoreRequest::rules()`       |
| 3-8-13 | 【新規作成】 バリデーションエラー（order 値が未入力）              | 異常系 | order 値が未入力                      | HTTP 422 Validation Error                      | `RecipeCategoryStoreRequest::rules()`       |
| 3-8-14 | 【新規作成】 バリデーションエラー（order 値が数値以外）            | 異常系 | order 値が数値以外                    | HTTP 422 Validation Error                      | `RecipeCategoryStoreRequest::rules()`       |
| 3-8-15 | 【新規作成】 バリデーションエラー（order 値が負の値）              | 異常系 | order 値が 0 未満の負の値             | HTTP 422 Validation Error                      | `RecipeCategoryStoreRequest::rules()`       |
| 3-8-16 | 【新規作成】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー              | HTTP 401 Unauthorized                          | `RecipeCategoryController::store()`         |
| 3-8-17 | 【新規作成】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない    | HTTP 422 Unprocessable Entity                  | `RecipeCategoryController::store()`         |
| 3-8-18 | 【新規作成】 データベース接続エラー                                | 異常系 | データベース接続が失敗                | HTTP 500 Internal Server Error                 | `RecipeCategoryController::store()`         |
| 3-8-19 | 【新規作成】 料理カテゴリ作成失敗                                  | 異常系 | RecipeCategory::create() が失敗       | HTTP 500 Internal Server Error                 | `RecipeCategoryController::store()`         |
| 3-8-20 | 【一括更新】 正常な料理カテゴリ一括更新                            | 正常系 | 有効な料理カテゴリデータ配列を提供    | HTTP 200 JSON success                          | `RecipeCategoryController::bulkUpdate()`    |
| 3-8-21 | 【一括更新】 一括更新成功メッセージの確認                          | 正常系 | 正常な一括更新後                      | 更新件数を含む適切なメッセージが返される       | `RecipeCategoryController::bulkUpdate()`    |
| 3-8-22 | 【一括更新】 一括更新後のデータ取得確認                            | 正常系 | 正常な一括更新後                      | 更新された料理カテゴリデータが正しく取得される | `RecipeCategoryController::bulkUpdate()`    |
| 3-8-23 | 【一括更新】 存在しない料理カテゴリの更新                          | 異常系 | 存在しない ID を含むデータ配列を提供  | HTTP 404 Not Found                             | `RecipeCategoryController::bulkUpdate()`    |
| 3-8-24 | 【一括更新】 他グループの料理カテゴリ更新                          | 異常系 | 他グループの料理カテゴリ ID を提供    | HTTP 404 Not Found                             | `RecipeCategoryController::bulkUpdate()`    |
| 3-8-25 | 【一括更新】 バリデーションエラー（data 未入力）                   | 異常系 | data フィールドが未入力               | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-26 | 【一括更新】 バリデーションエラー（data が配列でない）             | 異常系 | data が配列でない（文字列など）       | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-27 | 【一括更新】 バリデーションエラー（data が空配列）                 | 異常系 | data が空配列                         | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-28 | 【一括更新】 バリデーションエラー（id が未入力）                   | 異常系 | data.\*.id が未入力                   | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-29 | 【一括更新】 バリデーションエラー（id が UUID 形式でない）         | 異常系 | data.\*.id が UUID 形式でない         | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-30 | 【一括更新】 バリデーションエラー（name が未入力）                 | 異常系 | data.\*.name が未入力                 | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-31 | 【一括更新】 バリデーションエラー（name が文字列でない）           | 異常系 | data.\*.name が文字列でない（数値等） | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-32 | 【一括更新】 バリデーションエラー（order が未入力）                | 異常系 | data.\*.order が未入力                | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-33 | 【一括更新】 バリデーションエラー（order が数値でない）            | 異常系 | data.\*.order が数値でない            | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-34 | 【一括更新】 バリデーションエラー（order が負の値）                | 異常系 | data.\*.order が 0 未満の負の値       | HTTP 422 Validation Error                      | `RecipeCategoryBulkUpdateRequest::rules()`  |
| 3-8-35 | 【一括更新】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー              | HTTP 401 Unauthorized                          | `RecipeCategoryController::bulkUpdate()`    |
| 3-8-36 | 【一括更新】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない    | HTTP 422 Unprocessable Entity                  | `RecipeCategoryController::bulkUpdate()`    |
| 3-8-37 | 【一括更新】 データベース接続エラー                                | 異常系 | データベース接続が失敗                | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkUpdate()`    |
| 3-8-38 | 【一括更新】 料理カテゴリ更新失敗                                  | 異常系 | RecipeCategory::update() が失敗       | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkUpdate()`    |
| 3-8-39 | 【一括削除】 正常な料理カテゴリ一括削除                            | 正常系 | 有効な料理カテゴリ ID 配列を提供      | HTTP 200 JSON success                          | `RecipeCategoryController::bulkDestroy()`   |
| 3-8-40 | 【一括削除】 一括削除成功メッセージの確認                          | 正常系 | 正常な一括削除後                      | 削除件数を含む適切なメッセージが返される       | `RecipeCategoryController::bulkDestroy()`   |
| 3-8-41 | 【一括削除】 削除後の order 整理確認                               | 正常系 | 料理カテゴリ削除後                    | 残りの料理カテゴリの order が正しく整理される  | `RecipeCategoryController::bulkDestroy()`   |
| 3-8-42 | 【一括削除】 存在しない料理カテゴリの削除                          | 異常系 | 存在しない ID を含む配列を提供        | HTTP 404 Not Found                             | `RecipeCategoryController::bulkDestroy()`   |
| 3-8-43 | 【一括削除】 他グループの料理カテゴリ削除                          | 異常系 | 他グループの ID を含む配列を提供      | HTTP 404 Not Found                             | `RecipeCategoryController::bulkDestroy()`   |
| 3-8-44 | 【一括削除】 バリデーションエラー（IDs 未入力）                    | 異常系 | ids フィールドが未入力                | HTTP 422 Validation Error                      | `RecipeCategoryBulkDestroyRequest::rules()` |
| 3-8-45 | 【一括削除】 バリデーションエラー（IDs が配列でない）              | 異常系 | ids が配列でない（文字列など）        | HTTP 422 Validation Error                      | `RecipeCategoryBulkDestroyRequest::rules()` |
| 3-8-46 | 【一括削除】 バリデーションエラー（IDs が空配列）                  | 異常系 | ids が空配列（min:1 違反）            | HTTP 422 Validation Error                      | `RecipeCategoryBulkDestroyRequest::rules()` |
| 3-8-47 | 【一括削除】 バリデーションエラー（ID が UUID 形式でない）         | 異常系 | ids.\* が UUID 形式でない             | HTTP 422 Validation Error                      | `RecipeCategoryBulkDestroyRequest::rules()` |
| 3-8-48 | 【一括削除】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー              | HTTP 401 Unauthorized                          | `RecipeCategoryController::bulkDestroy()`   |
| 3-8-49 | 【一括削除】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない    | HTTP 422 Unprocessable Entity                  | `RecipeCategoryController::bulkDestroy()`   |
| 3-8-50 | 【一括削除】 データベース接続エラー                                | 異常系 | データベース接続が失敗                | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkDestroy()`   |
| 3-8-51 | 【一括削除】 料理カテゴリ削除失敗                                  | 異常系 | RecipeCategory::delete() が失敗       | HTTP 500 Internal Server Error                 | `RecipeCategoryController::bulkDestroy()`   |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
