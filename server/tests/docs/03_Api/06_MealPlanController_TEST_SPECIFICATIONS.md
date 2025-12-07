# MealPlanController テストケース詳細仕様

## 概要

MealPlanController のテストケースの詳細仕様を示します。献立の一覧取得、作成、詳細取得、更新、削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                            | 種別   | 入力条件                                 | 期待される出力                                   | 該当メソッド                     |
| ------ | ------------------------------------------------------------------- | ------ | ---------------------------------------- | ------------------------------------------------ | -------------------------------- |
| 3-6-1  | 【一覧取得】 正常な献立一覧取得                                     | 正常系 | 認証済みユーザー                         | HTTP 200 JSON success                            | `MealPlanController::index()`    |
| 3-6-2  | 【一覧取得】 献立データの日付別グループ化確認                       | 正常系 | 正常な献立一覧取得後                     | 献立が日付別にグループ化される                   | `MealPlanController::index()`    |
| 3-6-3  | 【一覧取得】 レスポンス形式確認                                     | 正常系 | 正常な献立一覧取得後                     | 正しい JSON 形式でレスポンスが返される           | `MealPlanController::index()`    |
| 3-6-4  | 【一覧取得】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::index()`    |
| 3-6-5  | 【一覧取得】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::index()`    |
| 3-6-6  | 【一覧取得】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::index()`    |
| 3-6-7  | 【一覧取得】 MealPlanService 例外                                   | 異常系 | MealPlanService で例外が発生             | HTTP 500 Internal Server Error                   | `MealPlanController::index()`    |
| 3-6-8  | 【新規作成】 正常な献立作成                                         | 正常系 | 有効な献立データを提供                   | HTTP 201 Created                                 | `MealPlanController::store()`    |
| 3-6-9  | 【新規作成】 献立に料理を紐づけ                                     | 正常系 | 献立作成時に料理 ID を提供               | 料理が正しく献立に紐づけられる                   | `MealPlanController::store()`    |
| 3-6-10 | 【新規作成】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::store()`    |
| 3-6-11 | 【新規作成】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::store()`    |
| 3-6-12 | 【新規作成】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::store()`    |
| 3-6-13 | 【新規作成】 料理紐づけ失敗                                         | 異常系 | 料理の紐づけ処理が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::store()`    |
| 3-6-14 | 【新規作成】 バリデーションエラー（date 必須）                      | 異常系 | date パラメータが未入力                  | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-15 | 【新規作成】 バリデーションエラー（date 形式）                      | 異常系 | 無効な日付形式を提供                     | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-16 | 【新規作成】 バリデーションエラー（mealCategoryId 必須）            | 異常系 | mealCategoryId パラメータが未入力        | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-17 | 【新規作成】 バリデーションエラー（mealCategoryId 形式）            | 異常系 | 無効な UUID 形式の mealCategoryId を提供 | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-18 | 【新規作成】 バリデーションエラー（mealCategoryId 存在チェック）    | 異常系 | 存在しない mealCategoryId を提供         | HTTP 404 Not Found                               | `MealPlanController::store()`    |
| 3-6-19 | 【新規作成】 バリデーションエラー（menu 必須）                      | 異常系 | menu パラメータが未入力                  | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-20 | 【新規作成】 バリデーションエラー（menu 配列形式）                  | 異常系 | menu が配列でない                        | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-21 | 【新規作成】 バリデーションエラー（menu 最小要素数）                | 異常系 | menu が空配列                            | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-22 | 【新規作成】 バリデーションエラー（recipeIds 必須）                 | 異常系 | recipeIds パラメータが未入力             | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-23 | 【新規作成】 バリデーションエラー（recipeIds 配列形式）             | 異常系 | recipeIds が配列でない                   | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-24 | 【新規作成】 バリデーションエラー（recipeIds 最小要素数）           | 異常系 | recipeIds が空配列                       | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-25 | 【新規作成】 バリデーションエラー（recipeIds 個別要素必須）         | 異常系 | recipeIds の個別要素が未入力             | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-26 | 【新規作成】 バリデーションエラー（recipeIds 個別要素形式）         | 異常系 | recipeIds の個別要素が無効な UUID        | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-27 | 【新規作成】 バリデーションエラー（recipeIds 個別要素存在チェック） | 異常系 | 存在しない recipeId を提供               | HTTP 404 Not Found                               | `MealPlanController::store()`    |
| 3-6-28 | 【新規作成】 バリデーションエラー（menuCategoryId 必須）            | 異常系 | menuCategoryId パラメータが未入力        | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-29 | 【新規作成】 バリデーションエラー（menuCategoryId 形式）            | 異常系 | 無効な UUID 形式の menuCategoryId を提供 | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-6-30 | 【新規作成】 バリデーションエラー（menuCategoryId 存在チェック）    | 異常系 | 存在しない menuCategoryId を提供         | HTTP 404 Not Found                               | `MealPlanController::store()`    |
| 3-6-31 | 【詳細取得】 正常な献立詳細取得                                     | 正常系 | 有効な献立 ID を提供                     | HTTP 200 JSON success                            | `MealPlanController::show()`     |
| 3-6-32 | 【詳細取得】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::show()`     |
| 3-6-33 | 【詳細取得】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::show()`     |
| 3-6-34 | 【詳細取得】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::show()`     |
| 3-6-35 | 【詳細取得】 存在しない献立詳細取得                                 | 異常系 | 存在しない献立 ID を提供                 | HTTP 404 Not Found                               | `MealPlanController::show()`     |
| 3-6-36 | 【詳細取得】 他グループの献立詳細取得                               | 異常系 | 他グループの献立 ID を提供               | HTTP 404 Not Found                               | `MealPlanController::show()`     |
| 3-6-37 | 【更新】 正常な献立更新                                             | 正常系 | 有効な献立データを提供                   | HTTP 200 JSON success                            | `MealPlanController::update()`   |
| 3-6-38 | 【更新】 献立の料理更新                                             | 正常系 | 献立更新時に料理 ID を提供               | 料理の紐づけが正しく更新される                   | `MealPlanController::update()`   |
| 3-6-39 | 【更新】 更新成功メッセージの確認                                   | 正常系 | 正常な献立更新後                         | 更新された献立名を含む適切なメッセージが返される | `MealPlanController::update()`   |
| 3-6-40 | 【更新】 未認証ユーザー                                             | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::update()`   |
| 3-6-41 | 【更新】 グループが存在しない                                       | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::update()`   |
| 3-6-42 | 【更新】 データベース接続エラー                                     | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::update()`   |
| 3-6-43 | 【更新】 存在しない献立更新                                         | 異常系 | 存在しない献立 ID を提供                 | HTTP 404 Not Found                               | `MealPlanController::update()`   |
| 3-6-44 | 【更新】 他グループの献立更新                                       | 異常系 | 他グループの献立 ID を提供               | HTTP 404 Not Found                               | `MealPlanController::update()`   |
| 3-6-45 | 【更新】 バリデーションエラー（date 必須）                          | 異常系 | date パラメータが未入力                  | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-46 | 【更新】 バリデーションエラー（date 形式）                          | 異常系 | 無効な日付形式を提供                     | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-47 | 【更新】 バリデーションエラー（mealCategoryId 必須）                | 異常系 | mealCategoryId パラメータが未入力        | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-48 | 【更新】 バリデーションエラー（mealCategoryId 形式）                | 異常系 | 無効な UUID 形式の mealCategoryId を提供 | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-49 | 【更新】 バリデーションエラー（mealCategoryId 存在チェック）        | 異常系 | 存在しない mealCategoryId を提供         | HTTP 404 Not Found                               | `MealPlanController::update()`   |
| 3-6-50 | 【更新】 バリデーションエラー（menu 必須）                          | 異常系 | menu パラメータが未入力                  | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-51 | 【更新】 バリデーションエラー（menu 配列形式）                      | 異常系 | menu が配列でない                        | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-52 | 【更新】 バリデーションエラー（menu 最小要素数）                    | 異常系 | menu が空配列                            | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-53 | 【更新】 バリデーションエラー（recipeIds 必須）                     | 異常系 | recipeIds パラメータが未入力             | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-54 | 【更新】 バリデーションエラー（recipeIds 配列形式）                 | 異常系 | recipeIds が配列でない                   | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-55 | 【更新】 バリデーションエラー（recipeIds 最小要素数）               | 異常系 | recipeIds が空配列                       | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-56 | 【更新】 バリデーションエラー（recipeIds 個別要素必須）             | 異常系 | recipeIds の個別要素が未入力             | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-57 | 【更新】 バリデーションエラー（recipeIds 個別要素形式）             | 異常系 | recipeIds の個別要素が無効な UUID        | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-58 | 【更新】 バリデーションエラー（recipeIds 個別要素存在チェック）     | 異常系 | 存在しない recipeId を提供               | HTTP 404 Not Found                               | `MealPlanController::update()`   |
| 3-6-59 | 【更新】 バリデーションエラー（menuCategoryId 必須）                | 異常系 | menuCategoryId パラメータが未入力        | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-60 | 【更新】 バリデーションエラー（menuCategoryId 形式）                | 異常系 | 無効な UUID 形式の menuCategoryId を提供 | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-6-61 | 【更新】 バリデーションエラー（menuCategoryId 存在チェック）        | 異常系 | 存在しない menuCategoryId を提供         | HTTP 404 Not Found                               | `MealPlanController::update()`   |
| 3-6-62 | 【削除】 正常な献立削除                                             | 正常系 | 有効な献立 ID を提供                     | HTTP 200 JSON success                            | `MealPlanController::destroy()`  |
| 3-6-63 | 【削除】 削除成功メッセージの確認                                   | 正常系 | 正常な献立削除後                         | 削除された献立名を含む適切なメッセージが返される | `MealPlanController::destroy()`  |
| 3-6-64 | 【削除】 未認証ユーザー                                             | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::destroy()`  |
| 3-6-65 | 【削除】 グループが存在しない                                       | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::destroy()`  |
| 3-6-66 | 【削除】 データベース接続エラー                                     | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::destroy()`  |
| 3-6-67 | 【削除】 存在しない献立削除                                         | 異常系 | 存在しない献立 ID を提供                 | HTTP 404 Not Found                               | `MealPlanController::destroy()`  |
| 3-6-68 | 【削除】 他グループの献立削除                                       | 異常系 | 他グループの献立 ID を提供               | HTTP 404 Not Found                               | `MealPlanController::destroy()`  |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
