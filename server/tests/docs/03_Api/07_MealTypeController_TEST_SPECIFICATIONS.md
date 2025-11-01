# MealTypeController テストケース詳細仕様

## 概要

MealTypeController のテストケースの詳細仕様を示します。献立種別の一覧取得、作成、一括更新、削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                       | 種別   | 入力条件                             | 期待される出力                                       | 該当メソッド                         |
| ------ | -------------------------------------------------------------- | ------ | ------------------------------------ | ---------------------------------------------------- | ------------------------------------ |
| 3-7-1  | 【一覧取得】 正常な献立種別一覧取得                            | 正常系 | 認証済みユーザー                     | HTTP 200 献立種別一覧を取得                          | `MealTypeController::index()`        |
| 3-7-2  | 【一覧取得】 レスポンス形式確認                                | 正常系 | 正常な一覧取得後                     | 正しい JSON 形式でレスポンスが返される               | `MealTypeController::index()`        |
| 3-7-3  | 【一覧取得】 order 順での取得確認                              | 正常系 | 複数の献立種別が存在                 | order フィールド順に献立種別が取得される             | `MealTypeController::index()`        |
| 3-7-4  | 【一覧取得】 空のリスト取得                                    | 正常系 | 献立種別が 0 件                      | HTTP 200 空の配列が返される                          | `MealTypeController::index()`        |
| 3-7-5  | 【一覧取得】 他グループの献立種別は取得されない                | 正常系 | 他グループの献立種別が存在           | 自グループの献立種別のみが取得される                 | `MealTypeController::index()`        |
| 3-7-6  | 【一覧取得】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                | `MealTypeController::index()`        |
| 3-7-7  | 【一覧取得】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                        | `MealTypeController::index()`        |
| 3-7-8  | 【一覧取得】 データベース接続エラー                            | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                       | `MealTypeController::index()`        |
| 3-7-9  | 【新規作成】 正常な献立種別作成                                | 正常系 | 有効な献立種別データを提供           | HTTP 201 Created                                     | `MealTypeController::store()`        |
| 3-7-10 | 【新規作成】 レスポンス形式確認                                | 正常系 | 正常な献立種別作成後                 | 正しい JSON 形式でレスポンスが返される               | `MealTypeController::store()`        |
| 3-7-11 | 【新規作成】 バリデーションエラー（献立種別名未入力）          | 異常系 | 献立種別名が未入力                   | HTTP 422 Validation Error                            | `MealTypeStoreRequest::rules()`      |
| 3-7-12 | 【新規作成】 バリデーションエラー（献立種別名が文字列以外）    | 異常系 | 献立種別名が数値や配列などを提供     | HTTP 422 Validation Error                            | `MealTypeStoreRequest::rules()`      |
| 3-7-13 | 【新規作成】 バリデーションエラー（献立種別名が 255 文字超過） | 異常系 | 256 文字以上の献立種別名を提供       | HTTP 422 Validation Error                            | `MealTypeStoreRequest::rules()`      |
| 3-7-14 | 【新規作成】 バリデーションエラー（色 ID 未入力）              | 異常系 | 色 ID が未入力                       | HTTP 422 Validation Error                            | `MealTypeStoreRequest::rules()`      |
| 3-7-15 | 【新規作成】 バリデーションエラー（色 ID が UUID 形式でない）  | 異常系 | 色 ID が UUID 形式でない文字列を提供 | HTTP 422 Validation Error                            | `MealTypeStoreRequest::rules()`      |
| 3-7-16 | 【新規作成】 バリデーションエラー（色 ID が存在しない）        | 異常系 | 存在しない色 ID（UUID）を提供        | HTTP 422 Validation Error                            | `MealTypeStoreRequest::rules()`      |
| 3-7-17 | 【新規作成】 バリデーションエラー（order 値が未入力）          | 異常系 | order 値が未入力                     | HTTP 422 Validation Error                            | `MealTypeStoreRequest::rules()`      |
| 3-7-18 | 【新規作成】 バリデーションエラー（order 値が数値以外）        | 異常系 | order 値が数値以外                   | HTTP 422 Validation Error                            | `MealTypeStoreRequest::rules()`      |
| 3-7-19 | 【新規作成】 バリデーションエラー（order 値が負の値）          | 異常系 | order 値が 0 未満の負の値            | HTTP 422 Validation Error                            | `MealTypeStoreRequest::rules()`      |
| 3-7-20 | 【新規作成】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                | `MealTypeController::store()`        |
| 3-7-21 | 【新規作成】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                        | `MealTypeController::store()`        |
| 3-7-22 | 【新規作成】 データベース接続エラー                            | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                       | `MealTypeController::store()`        |
| 3-7-23 | 【新規作成】 献立種別作成失敗                                  | 異常系 | MealType::create() が失敗            | HTTP 500 Internal Server Error                       | `MealTypeController::store()`        |
| 3-7-24 | 【一括更新】 正常な献立種別一括更新                            | 正常系 | 有効な献立種別データ配列を提供       | HTTP 200 JSON success                                | `MealTypeController::bulkUpdate()`   |
| 3-7-25 | 【一括更新】 一括更新成功メッセージの確認                      | 正常系 | 正常な一括更新後                     | 更新件数を含む適切なメッセージが返される             | `MealTypeController::bulkUpdate()`   |
| 3-7-26 | 【一括更新】 一括更新後のデータ取得確認                        | 正常系 | 正常な一括更新後                     | 更新された献立種別データが正しく取得される           | `MealTypeController::bulkUpdate()`   |
| 3-7-27 | 【一括更新】 バリデーションエラー（data が未入力）             | 異常系 | data フィールドが未入力              | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-28 | 【一括更新】 バリデーションエラー（data が配列以外）           | 異常系 | data が配列以外の型を提供            | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-29 | 【一括更新】 バリデーションエラー（data が空配列）             | 異常系 | data が空配列（min:1 違反）          | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-30 | 【一括更新】 バリデーションエラー（ID 未入力）                 | 異常系 | data.\*.id が未入力                  | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-31 | 【一括更新】 バリデーションエラー（ID が UUID 形式でない）     | 異常系 | data.\*.id が UUID 形式でない        | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-32 | 【一括更新】 バリデーションエラー（献立種別名未入力）          | 異常系 | data.\*.name が未入力                | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-33 | 【一括更新】 バリデーションエラー（献立種別名が文字列以外）    | 異常系 | data.\*.name が文字列以外            | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-34 | 【一括更新】 バリデーションエラー（献立種別名が 255 文字超過） | 異常系 | data.\*.name が 256 文字以上         | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-35 | 【一括更新】 バリデーションエラー（色 ID 未入力）              | 異常系 | data.\*.colorId が未入力             | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-36 | 【一括更新】 バリデーションエラー（色 ID が UUID 形式でない）  | 異常系 | data.\*.colorId が UUID 形式でない   | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-37 | 【一括更新】 バリデーションエラー（色 ID が存在しない）        | 異常系 | data.\*.colorId が存在しない色 ID    | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-38 | 【一括更新】 バリデーションエラー（order 値が未入力）          | 異常系 | data.\*.order が未入力               | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-39 | 【一括更新】 バリデーションエラー（order 値が数値以外）        | 異常系 | data.\*.order が数値以外             | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-40 | 【一括更新】 バリデーションエラー（order 値が負の値）          | 異常系 | data.\*.order が 0 未満の負の値      | HTTP 422 Validation Error                            | `MealTypeBulkUpdateRequest::rules()` |
| 3-7-41 | 【一括更新】 存在しない献立種別の更新                          | 異常系 | 存在しない ID を含むデータ配列を提供 | HTTP 404 Not Found                                   | `MealTypeController::bulkUpdate()`   |
| 3-7-42 | 【一括更新】 他グループの献立種別更新                          | 異常系 | 他グループの献立種別 ID を提供       | HTTP 404 Not Found                                   | `MealTypeController::bulkUpdate()`   |
| 3-7-43 | 【一括更新】 未認証ユーザー                                    | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                | `MealTypeController::bulkUpdate()`   |
| 3-7-44 | 【一括更新】 グループが存在しない                              | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                        | `MealTypeController::bulkUpdate()`   |
| 3-7-45 | 【一括更新】 データベース接続エラー                            | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                       | `MealTypeController::bulkUpdate()`   |
| 3-7-46 | 【一括更新】 献立種別更新失敗                                  | 異常系 | MealType::update() が失敗            | HTTP 500 Internal Server Error                       | `MealTypeController::bulkUpdate()`   |
| 3-7-47 | 【削除】 正常な献立種別削除                                    | 正常系 | 有効な献立種別 ID を提供             | HTTP 200 JSON success                                | `MealTypeController::destroy()`      |
| 3-7-48 | 【削除】 削除後の order 整理確認                               | 正常系 | 献立種別削除後                       | 残りの献立種別の order が正しく整理される            | `MealTypeController::destroy()`      |
| 3-7-49 | 【削除】 削除成功メッセージの確認                              | 正常系 | 正常な献立種別削除後                 | 削除された献立種別名を含む適切なメッセージが返される | `MealTypeController::destroy()`      |
| 3-7-50 | 【削除】 存在しない献立種別削除                                | 異常系 | 存在しない献立種別 ID を提供         | HTTP 404 Not Found                                   | `MealTypeController::destroy()`      |
| 3-7-51 | 【削除】 他グループの献立種別削除                              | 異常系 | 他グループの献立種別 ID を提供       | HTTP 404 Not Found                                   | `MealTypeController::destroy()`      |
| 3-7-52 | 【削除】 未認証ユーザー                                        | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                                | `MealTypeController::destroy()`      |
| 3-7-53 | 【削除】 グループが存在しない                                  | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity                        | `MealTypeController::destroy()`      |
| 3-7-54 | 【削除】 データベース接続エラー                                | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error                       | `MealTypeController::destroy()`      |
| 3-7-55 | 【削除】 献立種別削除失敗                                      | 異常系 | MealType::delete() が失敗            | HTTP 500 Internal Server Error                       | `MealTypeController::destroy()`      |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
