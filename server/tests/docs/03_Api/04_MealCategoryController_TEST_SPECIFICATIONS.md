# MealCategoryController テストケース詳細仕様

## 概要

MealCategoryController のテストケースの詳細仕様を示します。献立カテゴリの一覧取得、一括作成、一括更新、一括削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                           | 種別   | 入力条件                             | 期待される出力                                           | 該当メソッド                             |
| ------ | ------------------------------------------------------------------ | ------ | ------------------------------------ | -------------------------------------------------------- | ---------------------------------------- |
| 3-4-1  | 【一覧取得】 正常な献立カテゴリ一覧取得                            | 正常系 | 認証済みユーザー                     | HTTP 200 献立カテゴリ一覧を取得                          | `MealCategoryController::index()`        |
| 3-4-2  | 【一覧取得】 レスポンス形式確認                                    | 正常系 | 正常な一覧取得後                     | 正しい JSON 形式でレスポンスが返される                   | `MealCategoryController::index()`        |
| 3-4-3  | 【一覧取得】 order 順での取得確認                                  | 正常系 | 複数の献立カテゴリが存在             | order フィールド順に献立カテゴリが取得される             | `MealCategoryController::index()`        |
| 3-4-4  | 【一覧取得】 空のリスト取得                                        | 正常系 | 献立カテゴリが 0 件                  | HTTP 200 空の配列が返される                              | `MealCategoryController::index()`        |
| 3-4-5  | 【一覧取得】 他グループの献立カテゴリは取得されない                | 正常系 | 他グループの献立カテゴリが存在       | 自グループの献立カテゴリのみが取得される                 | `MealCategoryController::index()`        |
| 3-4-6  | 【一覧取得】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                    | `MealCategoryController::index()`        |
| 3-4-7  | 【一覧取得】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                            | `MealCategoryController::index()`        |
| 3-4-8  | 【一覧取得】 データベース接続エラー                                | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                           | `MealCategoryController::index()`        |
| 3-4-9  | 【一括作成】 正常な献立カテゴリ一括作成                            | 正常系 | 有効な献立カテゴリデータ配列を提供   | HTTP 201 Created                                         | `MealCategoryController::bulkStore()`    |
| 3-4-10 | 【一括作成】 レスポンス形式確認                                    | 正常系 | 正常な献立カテゴリ一括作成後         | 正しい JSON 形式でレスポンスが返される                   | `MealCategoryController::bulkStore()`    |
| 3-4-11 | 【一括作成】 バリデーションエラー（献立カテゴリ名未入力）          | 異常系 | data.\*.name が未入力                | HTTP 422 Validation Error                                | `MealCategoryBulkStoreRequest::rules()`  |
| 3-4-12 | 【一括作成】 バリデーションエラー（献立カテゴリ名が文字列以外）    | 異常系 | data.\*.name が数値や配列などを提供  | HTTP 422 Validation Error                                | `MealCategoryBulkStoreRequest::rules()`  |
| 3-4-13 | 【一括作成】 バリデーションエラー（献立カテゴリ名が 255 文字超過） | 異常系 | data.\*.name が 256 文字以上         | HTTP 422 Validation Error                                | `MealCategoryBulkStoreRequest::rules()`  |
| 3-4-14 | 【一括作成】 バリデーションエラー（色 ID 未入力）                  | 異常系 | data.\*.colorId が未入力              | HTTP 422 Validation Error                                | `MealCategoryBulkStoreRequest::rules()`  |
| 3-4-15 | 【一括作成】 バリデーションエラー（色 ID が UUID 形式でない）      | 異常系 | data.\*.colorId が UUID 形式でない   | HTTP 422 Validation Error                                | `MealCategoryBulkStoreRequest::rules()`  |
| 3-4-16 | 【一括作成】 バリデーションエラー（色 ID が存在しない）            | 異常系 | data.\*.colorId が存在しない UUID   | HTTP 422 Validation Error                                | `MealCategoryBulkStoreRequest::rules()`  |
| 3-4-17 | 【一括作成】 バリデーションエラー（order 値が未入力）              | 異常系 | data.\*.order が未入力               | HTTP 422 Validation Error                                | `MealCategoryBulkStoreRequest::rules()`  |
| 3-4-18 | 【一括作成】 バリデーションエラー（order 値が数値以外）            | 異常系 | data.\*.order が数値以外             | HTTP 422 Validation Error                                | `MealCategoryBulkStoreRequest::rules()`  |
| 3-4-19 | 【一括作成】 バリデーションエラー（order 値が負の値）              | 異常系 | data.\*.order が 0 未満の負の値      | HTTP 422 Validation Error                                | `MealCategoryBulkStoreRequest::rules()`  |
| 3-4-20 | 【一括作成】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                    | `MealCategoryController::bulkStore()`    |
| 3-4-21 | 【一括作成】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                            | `MealCategoryController::bulkStore()`    |
| 3-4-22 | 【一括作成】 データベース接続エラー                                | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                           | `MealCategoryController::bulkStore()`    |
| 3-4-23 | 【一括作成】 献立カテゴリ作成失敗                                  | 異常系 | MealCategory::create() が失敗        | HTTP 500 Internal Server Error                           | `MealCategoryController::bulkStore()`   |
| 3-4-24 | 【一括更新】 正常な献立カテゴリ一括更新                            | 正常系 | 有効な献立カテゴリデータ配列を提供   | HTTP 200 JSON success                                    | `MealCategoryController::bulkUpdate()`   |
| 3-4-25 | 【一括更新】 一括更新成功メッセージの確認                          | 正常系 | 正常な一括更新後                     | 更新件数を含む適切なメッセージが返される                 | `MealCategoryController::bulkUpdate()`   |
| 3-4-26 | 【一括更新】 一括更新後のデータ取得確認                            | 正常系 | 正常な一括更新後                     | 更新された献立カテゴリデータが正しく取得される           | `MealCategoryController::bulkUpdate()`   |
| 3-4-27 | 【一括更新】 バリデーションエラー（data が未入力）                 | 異常系 | data フィールドが未入力              | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-28 | 【一括更新】 バリデーションエラー（data が配列以外）               | 異常系 | data が配列以外の型を提供            | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-29 | 【一括更新】 バリデーションエラー（data が空配列）                 | 異常系 | data が空配列（min:1 違反）          | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-30 | 【一括更新】 バリデーションエラー（ID 未入力）                     | 異常系 | data.\*.id が未入力                  | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-31 | 【一括更新】 バリデーションエラー（ID が UUID 形式でない）         | 異常系 | data.\*.id が UUID 形式でない        | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-32 | 【一括更新】 バリデーションエラー（献立カテゴリ名未入力）          | 異常系 | data.\*.name が未入力                | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-33 | 【一括更新】 バリデーションエラー（献立カテゴリ名が文字列以外）    | 異常系 | data.\*.name が文字列以外            | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-34 | 【一括更新】 バリデーションエラー（献立カテゴリ名が 255 文字超過） | 異常系 | data.\*.name が 256 文字以上         | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-35 | 【一括更新】 バリデーションエラー（色 ID 未入力）                  | 異常系 | data.\*.colorId が未入力             | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-36 | 【一括更新】 バリデーションエラー（色 ID が UUID 形式でない）      | 異常系 | data.\*.colorId が UUID 形式でない   | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-37 | 【一括更新】 バリデーションエラー（色 ID が存在しない）            | 異常系 | data.\*.colorId が存在しない色 ID    | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-38 | 【一括更新】 バリデーションエラー（order 値が未入力）              | 異常系 | data.\*.order が未入力               | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-39 | 【一括更新】 バリデーションエラー（order 値が数値以外）            | 異常系 | data.\*.order が数値以外             | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-40 | 【一括更新】 バリデーションエラー（order 値が負の値）              | 異常系 | data.\*.order が 0 未満の負の値      | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-4-41 | 【一括更新】 存在しない献立カテゴリの更新                          | 異常系 | 存在しない ID を含むデータ配列を提供 | HTTP 404 Not Found                                       | `MealCategoryController::bulkUpdate()`   |
| 3-4-42 | 【一括更新】 他グループの献立カテゴリ更新                          | 異常系 | 他グループの献立カテゴリ ID を提供   | HTTP 404 Not Found                                       | `MealCategoryController::bulkUpdate()`   |
| 3-4-43 | 【一括更新】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                    | `MealCategoryController::bulkUpdate()`   |
| 3-4-44 | 【一括更新】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                            | `MealCategoryController::bulkUpdate()`   |
| 3-4-45 | 【一括更新】 データベース接続エラー                                | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                           | `MealCategoryController::bulkUpdate()`   |
| 3-4-46 | 【一括更新】 献立カテゴリ更新失敗                                  | 異常系 | MealCategory::update() が失敗        | HTTP 500 Internal Server Error                           | `MealCategoryController::bulkUpdate()`   |
| 3-4-47 | 【一括削除】 正常な献立カテゴリ一括削除                            | 正常系 | 有効な献立カテゴリ ID 配列を提供     | HTTP 200 JSON success                                    | `MealCategoryController::bulkDestroy()`  |
| 3-4-48 | 【一括削除】 削除後の order 整理確認                               | 正常系 | 献立カテゴリ一括削除後               | 残りの献立カテゴリの order が正しく整理される            | `MealCategoryController::bulkDestroy()`  |
| 3-4-49 | 【一括削除】 削除成功メッセージの確認                              | 正常系 | 正常な献立カテゴリ一括削除後         | 削除件数を含む適切なメッセージが返される                 | `MealCategoryController::bulkDestroy()`  |
| 3-4-50 | 【一括削除】 存在しない献立カテゴリ削除                            | 異常系 | 存在しない献立カテゴリ ID を ids に含む | HTTP 404 Not Found 等（仕様に応じる）                 | `MealCategoryController::bulkDestroy()`  |
| 3-4-51 | 【一括削除】 他グループの献立カテゴリ削除                          | 異常系 | 他グループの献立カテゴリ ID を提供   | HTTP 404 Not Found 等（仕様に応じる）                    | `MealCategoryController::bulkDestroy()`  |
| 3-4-52 | 【一括削除】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                    | `MealCategoryController::bulkDestroy()`  |
| 3-4-53 | 【一括削除】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                            | `MealCategoryController::bulkDestroy()`  |
| 3-4-54 | 【一括削除】 データベース接続エラー                                | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                           | `MealCategoryController::bulkDestroy()`  |
| 3-4-55 | 【一括削除】 献立カテゴリ削除失敗                                  | 異常系 | MealCategory::delete() が失敗        | HTTP 500 Internal Server Error                           | `MealCategoryController::bulkDestroy()`  |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
