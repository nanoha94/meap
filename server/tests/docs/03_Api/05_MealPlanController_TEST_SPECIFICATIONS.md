# MealPlanController テストケース詳細仕様

## 概要

MealPlanController のテストケースの詳細仕様を示します。献立の一覧取得、作成、詳細取得、更新、削除、および献立の1食削除（destroyMeal）機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                            | 種別   | 入力条件                                 | 期待される出力                                   | 該当メソッド                     |
| ------ | ------------------------------------------------------------------- | ------ | ---------------------------------------- | ------------------------------------------------ | -------------------------------- |
| 3-5-1  | 【一覧取得】 正常な献立一覧取得                                     | 正常系 | 認証済みユーザー、date_from, date_to をクエリで指定 | HTTP 200 JSON success                            | `MealPlanController::index()`    |
| 3-5-2  | 【一覧取得】 献立データの日付別グループ化確認                       | 正常系 | date_from, date_to をクエリで指定し正常な献立一覧取得後 | data 配列の各要素が 1 日分（id, date, meals）で返される。 | `MealPlanController::index()`    |
| 3-5-3  | 【一覧取得】 レスポンス形式確認                                     | 正常系 | date_from, date_to をクエリで指定し正常な献立一覧取得後 | data 配列の各要素が id, date, meals 形式の JSON で返される | `MealPlanController::index()`    |
| 3-5-4  | 【一覧取得】 date_from・date_to クエリで指定期間の献立一覧取得       | 正常系 | date_from, date_to をクエリで指定（例: ?date_from=2024-01-01&date_to=2024-01-31） | HTTP 200、指定期間の献立のみ返る                  | `MealPlanController::index()`    |
| 3-5-5  | 【一覧取得】 同一日の meals がフラット配列で並び順どおり返ること     | 正常系 | date_from, date_to をクエリで指定し、同一日に複数 meals（order ばらばら）で献立作成後、一覧取得 | HTTP 200、該当日の data.meals が meal の order 昇順→レシピ順（pivot の order＝recipeOrder 昇順）のフラット配列として返る | `MealPlanController::index()`    |
| 3-5-6  | 【一覧取得】 include_ingredients=true で食材一覧付きの献立取得       | 正常系 | 認証済みユーザー、date_from, date_to, include_ingredients=true | HTTP 200、各 meals の要素に ingredients 配列が含まれる | `MealPlanController::index()`    |
| 3-5-7  | 【一覧取得】 include_ingredients なしで食材一覧が含まれないこと      | 正常系 | 認証済みユーザー、date_from, date_to のみ（include_ingredients 省略） | HTTP 200、各 meals の要素に ingredients キーが含まれない | `MealPlanController::index()`    |
| 3-5-8  | 【一覧取得】 include_ingredients=true で食材のレスポンス形式確認      | 正常系 | include_ingredients=true で食材付きレシピの献立取得後 | ingredients 配列の各要素が id, name, quantity, unit, categoryId, order を持つ | `MealPlanController::index()`    |
| 3-5-9  | 【一覧取得】 クエリなしでバリデーションエラー（date_from, date_to 必須） | 異常系 | date_from, date_to クエリを省略          | HTTP 422 Validation Error                        | `MealPlanIndexRequest::rules()`  |
| 3-5-10 | 【一覧取得】 バリデーションエラー（date_from 必須）                  | 異常系 | date_from クエリを省略                   | HTTP 422 Validation Error                        | `MealPlanIndexRequest::rules()`  |
| 3-5-11 | 【一覧取得】 バリデーションエラー（date_to 必須）                    | 異常系 | date_to クエリを省略                     | HTTP 422 Validation Error                        | `MealPlanIndexRequest::rules()`  |
| 3-5-12 | 【一覧取得】 バリデーションエラー（date_from 日付形式）              | 異常系 | date_from が Y-m-d 形式でない            | HTTP 422 Validation Error                        | `MealPlanIndexRequest::rules()`  |
| 3-5-13 | 【一覧取得】 バリデーションエラー（date_to 日付形式）                | 異常系 | date_to が Y-m-d 形式でない              | HTTP 422 Validation Error                        | `MealPlanIndexRequest::rules()`  |
| 3-5-14 | 【一覧取得】 バリデーションエラー（date_to が date_from より前）     | 異常系 | date_to < date_from でリクエスト         | HTTP 422 Validation Error                        | `MealPlanIndexRequest::rules()`  |
| 3-5-15 | 【一覧取得】 バリデーションエラー（include_ingredients が boolean でない） | 異常系 | include_ingredients=abc（非boolean値） | HTTP 422 Validation Error | `MealPlanIndexRequest::rules()` |
| 3-5-16 | 【一覧取得】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::index()`    |
| 3-5-17 | 【一覧取得】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::index()`    |
| 3-5-18 | 【一覧取得】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::index()`    |
| 3-5-19 | 【一覧取得】 MealPlanService 例外                                   | 異常系 | MealPlanService で例外が発生             | HTTP 500 Internal Server Error                   | `MealPlanController::index()`    |
| 3-5-20 | 【新規作成】 正常な献立作成                                         | 正常系 | date と meals（1件以上）を提供           | HTTP 201 Created                                 | `MealPlanController::store()`    |
| 3-5-21 | 【新規作成】 献立に料理を紐づけ                                     | 正常系 | meals 各要素に categoryId と recipeIds を提供 | 各食（meal）に料理が正しく紐づけられる           | `MealPlanController::store()`    |
| 3-5-22 | 【新規作成】 order が保存され show でフラット配列の並び順で返ること  | 正常系 | 複数 meals を異なる order（0, 1, 2）で POST   | HTTP 201、show で取得した meals が meal の order 昇順→レシピ順（pivot の order＝recipeOrder 昇順）のフラット配列として返る | `MealPlanController::store()`    |
| 3-5-23 | 【新規作成】 1食内のレシピ順が保存され show で recipeOrder 順で返ること | 正常系 | 1つの meal に recipes: [{ id: A, order: 0 }, { id: B, order: 1 }, { id: C, order: 2 }] で POST | HTTP 201、show で meal の order → recipeOrder 昇順で返る。DB の meal_recipe_mappings に order 0, 1, 2 が入っていることを確認 | `MealPlanController::store()`    |
| 3-5-24 | 【新規作成】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::store()`    |
| 3-5-25 | 【新規作成】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::store()`    |
| 3-5-26 | 【新規作成】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::store()`    |
| 3-5-27 | 【新規作成】 料理紐づけ失敗                                         | 異常系 | 料理の紐づけ処理が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::store()`    |
| 3-5-28 | 【新規作成】 バリデーションエラー（date 形式）                      | 異常系 | 無効な日付形式を提供                     | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-29 | 【新規作成】 バリデーションエラー（date 必須）                      | 異常系 | date パラメータが未入力                  | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-30 | 【新規作成】 バリデーションエラー（meals 配列形式）                  | 異常系 | meals が配列でない                       | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-31 | 【新規作成】 バリデーションエラー（meals 最小要素数）                | 異常系 | meals が空配列                          | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-32 | 【新規作成】 バリデーションエラー（meals 必須）                      | 異常系 | meals パラメータが未入力                 | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-33 | 【新規作成】 バリデーションエラー（meals.*.categoryId 形式）         | 異常系 | 無効な UUID 形式の categoryId を提供     | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-34 | 【新規作成】 バリデーションエラー（meals.*.categoryId 必須）         | 異常系 | meals の要素に categoryId が未入力      | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-35 | 【新規作成】 バリデーションエラー（meals.*.order 整数）              | 異常系 | meals の要素の order が整数でない（小数・文字列等） | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-36 | 【新規作成】 バリデーションエラー（meals.*.order 必須）              | 異常系 | meals の要素に order が未入力            | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-37 | 【新規作成】 バリデーションエラー（meals.*.recipeIds 配列形式）      | 異常系 | meals の要素の recipeIds が配列でない    | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-38 | 【新規作成】 バリデーションエラー（meals.*.recipeIds 最小要素数）    | 異常系 | meals の要素の recipeIds が空配列         | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-39 | 【新規作成】 バリデーションエラー（meals.*.recipeIds 必須）          | 異常系 | meals の要素に recipeIds が未入力         | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-40 | 【新規作成】 バリデーションエラー（meals.*.recipeIds.* UUID形式）    | 異常系 | recipeIds の個別要素が無効な UUID        | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-41 | 【新規作成】 バリデーションエラー（meals.*.recipeIds.* 必須）        | 異常系 | recipeIds の個別要素が未入力             | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-42 | 【新規作成】 バリデーションエラー（meals.*.recipeIds 重複）           | 異常系 | 同一 meal の recipeIds に同じ recipeId を複数含める | HTTP 422 Validation Error                        | `MealPlanStoreRequest::rules()`  |
| 3-5-43 | 【新規作成】 バリデーションエラー（categoryId 存在チェック）        | 異常系 | 存在しない categoryId を meals に提供    | HTTP 404 Not Found                               | `MealPlanController::store()`    |
| 3-5-44 | 【新規作成】 バリデーションエラー（recipeIds 存在チェック）          | 異常系 | 存在しない recipeId を meals に提供       | HTTP 404 Not Found                               | `MealPlanController::store()`    |
| 3-5-45 | 【詳細取得】 正常な献立詳細取得                                     | 正常系 | 有効な献立 ID を提供                     | HTTP 200 JSON success        | `MealPlanController::show()`     |
| 3-5-46 | 【詳細取得】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::show()`     |
| 3-5-47 | 【詳細取得】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::show()`     |
| 3-5-48 | 【詳細取得】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::show()`     |
| 3-5-49 | 【詳細取得】 日付形式不正で 422                                    | 異常系 | 日付パスが Y-m-d 形式でない              | HTTP 422 Validation Error                       | `MealPlanShowRequest::rules()`  |
| 3-5-50 | 【詳細取得】 存在しない献立詳細取得                                 | 異常系 | 存在しない献立 ID を提供                 | HTTP 404 Not Found                               | `MealPlanController::show()`     |
| 3-5-51 | 【詳細取得】 他グループの献立詳細取得                               | 異常系 | 他グループの献立 ID を提供               | HTTP 404 Not Found                               | `MealPlanController::show()`     |
| 3-5-52 | 【更新】 正常な献立更新                                             | 正常系 | 有効な meals を提供（body に date は含めない） | HTTP 200 JSON success                            | `MealPlanController::update()`   |
| 3-5-53 | 【更新】 献立の料理更新                                             | 正常系 | meals 各要素に categoryId と recipeIds を提供 | 各食の紐づけが正しく更新される                   | `MealPlanController::update()`   |
| 3-5-54 | 【更新】 既存の meal が存在する場合の更新                           | 正常系 | 献立に既に紐づく meal の id を meals に含めて更新リクエスト | 既存 meal のカテゴリ・レシピが正しく更新され、HTTP 200 で返る | `MealPlanController::update()`   |
| 3-5-55 | 【更新】 新規 meal を追加する場合                                   | 正常系 | meals に id を含まない（または null）要素を送信 | 新規 meal が作成され献立に紐づき、HTTP 200 で返る | `MealPlanController::update()`   |
| 3-5-56 | 【更新】 既存の meal を削除する場合                                 | 正常系 | 既存の meal のうち、更新 meals に含めない id がある | 対象外の既存 meal が削除され、meals に含めた meal のみ残る | `MealPlanController::update()`   |
| 3-5-57 | 【更新】 既存 meal の更新・新規 meal の追加・既存 meal の削除を同時に行う場合 | 正常系 | 1回の更新で、既存 meal の id を含む要素・id を含まない要素を送り、既存 meal の一部を meals に含めない | 既存 meal は更新され、新規 meal が追加され、meals に含めなかった既存 meal は削除され、HTTP 200 で正しい meals が返る | `MealPlanController::update()`   |
| 3-5-58 | 【更新】 更新成功メッセージの確認                                   | 正常系 | 正常な献立更新後                         | 更新された献立の日付を含む適切なメッセージが返される | `MealPlanController::update()`   |
| 3-5-59 | 【更新】 order が反映され show でフラット配列の並び順で返ること      | 正常系 | 既存献立の meals の order を入れ替えて PUT   | HTTP 200、show で取得した meals が meal の order 昇順→レシピ順（pivot の order＝recipeOrder 昇順）のフラット配列として返る | `MealPlanController::update()`   |
| 3-5-60 | 【更新】 1食内のレシピ順を変更すると recipeOrder が更新され show で反映されること | 正常系 | 既存献立の同じ meal の recipes の order を変えて PUT（例: [{ id: B, order: 0 }, { id: A, order: 1 }, { id: C, order: 2 }]） | HTTP 200、show で recipeOrder が期待どおりであること、meal_recipe_mappings の order が更新されていることを確認 | `MealPlanController::update()`   |
| 3-5-61 | 【更新】 未認証ユーザー                                             | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::update()`   |
| 3-5-62 | 【更新】 グループが存在しない                                       | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::update()`   |
| 3-5-63 | 【更新】 データベース接続エラー                                     | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::update()`   |
| 3-5-64 | 【更新】 存在しない献立更新                                         | 異常系 | 存在しない献立 ID を提供                 | HTTP 404 Not Found                               | `MealPlanController::update()`   |
| 3-5-65 | 【更新】 他グループの献立更新                                       | 異常系 | 他グループの献立 ID を提供               | HTTP 404 Not Found                               | `MealPlanController::update()`   |
| 3-5-66 | 【更新】 バリデーションエラー（meals 配列形式）                      | 異常系 | meals が配列でない                       | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-67 | 【更新】 バリデーションエラー（meals 最小要素数）                    | 異常系 | meals が空配列                           | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-68 | 【更新】 バリデーションエラー（meals 必須）                         | 異常系 | meals パラメータが未入力                 | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-69 | 【更新】 バリデーションエラー（meals.*.id 形式）                      | 異常系 | meals の要素に無効な UUID の id を提供   | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-70 | 【更新】 バリデーションエラー（meals.*.categoryId 形式）              | 異常系 | 無効な UUID 形式の categoryId を提供     | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-71 | 【更新】 バリデーションエラー（meals.*.categoryId 必須）             | 異常系 | meals の要素に categoryId が未入力      | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-72 | 【更新】 バリデーションエラー（meals.*.order 整数）                   | 異常系 | meals の要素の order が整数でない        | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-73 | 【更新】 バリデーションエラー（meals.*.order 必須）                   | 異常系 | meals の要素に order が未入力            | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-74 | 【更新】 バリデーションエラー（meals.*.recipeIds 配列形式）           | 異常系 | meals の要素の recipeIds が配列でない    | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-75 | 【更新】 バリデーションエラー（meals.*.recipeIds 最小要素数）         | 異常系 | meals の要素の recipeIds が空配列         | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-76 | 【更新】 バリデーションエラー（meals.*.recipeIds 必須）              | 異常系 | meals の要素に recipeIds が未入力         | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-77 | 【更新】 バリデーションエラー（meals.*.recipeIds.* UUID形式）         | 異常系 | recipeIds の個別要素が無効な UUID        | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-78 | 【更新】 バリデーションエラー（meals.*.recipeIds.* 必須）             | 異常系 | recipeIds の個別要素が未入力             | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-79 | 【更新】 バリデーションエラー（meals.*.recipeIds 重複）               | 異常系 | 同一 meal の recipeIds に同じ recipeId を複数含める | HTTP 422 Validation Error                        | `MealPlanUpdateRequest::rules()` |
| 3-5-80 | 【更新】 バリデーションエラー（categoryId 存在チェック）             | 異常系 | 存在しない categoryId を meals に提供    | HTTP 404 Not Found                               | `MealPlanController::update()`   |
| 3-5-81 | 【更新】 バリデーションエラー（recipeIds 存在チェック）              | 異常系 | 存在しない recipeId を meals に提供      | HTTP 404 Not Found                               | `MealPlanController::update()`   |
| 3-5-82 | 【削除】 正常な献立削除                                             | 正常系 | 有効な献立 ID を提供                     | HTTP 200、「献立(日付)を削除しました。」形式のメッセージ | `MealPlanController::destroy()`  |
| 3-5-83 | 【削除】 削除成功メッセージの確認                                   | 正常系 | 正常な献立削除後                         | 「献立(2024-01-15)を削除しました。」が返される | `MealPlanController::destroy()`  |
| 3-5-84 | 【削除】 未認証ユーザー                                             | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::destroy()`  |
| 3-5-85 | 【削除】 グループが存在しない                                       | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::destroy()`  |
| 3-5-86 | 【削除】 データベース接続エラー                                     | 異常系 | データベース接続が失敗                   | HTTP 500 Internal Server Error                   | `MealPlanController::destroy()`  |
| 3-5-87 | 【削除】 存在しない献立削除                                         | 異常系 | 存在しない献立 ID を提供                 | HTTP 404 Not Found                               | `MealPlanController::destroy()`  |
| 3-5-88 | 【削除】 他グループの献立削除                                       | 異常系 | 他グループの献立 ID を提供               | HTTP 404 Not Found                               | `MealPlanController::destroy()`  |
| 3-5-89 | 【1食削除】 正常に献立の1食を削除                                   | 正常系 | 有効な献立ID・食事IDを提供               | HTTP 200、「献立(日付 / カテゴリ名)を削除しました。」形式のメッセージ、該当 meal のみ削除され献立は残る | `MealPlanController::destroyMeal()` |
| 3-5-90 | 【1食削除】 複数食のうち1食のみ削除                                  | 正常系 | 複数 meal が紐づく献立の1食IDを指定      | HTTP 200、上記形式のメッセージ、指定 meal のみ削除され他 meal と献立は残る | `MealPlanController::destroyMeal()` |
| 3-5-91 | 【1食削除】 未認証ユーザー                                          | 異常系 | 認証されていないユーザー                 | HTTP 401 Unauthorized                            | `MealPlanController::destroyMeal()` |
| 3-5-92 | 【1食削除】 グループが存在しない                                    | 異常系 | ユーザーにグループが紐づいていない       | HTTP 422 Unprocessable Entity                    | `MealPlanController::destroyMeal()` |
| 3-5-93 | 【1食削除】 存在しない献立ID                                        | 異常系 | 存在しない献立 ID を提供                 | HTTP 404 Not Found                               | `MealPlanController::destroyMeal()` |
| 3-5-94 | 【1食削除】 存在しない食事ID                                        | 異常系 | 存在しない食事 ID を提供                 | HTTP 404 Not Found（献立の1食が見つかりません）  | `MealPlanController::destroyMeal()` |
| 3-5-95 | 【1食削除】 他グループの献立に属する食事を削除                       | 異常系 | 他グループの献立に属する mealId を提供   | HTTP 404 Not Found                               | `MealPlanController::destroyMeal()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/MealPlanControllerTest.php --stop-on-failure
```
