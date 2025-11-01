# MealCategoryController テストケース詳細仕様

## 概要

MealCategoryController のテストケースの詳細仕様を示します。献立カテゴリの一覧取得、作成、一括更新、削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                           | 種別   | 入力条件                             | 期待される出力                                           | 該当メソッド                             |
| ------ | ------------------------------------------------------------------ | ------ | ------------------------------------ | -------------------------------------------------------- | ---------------------------------------- |
| 3-5-1  | 【一覧取得】 正常な献立カテゴリ一覧取得                            | 正常系 | 認証済みユーザー                     | HTTP 200 献立カテゴリ一覧を取得                          | `MealCategoryController::index()`        |
| 3-5-2  | 【一覧取得】 レスポンス形式確認                                    | 正常系 | 正常な一覧取得後                     | 正しい JSON 形式でレスポンスが返される                   | `MealCategoryController::index()`        |
| 3-5-3  | 【一覧取得】 order 順での取得確認                                  | 正常系 | 複数の献立カテゴリが存在             | order フィールド順に献立カテゴリが取得される             | `MealCategoryController::index()`        |
| 3-5-4  | 【一覧取得】 空のリスト取得                                        | 正常系 | 献立カテゴリが 0 件                  | HTTP 200 空の配列が返される                              | `MealCategoryController::index()`        |
| 3-5-5  | 【一覧取得】 他グループの献立カテゴリは取得されない                | 正常系 | 他グループの献立カテゴリが存在       | 自グループの献立カテゴリのみが取得される                 | `MealCategoryController::index()`        |
| 3-5-6  | 【一覧取得】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                    | `MealCategoryController::index()`        |
| 3-5-7  | 【一覧取得】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                            | `MealCategoryController::index()`        |
| 3-5-8  | 【一覧取得】 データベース接続エラー                                | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                           | `MealCategoryController::index()`        |
| 3-5-9  | 【新規作成】 正常な献立カテゴリ作成                                | 正常系 | 有効な献立カテゴリデータを提供       | HTTP 201 Created                                         | `MealCategoryController::store()`        |
| 3-5-10 | 【新規作成】 レスポンス形式確認                                    | 正常系 | 正常な献立カテゴリ作成後             | 正しい JSON 形式でレスポンスが返される                   | `MealCategoryController::store()`        |
| 3-5-11 | 【新規作成】 バリデーションエラー（献立カテゴリ名未入力）          | 異常系 | 献立カテゴリ名が未入力               | HTTP 422 Validation Error                                | `MealCategoryStoreRequest::rules()`      |
| 3-5-12 | 【新規作成】 バリデーションエラー（献立カテゴリ名が文字列以外）    | 異常系 | 献立カテゴリ名が数値や配列などを提供 | HTTP 422 Validation Error                                | `MealCategoryStoreRequest::rules()`      |
| 3-5-13 | 【新規作成】 バリデーションエラー（献立カテゴリ名が 255 文字超過） | 異常系 | 256 文字以上の献立カテゴリ名を提供   | HTTP 422 Validation Error                                | `MealCategoryStoreRequest::rules()`      |
| 3-5-14 | 【新規作成】 バリデーションエラー（色 ID 未入力）                  | 異常系 | 色 ID が未入力                       | HTTP 422 Validation Error                                | `MealCategoryStoreRequest::rules()`      |
| 3-5-15 | 【新規作成】 バリデーションエラー（色 ID が UUID 形式でない）      | 異常系 | 色 ID が UUID 形式でない文字列を提供 | HTTP 422 Validation Error                                | `MealCategoryStoreRequest::rules()`      |
| 3-5-16 | 【新規作成】 バリデーションエラー（色 ID が存在しない）            | 異常系 | 存在しない色 ID（UUID）を提供        | HTTP 422 Validation Error                                | `MealCategoryStoreRequest::rules()`      |
| 3-5-17 | 【新規作成】 バリデーションエラー（order 値が未入力）              | 異常系 | order 値が未入力                     | HTTP 422 Validation Error                                | `MealCategoryStoreRequest::rules()`      |
| 3-5-18 | 【新規作成】 バリデーションエラー（order 値が数値以外）            | 異常系 | order 値が数値以外                   | HTTP 422 Validation Error                                | `MealCategoryStoreRequest::rules()`      |
| 3-5-19 | 【新規作成】 バリデーションエラー（order 値が負の値）              | 異常系 | order 値が 0 未満の負の値            | HTTP 422 Validation Error                                | `MealCategoryStoreRequest::rules()`      |
| 3-5-20 | 【新規作成】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                    | `MealCategoryController::store()`        |
| 3-5-21 | 【新規作成】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                            | `MealCategoryController::store()`        |
| 3-5-22 | 【新規作成】 データベース接続エラー                                | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                           | `MealCategoryController::store()`        |
| 3-5-23 | 【新規作成】 献立カテゴリ作成失敗                                  | 異常系 | MealCategory::create() が失敗        | HTTP 500 Internal Server Error                           | `MealCategoryController::store()`        |
| 3-5-24 | 【一括更新】 正常な献立カテゴリ一括更新                            | 正常系 | 有効な献立カテゴリデータ配列を提供   | HTTP 200 JSON success                                    | `MealCategoryController::bulkUpdate()`   |
| 3-5-25 | 【一括更新】 一括更新成功メッセージの確認                          | 正常系 | 正常な一括更新後                     | 更新件数を含む適切なメッセージが返される                 | `MealCategoryController::bulkUpdate()`   |
| 3-5-26 | 【一括更新】 一括更新後のデータ取得確認                            | 正常系 | 正常な一括更新後                     | 更新された献立カテゴリデータが正しく取得される           | `MealCategoryController::bulkUpdate()`   |
| 3-5-27 | 【一括更新】 バリデーションエラー（data が未入力）                 | 異常系 | data フィールドが未入力              | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-28 | 【一括更新】 バリデーションエラー（data が配列以外）               | 異常系 | data が配列以外の型を提供            | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-29 | 【一括更新】 バリデーションエラー（data が空配列）                 | 異常系 | data が空配列（min:1 違反）          | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-30 | 【一括更新】 バリデーションエラー（ID 未入力）                     | 異常系 | data.\*.id が未入力                  | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-31 | 【一括更新】 バリデーションエラー（ID が UUID 形式でない）         | 異常系 | data.\*.id が UUID 形式でない        | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-32 | 【一括更新】 バリデーションエラー（献立カテゴリ名未入力）          | 異常系 | data.\*.name が未入力                | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-33 | 【一括更新】 バリデーションエラー（献立カテゴリ名が文字列以外）    | 異常系 | data.\*.name が文字列以外            | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-34 | 【一括更新】 バリデーションエラー（献立カテゴリ名が 255 文字超過） | 異常系 | data.\*.name が 256 文字以上         | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-35 | 【一括更新】 バリデーションエラー（色 ID 未入力）                  | 異常系 | data.\*.colorId が未入力             | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-36 | 【一括更新】 バリデーションエラー（色 ID が UUID 形式でない）      | 異常系 | data.\*.colorId が UUID 形式でない   | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-37 | 【一括更新】 バリデーションエラー（色 ID が存在しない）            | 異常系 | data.\*.colorId が存在しない色 ID    | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-38 | 【一括更新】 バリデーションエラー（order 値が未入力）              | 異常系 | data.\*.order が未入力               | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-39 | 【一括更新】 バリデーションエラー（order 値が数値以外）            | 異常系 | data.\*.order が数値以外             | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-40 | 【一括更新】 バリデーションエラー（order 値が負の値）              | 異常系 | data.\*.order が 0 未満の負の値      | HTTP 422 Validation Error                                | `MealCategoryBulkUpdateRequest::rules()` |
| 3-5-41 | 【一括更新】 存在しない献立カテゴリの更新                          | 異常系 | 存在しない ID を含むデータ配列を提供 | HTTP 404 Not Found                                       | `MealCategoryController::bulkUpdate()`   |
| 3-5-42 | 【一括更新】 他グループの献立カテゴリ更新                          | 異常系 | 他グループの献立カテゴリ ID を提供   | HTTP 404 Not Found                                       | `MealCategoryController::bulkUpdate()`   |
| 3-5-43 | 【一括更新】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                    | `MealCategoryController::bulkUpdate()`   |
| 3-5-44 | 【一括更新】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                            | `MealCategoryController::bulkUpdate()`   |
| 3-5-45 | 【一括更新】 データベース接続エラー                                | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                           | `MealCategoryController::bulkUpdate()`   |
| 3-5-46 | 【一括更新】 献立カテゴリ更新失敗                                  | 異常系 | MealCategory::update() が失敗        | HTTP 500 Internal Server Error                           | `MealCategoryController::bulkUpdate()`   |
| 3-5-47 | 【削除】 正常な献立カテゴリ削除                                    | 正常系 | 有効な献立カテゴリ ID を提供         | HTTP 200 JSON success                                    | `MealCategoryController::destroy()`      |
| 3-5-48 | 【削除】 削除後の order 整理確認                                   | 正常系 | 献立カテゴリ削除後                   | 残りの献立カテゴリの order が正しく整理される            | `MealCategoryController::destroy()`      |
| 3-5-49 | 【削除】 削除成功メッセージの確認                                  | 正常系 | 正常な献立カテゴリ削除後             | 削除された献立カテゴリ名を含む適切なメッセージが返される | `MealCategoryController::destroy()`      |
| 3-5-50 | 【削除】 存在しない献立カテゴリ削除                                | 異常系 | 存在しない献立カテゴリ ID を提供     | HTTP 404 Not Found                                       | `MealCategoryController::destroy()`      |
| 3-5-51 | 【削除】 他グループの献立カテゴリ削除                              | 異常系 | 他グループの献立カテゴリ ID を提供   | HTTP 404 Not Found                                       | `MealCategoryController::destroy()`      |
| 3-5-52 | 【削除】 未認証ユーザー                                            | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                    | `MealCategoryController::destroy()`      |
| 3-5-53 | 【削除】 グループが存在しない                                      | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                            | `MealCategoryController::destroy()`      |
| 3-5-54 | 【削除】 データベース接続エラー                                    | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                           | `MealCategoryController::destroy()`      |
| 3-5-55 | 【削除】 献立カテゴリ削除失敗                                      | 異常系 | MealCategory::delete() が失敗        | HTTP 500 Internal Server Error                           | `MealCategoryController::destroy()`      |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
