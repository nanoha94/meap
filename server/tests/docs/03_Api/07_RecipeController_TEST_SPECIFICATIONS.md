# RecipeController テストケース詳細仕様

## 概要

RecipeController のテストケースの詳細仕様を示します。料理の一覧取得、作成、詳細取得、更新、削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID      | テスト名                                                                          | 種別   | 入力条件                                                | 期待される出力                        | 該当メソッド                   |
| ------- | --------------------------------------------------------------------------------- | ------ | ------------------------------------------------------- | ------------------------------------- | ------------------------------ |
| 3-7-1   | 【一覧取得】 正常な料理一覧取得                                                   | 正常系 | 認証済みユーザー                                        | HTTP 200 JSON success、limit/offset がレスポンスに含まれる | `RecipeController::index()`    |
| 3-7-2   | 【一覧取得】 レスポンス形式確認                                                   | 正常系 | 正常な料理一覧取得後                                    | 正しい JSON 形式でレスポンスが返る、limit/offset がレスポンスに含まれる | `RecipeController::index()`    |
| 3-7-3   | 【一覧取得】 sort=name&order=asc で名前昇順にソートされていること                 | 正常系 | 認証済みユーザー、sort=name&order=asc を指定            | 名前が昇順で返る                      | `RecipeController::index()`    |
| 3-7-4   | 【一覧取得】 sort=name&order=desc で名前降順にソートされていること                | 正常系 | 認証済みユーザー、sort=name&order=desc を指定           | 名前が降順で返る                      | `RecipeController::index()`    |
| 3-7-5   | 【一覧取得】 sort=created_at&order=desc で作成日降順にソートされていること       | 正常系 | 認証済みユーザー、sort=created_at&order=desc を指定     | 作成日が降順で返る                    | `RecipeController::index()`    |
| 3-7-6   | 【一覧取得】 sort=created_at&order=asc で作成日昇順にソートされていること        | 正常系 | 認証済みユーザー、sort=created_at&order=asc を指定      | 作成日が昇順で返る                    | `RecipeController::index()`    |
| 3-7-7   | 【一覧取得】 sort=last_planned_date&order=desc で献立日降順、NULL は末尾になること | 正常系 | 認証済みユーザー、sort=last_planned_date&order=desc を指定 | 献立日が降順、献立日が NULL のレシピは末尾 | `RecipeController::index()`    |
| 3-7-8   | 【一覧取得】 sort=last_planned_date&order=asc で献立日昇順、NULL は末尾になること | 正常系 | 認証済みユーザー、sort=last_planned_date&order=asc を指定  | 献立日が昇順、献立日が NULL のレシピは末尾 | `RecipeController::index()`    |
| 3-7-9   | 【一覧取得】 パラメータ未指定時のデフォルト（created_at desc）を確認              | 正常系 | 認証済みユーザー、パラメータ未指定                      | created_at 降順で返る（デフォルト）   | `RecipeController::index()`    |
| 3-7-10  | 【一覧取得】 recipe_name を指定して料理名で絞り込みできること                      | 正常系 | 認証済みユーザー、recipe_name を指定                    | HTTP 200、該当レシピのみ返る          | `RecipeController::index()`    |
| 3-7-11  | 【一覧取得】 ingredient_name を指定して食材名で絞り込みできること                  | 正常系 | 認証済みユーザー、ingredient_name を指定                | HTTP 200、該当レシピのみ返る          | `RecipeController::index()`    |
| 3-7-12  | 【一覧取得】 category_ids を指定してカテゴリで絞り込みできること（指定したいずれかのカテゴリに属するレシピが返る） | 正常系 | 認証済みユーザー、category_ids を指定 | HTTP 200、該当レシピのみ返る          | `RecipeController::index()`    |
| 3-7-13  | 【一覧取得】 category_ids を指定してカテゴリで絞り込みできること（指定したいずれかのカテゴリに属するレシピが返る） | 正常系 | 認証済みユーザー、category_ids を指定 | HTTP 200、該当レシピのみ返る          | `RecipeController::index()`    |
| 3-7-14  | 【一覧取得】 last_planned_date_from / last_planned_date_to を指定して前回献立日で絞り込みできること | 正常系 | 認証済みユーザー、last_planned_date_from / last_planned_date_to を指定 | HTTP 200、該当レシピのみ返る | `RecipeController::index()`    |
| 3-7-15  | 【一覧取得】 last_planned_date_from のみ指定して前回献立日で絞り込みできること（その日以降） | 正常系 | 認証済みユーザー、last_planned_date_from のみ指定 | HTTP 200、前回献立日が指定日以上のレシピのみ返る | `RecipeController::index()`    |
| 3-7-16  | 【一覧取得】 last_planned_date_to のみ指定して前回献立日で絞り込みできること（その日以前） | 正常系 | 認証済みユーザー、last_planned_date_to のみ指定 | HTTP 200、前回献立日が指定日以下のレシピのみ返る | `RecipeController::index()`    |
| 3-7-17  | 【一覧取得】 複数フィルタパラメータを同時に指定した場合、AND 条件で絞り込みできること | 正常系 | 認証済みユーザー、recipe_name と category_ids など複数パラメータを指定 | HTTP 200、すべての条件を満たすレシピのみ返る | `RecipeController::index()`    |
| 3-7-18  | 【一覧取得】 絞り込みパラメータをすべて指定した場合、AND 条件で絞り込みできること   | 正常系 | 認証済みユーザー、recipe_name / ingredient_name / category_ids / last_planned_date_from / last_planned_date_to をすべて指定 | HTTP 200、すべての条件を満たすレシピのみ返る | `RecipeController::index()`    |
| 3-7-19  | 【一覧取得】 limit/offset 指定時に正しい件数・位置で取得できること                 | 正常系 | 認証済みユーザー、limit と offset を指定               | HTTP 200、指定した件数・位置で取得、limit/offset がレスポンスに含まれる | `RecipeController::index()`    |
| 3-7-20  | 【一覧取得】 limit のみ指定時にデフォルト offset=0 で取得できること                | 正常系 | 認証済みユーザー、limit のみ指定                        | HTTP 200、先頭から取得、limit/offset がレスポンスに含まれる | `RecipeController::index()`    |
| 3-7-21  | 【一覧取得】 offset のみ指定時にデフォルト limit=15 で取得できること               | 正常系 | 認証済みユーザー、offset のみ指定                       | HTTP 200、15 件取得、limit/offset がレスポンスに含まれる | `RecipeController::index()`    |
| 3-7-22  | 【一覧取得】 未認証ユーザー                                                       | 異常系 | 認証されていないユーザー                                | HTTP 401 Unauthorized                 | `RecipeController::index()`    |
| 3-7-23  | 【一覧取得】 グループが存在しない                                                 | 異常系 | ユーザーにグループが紐づいていない                      | HTTP 422 Unprocessable Entity         | `RecipeController::index()`    |
| 3-7-24  | 【一覧取得】 バリデーションエラー（limit が整数でない）                            | 異常系 | 認証済みユーザー、limit に整数でない値を指定            | HTTP 422 Validation Error             | `RecipeIndexRequest::rules()`  |
| 3-7-25  | 【一覧取得】 バリデーションエラー（limit が 1 未満）                               | 異常系 | 認証済みユーザー、limit に 0 以下を指定                 | HTTP 422 Validation Error             | `RecipeIndexRequest::rules()`  |
| 3-7-26  | 【一覧取得】 バリデーションエラー（limit が 100 超過）                             | 異常系 | 認証済みユーザー、limit に 101 以上を指定               | HTTP 422 Validation Error             | `RecipeIndexRequest::rules()`  |
| 3-7-27  | 【一覧取得】 バリデーションエラー（offset が整数でない）                          | 異常系 | 認証済みユーザー、offset に整数でない値を指定           | HTTP 422 Validation Error             | `RecipeIndexRequest::rules()`  |
| 3-7-28  | 【一覧取得】 バリデーションエラー（offset が 0 未満）                              | 異常系 | 認証済みユーザー、offset に負の値を指定                 | HTTP 422 Validation Error             | `RecipeIndexRequest::rules()`  |
| 3-7-29  | 【一覧取得】 バリデーションエラー（sort が不正な値）                              | 異常系 | 認証済みユーザー、sort に不正な値を指定                 | HTTP 422 Validation Error             | `RecipeController::index()`    |
| 3-7-30  | 【一覧取得】 バリデーションエラー（order が不正な値）                             | 異常系 | 認証済みユーザー、order に不正な値を指定                | HTTP 422 Validation Error             | `RecipeController::index()`    |
| 3-7-31  | 【一覧取得】 データベース接続エラー                                               | 異常系 | データベース接続が失敗                                  | HTTP 500 Internal Server Error        | `RecipeController::index()`    |
| 3-7-32  | 【一覧取得】 RecipeService 例外                                                   | 異常系 | RecipeService で例外が発生                              | HTTP 500 Internal Server Error        | `RecipeController::index()`    |
| 3-7-33  | 【一覧取得】 バリデーションエラー（recipe_name が 255 文字超過）                   | 異常系 | 認証済みユーザー、recipe_name が 256 文字以上           | HTTP 422 Validation Error             | `RecipeController::index()`    |
| 3-7-34  | 【一覧取得】 バリデーションエラー（ingredient_name が 255 文字超過）               | 異常系 | 認証済みユーザー、ingredient_name が 256 文字以上       | HTTP 422 Validation Error             | `RecipeController::index()`    |
| 3-7-35  | 【一覧取得】 バリデーションエラー（category_ids が配列でない）                     | 異常系 | 認証済みユーザー、category_ids が配列でない             | HTTP 422 Validation Error             | `RecipeController::index()`    |
| 3-7-36  | 【一覧取得】 バリデーションエラー（category_ids.\* が UUID 形式でない）            | 異常系 | 認証済みユーザー、category_ids.\* が UUID 形式でない    | HTTP 422 Validation Error             | `RecipeController::index()`    |
| 3-7-37  | 【一覧取得】 バリデーションエラー（category_ids.\* が存在しない ID）              | 異常系 | 認証済みユーザー、存在しないカテゴリ ID を category_ids に指定 | HTTP 422 Validation Error | `RecipeController::index()`    |
| 3-7-38  | 【一覧取得】 バリデーションエラー（last_planned_date_from が日付形式でない）       | 異常系 | 認証済みユーザー、last_planned_date_from が日付形式でない | HTTP 422 Validation Error             | `RecipeController::index()`    |
| 3-7-39  | 【一覧取得】 バリデーションエラー（last_planned_date_to が日付形式でない）         | 異常系 | 認証済みユーザー、last_planned_date_to が日付形式でない | HTTP 422 Validation Error             | `RecipeController::index()`    |
| 3-7-40  | 【一覧取得】 バリデーションエラー（last_planned_date_to が last_planned_date_from より前） | 異常系 | 認証済みユーザー、last_planned_date_to が last_planned_date_from より前 | HTTP 422 Validation Error | `RecipeController::index()`    |
| 3-7-41  | 【新規作成】 正常な料理作成                                                       | 正常系 | 有効な料理データを提供                                  | HTTP 201 Created                      | `RecipeController::store()`    |
| 3-7-42  | 【新規作成】 最小限のデータで料理作成                                             | 正常系 | name と serving_count を指定して料理を作成              | HTTP 201 Created                      | `RecipeController::store()`    |
| 3-7-43  | 【新規作成】 料理にカテゴリを紐づけ                                               | 正常系 | 料理作成時にカテゴリデータを提供                        | カテゴリが料理に紐づけられる          | `RecipeController::store()`    |
| 3-7-44  | 【新規作成】 料理に食材を紐づけ                                                   | 正常系 | 料理作成時に食材データを提供                            | 食材が料理に紐づけられる              | `RecipeController::store()`    |
| 3-7-45  | 【新規作成】 最小限の必須フィールドのみで食材を紐づけ                             | 正常系 | 食材の name/unitId/categoryId のみ指定                  | 食材が料理に紐づけられる              | `RecipeController::store()`    |
| 3-7-46  | 【新規作成】 料理に手順を紐づけ                                                   | 正常系 | 料理作成時に手順データを提供                            | 手順が料理に紐づけられる              | `RecipeController::store()`    |
| 3-7-47  | 【新規作成】 最小限の必須フィールドのみで手順を紐づけ                             | 正常系 | 手順の instruction/order のみ指定                       | 手順が料理に紐づけられる              | `RecipeController::store()`    |
| 3-7-48  | 【新規作成】 料理に画像を紐づけ                                                   | 正常系 | 料理作成時に画像データを提供                            | 画像が料理に紐づけられる              | `RecipeController::store()`    |
| 3-7-49  | 【新規作成】 requires_quantity=true の食材単位で数量指定                          | 正常系 | requires_quantity=true の単位で quantity 指定           | HTTP 201 Created、数量付きで作成      | `RecipeController::store()`    |
| 3-7-50  | 【新規作成】 requires_quantity=false の食材単位で数量指定                         | 正常系 | requires_quantity=false の単位で quantity 指定         | HTTP 201 Created、数量なしで作成      | `RecipeController::store()`    |
| 3-7-51  | 【新規作成】 requires_quantity=false の食材単位で数量省略                          | 正常系 | requires_quantity=false の単位で quantity 省略          | HTTP 201 Created、数量なしで作成      | `RecipeController::store()`    |
| 3-7-52  | 【新規作成】 すべての項目を含む料理作成                                           | 正常系 | name/servingCount/url/memo/thumbnailId/categoryIds/ingredients/steps をすべて指定 | HTTP 201 Created、すべての項目が正しく保存される | `RecipeController::store()`    |
| 3-7-53  | 【新規作成】 serving_count が null でも正常に作成できる                           | 正常系 | serving_count が null                                   | HTTP 201 Created                      | `RecipeController::store()`    |
| 3-7-54  | 【新規作成】 バリデーションエラー（name 未入力）                                  | 異常系 | name が未入力                                           | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-55  | 【新規作成】 バリデーションエラー（name が文字列でない）                          | 異常系 | name が文字列でない                                     | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-56  | 【新規作成】 バリデーションエラー（name が 255 文字超過）                         | 異常系 | name が 256 文字以上                                    | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-57  | 【新規作成】 バリデーションエラー（url が文字列でない）                           | 異常系 | url が文字列でない                                      | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-58  | 【新規作成】 バリデーションエラー（url が 2048 文字超過）                         | 異常系 | url が 2049 文字以上                                    | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-59  | 【新規作成】 バリデーションエラー（thumbnailId が UUID 形式でない）               | 異常系 | thumbnailId が UUID 形式でない                          | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-60  | 【新規作成】 バリデーションエラー（categoryIds が配列でない）                     | 異常系 | categoryIds が配列でない                                | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-61  | 【新規作成】 バリデーションエラー（categoryIds.\* が UUID 形式でない）            | 異常系 | categoryIds.\* が UUID 形式でない                       | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-62  | 【新規作成】 バリデーションエラー（categoryIds.\* 未入力）                        | 異常系 | categoryIds.\* が未入力                                 | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-63  | 【新規作成】 バリデーションエラー（ingredients が配列でない）                     | 異常系 | ingredients が配列でない                                | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-64  | 【新規作成】 バリデーションエラー（ingredients.\*.id が UUID 形式でない）         | 異常系 | ingredients.\*.id が UUID 形式でない                    | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-65  | 【新規作成】 バリデーションエラー（ingredients.\*.name 未入力）                   | 異常系 | ingredients.\*.name が未入力                            | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-66  | 【新規作成】 バリデーションエラー（ingredients.\*.name が文字列でない）           | 異常系 | ingredients.\*.name が文字列でない                      | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-67  | 【新規作成】 バリデーションエラー（ingredients.\*.name が 255 文字超過）          | 異常系 | ingredients.\*.name が 256 文字以上                     | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-68  | 【新規作成】 バリデーションエラー（ingredients.\*.unitId 未入力）                 | 異常系 | ingredients.\*.unitId が未入力                          | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-69  | 【新規作成】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない）     | 異常系 | ingredients.\*.unitId が UUID 形式でない                | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-70  | 【新規作成】 バリデーションエラー（ingredients.\*.categoryId 未入力）             | 異常系 | ingredients.\*.categoryId が未入力                      | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-71  | 【新規作成】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない） | 異常系 | ingredients.\*.categoryId が UUID 形式でない            | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-72  | 【新規作成】 バリデーションエラー（ingredients.\*.quantity が数値でない）         | 異常系 | ingredients.\*.quantity が数値でない                    | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-73  | 【新規作成】 バリデーションエラー（ingredients.\*.order が整数でない）            | 異常系 | ingredients.\*.order が整数でない                       | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-74  | 【新規作成】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で数量省略） | 異常系 | ingredients\*.requires_quantity=true の単位で quantity 省略 | HTTP 422 Validation Error          | `RecipeStoreRequest::rules()`  |
| 3-7-75  | 【新規作成】 バリデーションエラー（ingredients.\*.order が負の値）                | 異常系 | ingredients.\*.order が 0 未満の負の値                  | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-76  | 【新規作成】 バリデーションエラー（steps が配列でない）                           | 異常系 | steps が配列でない                                      | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-77  | 【新規作成】 バリデーションエラー（steps.\*.id が UUID 形式でない）               | 異常系 | steps.\*.id が UUID 形式でない                           | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-78  | 【新規作成】 バリデーションエラー（steps.\*.instruction 未入力）                   | 異常系 | steps.\*.instruction が未入力                            | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-79  | 【新規作成】 バリデーションエラー（steps.\*.instruction が文字列でない）          | 異常系 | steps.\*.instruction が文字列でない                     | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-80  | 【新規作成】 バリデーションエラー（steps.\*.instruction が 255 文字超過）         | 異常系 | steps.\*.instruction が 256 文字以上                    | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-81  | 【新規作成】 バリデーションエラー（steps.\*.imageId が UUID 形式でない）         | 異常系 | steps.\*.imageId が UUID 形式でない                     | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-82  | 【新規作成】 バリデーションエラー（steps.\*.order 未入力）                        | 異常系 | steps.\*.order が未入力                                 | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-83  | 【新規作成】 バリデーションエラー（steps.\*.order が整数でない）                  | 異常系 | steps.\*.order が整数でない                             | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-84  | 【新規作成】 バリデーションエラー（steps.\*.order が負の値）                      | 異常系 | steps.\*.order が 0 未満の負の値                        | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-85  | 【新規作成】 バリデーションエラー（memo が文字列でない）                          | 異常系 | memo が文字列でない                                     | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-86  | 【新規作成】 バリデーションエラー（memo が 255 文字超過）                         | 異常系 | memo が 256 文字以上                                    | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-87  | 【新規作成】 バリデーションエラー（serving_count が整数でない）                   | 異常系 | serving_count が整数でない                              | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-88  | 【新規作成】 バリデーションエラー（serving_count が 1 未満）                      | 異常系 | serving_count が 0 以下                                | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-89  | 【新規作成】 バリデーションエラー（ownerUserId 未入力）                           | 異常系 | ownerUserId が未入力                                    | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-90  | 【新規作成】 バリデーションエラー（ownerUserId が UUID 形式でない）               | 異常系 | ownerUserId が UUID 形式でない                          | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-7-91  | 【新規作成】 存在しない食材単位 ID 指定                                           | 異常系 | 存在しない食材単位 ID を指定                            | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-92  | 【新規作成】 他グループの食材単位 ID 指定                                         | 異常系 | 他グループの食材単位 ID を指定                          | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-93  | 【新規作成】 存在しない食材カテゴリ ID 指定                                       | 異常系 | 存在しない食材カテゴリ ID を指定                        | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-94  | 【新規作成】 他グループの食材カテゴリ ID 指定                                     | 異常系 | 他グループの食材カテゴリ ID を指定                     | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-95  | 【新規作成】 存在しない料理カテゴリ ID 指定                                       | 異常系 | 存在しない料理カテゴリ ID を指定                        | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-96  | 【新規作成】 他グループの料理カテゴリ ID 指定                                     | 異常系 | 他グループの料理カテゴリ ID を指定                     | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-97  | 【新規作成】 存在しない画像 ID 指定（thumbnailId）                                 | 異常系 | 存在しない画像 ID を thumbnailId に指定                 | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-98  | 【新規作成】 他グループの画像 ID 指定（thumbnailId）                              | 異常系 | 他グループの画像 ID を thumbnailId に指定              | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-99  | 【新規作成】 存在しない画像 ID 指定（steps.\*.imageId）                           | 異常系 | 存在しない画像 ID を steps.\*.imageId に指定            | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-100 | 【新規作成】 他グループの画像 ID 指定（steps.\*.imageId）                         | 異常系 | 他グループの画像 ID を steps.\*.imageId に指定          | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-7-101 | 【新規作成】 未認証ユーザー                                                       | 異常系 | 認証されていないユーザー                                | HTTP 401 Unauthorized                 | `RecipeController::store()`    |
| 3-7-102 | 【新規作成】 グループが存在しない                                                 | 異常系 | ユーザーにグループが紐づいていない                      | HTTP 422 Unprocessable Entity         | `RecipeController::store()`    |
| 3-7-103 | 【新規作成】 データベース接続エラー                                               | 異常系 | データベース接続が失敗                                  | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-7-104 | 【新規作成】 料理作成失敗                                                         | 異常系 | Recipe::create() が失敗                                 | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-7-105 | 【新規作成】 食材紐づけ失敗                                                       | 異常系 | 食材の紐づけ処理が失敗                                  | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-7-106 | 【新規作成】 手順紐づけ失敗                                                       | 異常系 | 手順の紐づけ処理が失敗                                  | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-7-107 | 【新規作成】 画像紐づけ失敗                                                       | 異常系 | 画像の紐づけ処理が失敗                                  | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-7-108 | 【新規作成】 ImageService 例外                                                    | 異常系 | ImageService で例外が発生                                | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-7-109 | 【詳細取得】 正常な料理詳細取得                                                   | 正常系 | 有効な料理 ID を提供                                    | HTTP 200 JSON success                 | `RecipeController::show()`     |
| 3-7-110 | 【詳細取得】 すべての項目を含む料理詳細取得                                       | 正常系 | name/servingCount/url/memo/thumbnailId/categories/ingredients/steps を含む料理を取得 | HTTP 200 JSON success、すべての項目が正しく取得される | `RecipeController::show()`     |
| 3-7-111 | 【詳細取得】 存在しない料理詳細取得                                               | 異常系 | 存在しない料理 ID を提供                                | HTTP 404 Not Found                    | `RecipeController::show()`     |
| 3-7-112 | 【詳細取得】 他グループの料理詳細取得                                             | 異常系 | 他グループの料理 ID を提供                              | HTTP 404 Not Found                    | `RecipeController::show()`     |
| 3-7-113 | 【詳細取得】 未認証ユーザー                                                       | 異常系 | 認証されていないユーザー                                | HTTP 401 Unauthorized                 | `RecipeController::show()`     |
| 3-7-114 | 【詳細取得】 グループが存在しない                                                 | 異常系 | ユーザーにグループが紐づいていない                      | HTTP 422 Unprocessable Entity         | `RecipeController::show()`     |
| 3-7-115 | 【詳細取得】 データベース接続エラー                                               | 異常系 | データベース接続が失敗                                  | HTTP 500 Internal Server Error        | `RecipeController::show()`     |
| 3-7-116 | 【更新】 正常な料理更新                                                           | 正常系 | 有効な料理データを提供                                  | HTTP 200 JSON success                 | `RecipeController::update()`   |
| 3-7-117 | 【更新】 最小限のデータで料理更新                                                 | 正常系 | name と serving_count を指定して料理を更新              | HTTP 200 JSON success                 | `RecipeController::update()`   |
| 3-7-118 | 【更新】 料理のカテゴリ更新                                                       | 正常系 | 料理更新時にカテゴリデータを提供                        | カテゴリの紐づけが更新される          | `RecipeController::update()`   |
| 3-7-119 | 【更新】 料理の食材更新                                                           | 正常系 | 料理更新時に食材データを提供                            | 食材の紐づけが更新される              | `RecipeController::update()`   |
| 3-7-120 | 【更新】 最小限の必須フィールドのみで食材を更新                                   | 正常系 | 食材の name/unitId/categoryId のみ指定                  | 食材の紐づけが更新される              | `RecipeController::update()`   |
| 3-7-121 | 【更新】 料理の手順更新                                                           | 正常系 | 料理更新時に手順データを提供                            | 手順の紐づけが更新される              | `RecipeController::update()`   |
| 3-7-122 | 【更新】 最小限の必須フィールドのみで手順を更新                                   | 正常系 | 手順の instruction/order のみ指定                       | 手順の紐づけが更新される              | `RecipeController::update()`   |
| 3-7-123 | 【更新】 手順の画像を削除（imageId が null）                                      | 正常系 | 既存の画像がある手順で imageId を null に指定           | 手順の画像が削除される                | `RecipeController::update()`   |
| 3-7-124 | 【更新】 手順の画像を削除（imageId キーが存在しない）                             | 正常系 | 既存の画像がある手順で imageId キーを省略                | 手順の画像が削除される                | `RecipeController::update()`   |
| 3-7-125 | 【更新】 料理の画像更新                                                           | 正常系 | 料理更新時に画像データを提供                            | 画像の紐づけが更新される              | `RecipeController::update()`   |
| 3-7-126 | 【更新】 サムネイルを削除（thumbnailId が null）                                  | 正常系 | 既存のサムネイルがある料理で thumbnailId を null に指定 | サムネイルが削除される                | `RecipeController::update()`   |
| 3-7-127 | 【更新】 サムネイルを削除（thumbnailId キーが存在しない）                         | 正常系 | 既存のサムネイルがある料理で thumbnailId キーを省略     | サムネイルが削除される                | `RecipeController::update()`   |
| 3-7-128 | 【更新】 更新成功メッセージの確認                                                 | 正常系 | 正常な料理更新後                                        | 料理名を含むメッセージが返される      | `RecipeController::update()`   |
| 3-7-129 | 【更新】 requires_quantity=true の食材単位で数量指定                              | 正常系 | requires_quantity=true の単位で quantity 指定           | HTTP 200 JSON success、数量付きで更新 | `RecipeController::update()`   |
| 3-7-130 | 【更新】 requires_quantity=false の食材単位で数量指定                             | 正常系 | requires_quantity=false の単位で quantity 指定          | HTTP 200 JSON success、数量なしで更新 | `RecipeController::update()`   |
| 3-7-131 | 【更新】 requires_quantity=false の食材単位で数量省略                             | 正常系 | requires_quantity=false の単位で quantity 省略          | HTTP 200 JSON success、数量なしで更新 | `RecipeController::update()`   |
| 3-7-132 | 【更新】 すべての項目を含む料理更新                                               | 正常系 | name/servingCount/url/memo/thumbnailId/categoryIds/ingredients/steps をすべて指定 | HTTP 200 JSON success、すべての項目が正しく更新される | `RecipeController::update()`   |
| 3-7-133 | 【更新】 serving_count が null でも正常に更新できる                               | 正常系 | serving_count が null                                   | HTTP 200 JSON success                 | `RecipeController::update()`   |
| 3-7-134 | 【更新】 同一グループの他ユーザーの料理更新                                       | 正常系 | 同一グループ内の他ユーザー（編集責任者以外）の料理 ID を提供 | HTTP 200 JSON success              | `RecipeController::update()`   |
| 3-7-135 | 【更新】 バリデーションエラー（name 未入力）                                      | 異常系 | name が未入力                                           | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-136 | 【更新】 バリデーションエラー（name が文字列でない）                              | 異常系 | name が文字列でない                                     | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-137 | 【更新】 バリデーションエラー（name が 255 文字超過）                             | 異常系 | name が 256 文字以上                                    | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-138 | 【更新】 バリデーションエラー（url が文字列でない）                               | 異常系 | url が文字列でない                                      | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-139 | 【更新】 バリデーションエラー（url が 2048 文字超過）                             | 異常系 | url が 2049 文字以上                                    | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-140 | 【更新】 バリデーションエラー（thumbnailId が UUID 形式でない）                   | 異常系 | thumbnailId が UUID 形式でない                          | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-141 | 【更新】 バリデーションエラー（categoryIds が配列でない）                         | 異常系 | categoryIds が配列でない                                | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-142 | 【更新】 バリデーションエラー（categoryIds.\* が UUID 形式でない）                | 異常系 | categoryIds.\* が UUID 形式でない                       | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-143 | 【更新】 バリデーションエラー（categoryIds.\* 未入力）                            | 異常系 | categoryIds.\* が未入力                                 | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-144 | 【更新】 バリデーションエラー（ingredients が配列でない）                         | 異常系 | ingredients が配列でない                                | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-145 | 【更新】 バリデーションエラー（ingredients.\*.id が UUID 形式でない）             | 異常系 | ingredients.\*.id が UUID 形式でない                    | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-146 | 【更新】 バリデーションエラー（ingredients.\*.name 未入力）                       | 異常系 | ingredients.\*.name が未入力                            | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-147 | 【更新】 バリデーションエラー（ingredients.\*.name が文字列でない）               | 異常系 | ingredients.\*.name が文字列でない                      | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-148 | 【更新】 バリデーションエラー（ingredients.\*.name が 255 文字超過）              | 異常系 | ingredients.\*.name が 256 文字以上                     | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-149 | 【更新】 バリデーションエラー（ingredients.\*.unitId 未入力）                     | 異常系 | ingredients.\*.unitId が未入力                          | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-150 | 【更新】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない）         | 異常系 | ingredients.\*.unitId が UUID 形式でない                | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-151 | 【更新】 バリデーションエラー（ingredients.\*.categoryId 未入力）                 | 異常系 | ingredients.\*.categoryId が未入力                      | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-152 | 【更新】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない）     | 異常系 | ingredients.\*.categoryId が UUID 形式でない            | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-153 | 【更新】 バリデーションエラー（ingredients.\*.quantity が数値でない）             | 異常系 | ingredients.\*.quantity が数値でない                    | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-154 | 【更新】 バリデーションエラー（ingredients.\*.order が整数でない）                | 異常系 | ingredients.\*.order が整数でない                       | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-155 | 【更新】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で数量省略） | 異常系 | ingredients\*.requires_quantity=true の単位で quantity 省略 | HTTP 422 Validation Error        | `RecipeUpdateRequest::rules()` |
| 3-7-156 | 【更新】 バリデーションエラー（ingredients.\*.order が負の値）                    | 異常系 | ingredients.\*.order が 0 未満の負の値                  | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-157 | 【更新】 バリデーションエラー（steps が配列でない）                               | 異常系 | steps が配列でない                                      | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-158 | 【更新】 バリデーションエラー（steps.\*.id が UUID 形式でない）                   | 異常系 | steps.\*.id が UUID 形式でない                          | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-159 | 【更新】 バリデーションエラー（steps.\*.instruction 未入力）                      | 異常系 | steps.\*.instruction が未入力                            | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-160 | 【更新】 バリデーションエラー（steps.\*.instruction が文字列でない）              | 異常系 | steps.\*.instruction が文字列でない                     | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-161 | 【更新】 バリデーションエラー（steps.\*.instruction が 255 文字超過）             | 異常系 | steps.\*.instruction が 256 文字以上                    | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-162 | 【更新】 バリデーションエラー（steps.\*.imageId が UUID 形式でない）              | 異常系 | steps.\*.imageId が UUID 形式でない                     | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-163 | 【更新】 バリデーションエラー（steps.\*.order 未入力）                            | 異常系 | steps.\*.order が未入力                                 | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-164 | 【更新】 バリデーションエラー（steps.\*.order が整数でない）                       | 異常系 | steps.\*.order が整数でない                             | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-165 | 【更新】 バリデーションエラー（steps.\*.order が負の値）                          | 異常系 | steps.\*.order が 0 未満の負の値                        | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-166 | 【更新】 バリデーションエラー（memo が文字列でない）                              | 異常系 | memo が文字列でない                                     | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-167 | 【更新】 バリデーションエラー（memo が 255 文字超過）                             | 異常系 | memo が 256 文字以上                                    | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-168 | 【更新】 バリデーションエラー（serving_count が整数でない）                       | 異常系 | serving_count が整数でない                               | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-169 | 【更新】 バリデーションエラー（serving_count が 1 未満）                          | 異常系 | serving_count が 0 以下                                 | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-170 | 【更新】 バリデーションエラー（ownerUserId 未入力）                               | 異常系 | ownerUserId が未入力                                    | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-171 | 【更新】 バリデーションエラー（ownerUserId が UUID 形式でない）                   | 異常系 | ownerUserId が UUID 形式でない                          | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-7-172 | 【更新】 存在しない食材単位 ID 指定                                               | 異常系 | 存在しない食材単位 ID を指定                            | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-173 | 【更新】 他グループの食材単位 ID 指定                                             | 異常系 | 他グループの食材単位 ID を指定                          | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-174 | 【更新】 存在しない食材カテゴリ ID 指定                                           | 異常系 | 存在しない食材カテゴリ ID を指定                        | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-175 | 【更新】 他グループの食材カテゴリ ID 指定                                         | 異常系 | 他グループの食材カテゴリ ID を指定                      | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-176 | 【更新】 存在しない料理カテゴリ ID 指定                                           | 異常系 | 存在しない料理カテゴリ ID を指定                        | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-177 | 【更新】 他グループの料理カテゴリ ID 指定                                         | 異常系 | 他グループの料理カテゴリ ID を指定                      | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-178 | 【更新】 存在しない画像 ID 指定（thumbnailId）                                    | 異常系 | 存在しない画像 ID を thumbnailId に指定                 | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-179 | 【更新】 他グループの画像 ID 指定（thumbnailId）                                  | 異常系 | 他グループの画像 ID を thumbnailId に指定               | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-180 | 【更新】 存在しない画像 ID 指定（steps.\*.imageId）                               | 異常系 | 存在しない画像 ID を steps.\*.imageId に指定            | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-181 | 【更新】 他グループの画像 ID 指定（steps.\*.imageId）                             | 異常系 | 他グループの画像 ID を steps.\*.imageId に指定          | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-182 | 【更新】 存在しない料理更新                                                       | 異常系 | 存在しない料理 ID を提供                                | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-183 | 【更新】 他グループの料理更新                                                     | 異常系 | 他グループの料理 ID を提供                              | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-7-184 | 【更新】 未認証ユーザー                                                           | 異常系 | 認証されていないユーザー                                | HTTP 401 Unauthorized                 | `RecipeController::update()`   |
| 3-7-185 | 【更新】 グループが存在しない                                                     | 異常系 | ユーザーにグループが紐づいていない                      | HTTP 422 Unprocessable Entity         | `RecipeController::update()`   |
| 3-7-186 | 【更新】 データベース接続エラー                                                   | 異常系 | データベース接続が失敗                                  | HTTP 500 Internal Server Error        | `RecipeController::update()`   |
| 3-7-187 | 【削除】 正常な料理削除                                                           | 正常系 | 有効な料理 ID を提供                                    | HTTP 200 JSON success                 | `RecipeController::destroy()`  |
| 3-7-188 | 【削除】 削除成功メッセージの確認                                                 | 正常系 | 正常な料理削除後                                        | 料理名を含むメッセージが返される      | `RecipeController::destroy()`  |
| 3-7-189 | 【削除】 存在しない料理削除                                                       | 異常系 | 存在しない料理 ID を提供                                | HTTP 404 Not Found                    | `RecipeController::destroy()`  |
| 3-7-190 | 【削除】 他グループの料理削除                                                     | 異常系 | 他グループの料理 ID を提供                              | HTTP 404 Not Found                    | `RecipeController::destroy()`  |
| 3-7-191 | 【削除】 同一グループの他ユーザーの料理削除                                       | 異常系 | 同一グループ内の他ユーザーの料理 ID を提供              | HTTP 403 Forbidden                    | `RecipeController::destroy()`  |
| 3-7-192 | 【削除】 未認証ユーザー                                                           | 異常系 | 認証されていないユーザー                                | HTTP 401 Unauthorized                 | `RecipeController::destroy()`  |
| 3-7-193 | 【削除】 グループが存在しない                                                     | 異常系 | ユーザーにグループが紐づいていない                      | HTTP 422 Unprocessable Entity         | `RecipeController::destroy()`  |
| 3-7-194 | 【削除】 データベース接続エラー                                                   | 異常系 | データベース接続が失敗                                  | HTTP 500 Internal Server Error        | `RecipeController::destroy()`  |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
