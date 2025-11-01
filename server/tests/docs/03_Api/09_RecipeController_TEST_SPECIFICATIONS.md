# RecipeController テストケース詳細仕様

## 概要

RecipeController のテストケースの詳細仕様を示します。料理の一覧取得、作成、詳細取得、更新、削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID      | テスト名                                                                          | 種別   | 入力条件                                       | 期待される出力                        | 該当メソッド                   |
| ------- | --------------------------------------------------------------------------------- | ------ | ---------------------------------------------- | ------------------------------------- | ------------------------------ |
| 3-9-1   | 【一覧取得】 正常な料理一覧取得                                                   | 正常系 | 認証済みユーザー                               | HTTP 200 JSON success                 | `RecipeController::index()`    |
| 3-9-2   | 【一覧取得】 ページネーション機能確認                                             | 正常系 | ページパラメータを指定                         | 正しいページネーションでデータ取得    | `RecipeController::index()`    |
| 3-9-3   | 【一覧取得】 検索機能確認                                                         | 正常系 | 検索キーワードを指定                           | 検索条件に合致する料理が取得される    | `RecipeController::index()`    |
| 3-9-4   | 【一覧取得】 レスポンス形式確認                                                   | 正常系 | 正常な料理一覧取得後                           | 正しい JSON 形式でレスポンスが返る    | `RecipeController::index()`    |
| 3-9-5   | 【一覧取得】 未認証ユーザー                                                       | 異常系 | 認証されていないユーザー                       | HTTP 401 Unauthorized                 | `RecipeController::index()`    |
| 3-9-6   | 【一覧取得】 グループが存在しない                                                 | 異常系 | ユーザーにグループが紐づいていない             | HTTP 422 Unprocessable Entity         | `RecipeController::index()`    |
| 3-9-7   | 【一覧取得】 データベース接続エラー                                               | 異常系 | データベース接続が失敗                         | HTTP 500 Internal Server Error        | `RecipeController::index()`    |
| 3-9-8   | 【一覧取得】 RecipeService 例外                                                   | 異常系 | RecipeService で例外が発生                     | HTTP 500 Internal Server Error        | `RecipeController::index()`    |
| 3-9-9   | 【新規作成】 正常な料理作成                                                       | 正常系 | 有効な料理データを提供                         | HTTP 201 Created                      | `RecipeController::store()`    |
| 3-9-10  | 【新規作成】 最小限のデータで料理作成                                             | 正常系 | name のみ指定して料理を作成                    | HTTP 201 Created                      | `RecipeController::store()`    |
| 3-9-11  | 【新規作成】 料理にカテゴリを紐づけ                                               | 正常系 | 料理作成時にカテゴリデータを提供               | カテゴリが料理に紐づけられる          | `RecipeController::store()`    |
| 3-9-12  | 【新規作成】 料理に食材を紐づけ                                                   | 正常系 | 料理作成時に食材データを提供                   | 食材が料理に紐づけられる              | `RecipeController::store()`    |
| 3-9-13  | 【新規作成】 最小限の必須フィールドのみで食材を紐づけ                             | 正常系 | 食材の name/unitId/categoryId のみ指定         | 食材が料理に紐づけられる              | `RecipeController::store()`    |
| 3-9-14  | 【新規作成】 料理に手順を紐づけ                                                   | 正常系 | 料理作成時に手順データを提供                   | 手順が料理に紐づけられる              | `RecipeController::store()`    |
| 3-9-15  | 【新規作成】 最小限の必須フィールドのみで手順を紐づけ                             | 正常系 | 手順の instruction/order のみ指定              | 手順が料理に紐づけられる              | `RecipeController::store()`    |
| 3-9-16  | 【新規作成】 料理に画像を紐づけ                                                   | 正常系 | 料理作成時に画像データを提供                   | 画像が料理に紐づけられる              | `RecipeController::store()`    |
| 3-9-17  | 【新規作成】 requires_quantity=true の食材単位で数量指定                          | 正常系 | requires_quantity=true の単位で quantity 指定  | HTTP 201 Created、数量付きで作成      | `RecipeController::store()`    |
| 3-9-18  | 【新規作成】 requires_quantity=false の食材単位で数量指定                         | 正常系 | requires_quantity=false の単位で quantity 指定 | HTTP 201 Created、数量なしで作成      | `RecipeController::store()`    |
| 3-9-19  | 【新規作成】 requires_quantity=false の食材単位で数量省略                         | 正常系 | requires_quantity=false の単位で quantity 省略 | HTTP 201 Created、数量なしで作成      | `RecipeController::store()`    |
| 3-9-20  | 【新規作成】 バリデーションエラー（requires_quantity=true の単位で数量省略）      | 異常系 | requires_quantity=true の単位で quantity 省略  | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-21  | 【新規作成】 バリデーションエラー（name 未入力）                                  | 異常系 | name が未入力                                  | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-22  | 【新規作成】 バリデーションエラー（name が文字列でない）                          | 異常系 | name が文字列でない                            | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-23  | 【新規作成】 バリデーションエラー（name が 255 文字超過）                         | 異常系 | name が 256 文字以上                           | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-24  | 【新規作成】 バリデーションエラー（url が文字列でない）                           | 異常系 | url が文字列でない                             | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-25  | 【新規作成】 バリデーションエラー（url が 2048 文字超過）                         | 異常系 | url が 2049 文字以上                           | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-26  | 【新規作成】 バリデーションエラー（thumbnailId が UUID 形式でない）               | 異常系 | thumbnailId が UUID 形式でない                 | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-27  | 【新規作成】 バリデーションエラー（categoryIds が配列でない）                     | 異常系 | categoryIds が配列でない                       | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-28  | 【新規作成】 バリデーションエラー（categoryIds.\* が UUID 形式でない）            | 異常系 | categoryIds.\* が UUID 形式でない              | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-29  | 【新規作成】 バリデーションエラー（ingredients が配列でない）                     | 異常系 | ingredients が配列でない                       | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-30  | 【新規作成】 バリデーションエラー（ingredients.\*.id が UUID 形式でない）         | 異常系 | ingredients.\*.id が UUID 形式でない           | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-31  | 【新規作成】 バリデーションエラー（ingredients.\*.name 未入力）                   | 異常系 | ingredients.\*.name が未入力                   | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-32  | 【新規作成】 バリデーションエラー（ingredients.\*.name が文字列でない）           | 異常系 | ingredients.\*.name が文字列でない             | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-33  | 【新規作成】 バリデーションエラー（ingredients.\*.name が 255 文字超過）          | 異常系 | ingredients.\*.name が 256 文字以上            | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-34  | 【新規作成】 バリデーションエラー（ingredients.\*.unitId 未入力）                 | 異常系 | ingredients.\*.unitId が未入力                 | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-35  | 【新規作成】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない）     | 異常系 | ingredients.\*.unitId が UUID 形式でない       | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-36  | 【新規作成】 バリデーションエラー（ingredients.\*.categoryId 未入力）             | 異常系 | ingredients.\*.categoryId が未入力             | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-37  | 【新規作成】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない） | 異常系 | ingredients.\*.categoryId が UUID 形式でない   | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-38  | 【新規作成】 バリデーションエラー（ingredients.\*.quantity が数値でない）         | 異常系 | ingredients.\*.quantity が数値でない           | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-39  | 【新規作成】 バリデーションエラー（ingredients.\*.order が整数でない）            | 異常系 | ingredients.\*.order が整数でない              | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-40  | 【新規作成】 バリデーションエラー（ingredients.\*.order が負の値）                | 異常系 | ingredients.\*.order が 0 未満の負の値         | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-41  | 【新規作成】 バリデーションエラー（steps が配列でない）                           | 異常系 | steps が配列でない                             | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-42  | 【新規作成】 バリデーションエラー（steps.\*.id が UUID 形式でない）               | 異常系 | steps.\*.id が UUID 形式でない                 | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-43  | 【新規作成】 バリデーションエラー（steps.\*.instruction 未入力）                  | 異常系 | steps.\*.instruction が未入力                  | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-44  | 【新規作成】 バリデーションエラー（steps.\*.instruction が文字列でない）          | 異常系 | steps.\*.instruction が文字列でない            | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-45  | 【新規作成】 バリデーションエラー（steps.\*.instruction が 255 文字超過）         | 異常系 | steps.\*.instruction が 256 文字以上           | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-46  | 【新規作成】 バリデーションエラー（steps.\*.imageId が UUID 形式でない）          | 異常系 | steps.\*.imageId が UUID 形式でない            | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-47  | 【新規作成】 バリデーションエラー（steps.\*.order 未入力）                        | 異常系 | steps.\*.order が未入力                        | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-48  | 【新規作成】 バリデーションエラー（steps.\*.order が整数でない）                  | 異常系 | steps.\*.order が整数でない                    | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-49  | 【新規作成】 バリデーションエラー（steps.\*.order が負の値）                      | 異常系 | steps.\*.order が 0 未満の負の値               | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-50  | 【新規作成】 バリデーションエラー（memo が文字列でない）                          | 異常系 | memo が文字列でない                            | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-51  | 【新規作成】 バリデーションエラー（memo が 255 文字超過）                         | 異常系 | memo が 256 文字以上                           | HTTP 422 Validation Error             | `RecipeStoreRequest::rules()`  |
| 3-9-52  | 【新規作成】 存在しない食材単位 ID 指定                                           | 異常系 | 存在しない食材単位 ID を指定                   | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-53  | 【新規作成】 他グループの食材単位 ID 指定                                         | 異常系 | 他グループの食材単位 ID を指定                 | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-54  | 【新規作成】 存在しない食材カテゴリ ID 指定                                       | 異常系 | 存在しない食材カテゴリ ID を指定               | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-55  | 【新規作成】 他グループの食材カテゴリ ID 指定                                     | 異常系 | 他グループの食材カテゴリ ID を指定             | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-56  | 【新規作成】 存在しない料理カテゴリ ID 指定                                       | 異常系 | 存在しない料理カテゴリ ID を指定               | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-57  | 【新規作成】 他グループの料理カテゴリ ID 指定                                     | 異常系 | 他グループの料理カテゴリ ID を指定             | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-58  | 【新規作成】 存在しない画像 ID 指定（thumbnailId）                                | 異常系 | 存在しない画像 ID を thumbnailId に指定        | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-59  | 【新規作成】 他グループの画像 ID 指定（thumbnailId）                              | 異常系 | 他グループの画像 ID を thumbnailId に指定      | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-60  | 【新規作成】 存在しない画像 ID 指定（steps.\*.imageId）                           | 異常系 | 存在しない画像 ID を steps.\*.imageId に指定   | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-61  | 【新規作成】 他グループの画像 ID 指定（steps.\*.imageId）                         | 異常系 | 他グループの画像 ID を steps.\*.imageId に指定 | HTTP 404 Not Found                    | `RecipeController::store()`    |
| 3-9-62  | 【新規作成】 未認証ユーザー                                                       | 異常系 | 認証されていないユーザー                       | HTTP 401 Unauthorized                 | `RecipeController::store()`    |
| 3-9-63  | 【新規作成】 グループが存在しない                                                 | 異常系 | ユーザーにグループが紐づいていない             | HTTP 422 Unprocessable Entity         | `RecipeController::store()`    |
| 3-9-64  | 【新規作成】 データベース接続エラー                                               | 異常系 | データベース接続が失敗                         | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-9-65  | 【新規作成】 料理作成失敗                                                         | 異常系 | Recipe::create() が失敗                        | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-9-66  | 【新規作成】 食材紐づけ失敗                                                       | 異常系 | 食材の紐づけ処理が失敗                         | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-9-67  | 【新規作成】 手順紐づけ失敗                                                       | 異常系 | 手順の紐づけ処理が失敗                         | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-9-68  | 【新規作成】 画像紐づけ失敗                                                       | 異常系 | 画像の紐づけ処理が失敗                         | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-9-69  | 【新規作成】 ImageService 例外                                                    | 異常系 | ImageService で例外が発生                      | HTTP 500 Internal Server Error        | `RecipeController::store()`    |
| 3-9-70  | 【詳細取得】 正常な料理詳細取得                                                   | 正常系 | 有効な料理 ID を提供                           | HTTP 200 JSON success                 | `RecipeController::show()`     |
| 3-9-71  | 【詳細取得】 存在しない料理詳細取得                                               | 異常系 | 存在しない料理 ID を提供                       | HTTP 404 Not Found                    | `RecipeController::show()`     |
| 3-9-72  | 【詳細取得】 他グループの料理詳細取得                                             | 異常系 | 他グループの料理 ID を提供                     | HTTP 404 Not Found                    | `RecipeController::show()`     |
| 3-9-73  | 【詳細取得】 未認証ユーザー                                                       | 異常系 | 認証されていないユーザー                       | HTTP 401 Unauthorized                 | `RecipeController::show()`     |
| 3-9-74  | 【詳細取得】 グループが存在しない                                                 | 異常系 | ユーザーにグループが紐づいていない             | HTTP 422 Unprocessable Entity         | `RecipeController::show()`     |
| 3-9-75  | 【詳細取得】 データベース接続エラー                                               | 異常系 | データベース接続が失敗                         | HTTP 500 Internal Server Error        | `RecipeController::show()`     |
| 3-9-76  | 【更新】 正常な料理更新                                                           | 正常系 | 有効な料理データを提供                         | HTTP 200 JSON success                 | `RecipeController::update()`   |
| 3-9-77  | 【更新】 最小限のデータで料理更新                                                 | 正常系 | name のみ指定して料理を更新                    | HTTP 200 JSON success                 | `RecipeController::update()`   |
| 3-9-78  | 【更新】 料理のカテゴリ更新                                                       | 正常系 | 料理更新時にカテゴリデータを提供               | カテゴリの紐づけが更新される          | `RecipeController::update()`   |
| 3-9-79  | 【更新】 料理の食材更新                                                           | 正常系 | 料理更新時に食材データを提供                   | 食材の紐づけが更新される              | `RecipeController::update()`   |
| 3-9-80  | 【更新】 最小限の必須フィールドのみで食材を更新                                   | 正常系 | 食材の name/unitId/categoryId のみ指定         | 食材の紐づけが更新される              | `RecipeController::update()`   |
| 3-9-81  | 【更新】 料理の手順更新                                                           | 正常系 | 料理更新時に手順データを提供                   | 手順の紐づけが更新される              | `RecipeController::update()`   |
| 3-9-82  | 【更新】 最小限の必須フィールドのみで手順を更新                                   | 正常系 | 手順の instruction/order のみ指定              | 手順の紐づけが更新される              | `RecipeController::update()`   |
| 3-9-83  | 【更新】 料理の画像更新                                                           | 正常系 | 料理更新時に画像データを提供                   | 画像の紐づけが更新される              | `RecipeController::update()`   |
| 3-9-84  | 【更新】 更新成功メッセージの確認                                                 | 正常系 | 正常な料理更新後                               | 料理名を含むメッセージが返される      | `RecipeController::update()`   |
| 3-9-85  | 【更新】 requires_quantity=true の食材単位で数量指定                              | 正常系 | requires_quantity=true の単位で quantity 指定  | HTTP 200 JSON success、数量付きで更新 | `RecipeController::update()`   |
| 3-9-86  | 【更新】 requires_quantity=false の食材単位で数量指定                             | 正常系 | requires_quantity=false の単位で quantity 指定 | HTTP 200 JSON success、数量なしで更新 | `RecipeController::update()`   |
| 3-9-87  | 【更新】 requires_quantity=false の食材単位で数量省略                             | 正常系 | requires_quantity=false の単位で quantity 省略 | HTTP 200 JSON success、数量なしで更新 | `RecipeController::update()`   |
| 3-9-88  | 【更新】 バリデーションエラー（requires_quantity=true の単位で数量省略）          | 異常系 | requires_quantity=true の単位で quantity 省略  | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-89  | 【更新】 バリデーションエラー（name 未入力）                                      | 異常系 | name が未入力                                  | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-90  | 【更新】 バリデーションエラー（name が文字列でない）                              | 異常系 | name が文字列でない                            | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-91  | 【更新】 バリデーションエラー（name が 255 文字超過）                             | 異常系 | name が 256 文字以上                           | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-92  | 【更新】 バリデーションエラー（url が文字列でない）                               | 異常系 | url が文字列でない                             | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-93  | 【更新】 バリデーションエラー（url が 2048 文字超過）                             | 異常系 | url が 2049 文字以上                           | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-94  | 【更新】 バリデーションエラー（thumbnailId が UUID 形式でない）                   | 異常系 | thumbnailId が UUID 形式でない                 | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-95  | 【更新】 バリデーションエラー（categoryIds が配列でない）                         | 異常系 | categoryIds が配列でない                       | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-96  | 【更新】 バリデーションエラー（categoryIds.\* が UUID 形式でない）                | 異常系 | categoryIds.\* が UUID 形式でない              | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-97  | 【更新】 バリデーションエラー（ingredients が配列でない）                         | 異常系 | ingredients が配列でない                       | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-98  | 【更新】 バリデーションエラー（ingredients.\*.id が UUID 形式でない）             | 異常系 | ingredients.\*.id が UUID 形式でない           | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-99  | 【更新】 バリデーションエラー（ingredients.\*.name 未入力）                       | 異常系 | ingredients.\*.name が未入力                   | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-100 | 【更新】 バリデーションエラー（ingredients.\*.name が文字列でない）               | 異常系 | ingredients.\*.name が文字列でない             | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-101 | 【更新】 バリデーションエラー（ingredients.\*.name が 255 文字超過）              | 異常系 | ingredients.\*.name が 256 文字以上            | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-102 | 【更新】 バリデーションエラー（ingredients.\*.unitId 未入力）                     | 異常系 | ingredients.\*.unitId が未入力                 | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-103 | 【更新】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない）         | 異常系 | ingredients.\*.unitId が UUID 形式でない       | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-104 | 【更新】 バリデーションエラー（ingredients.\*.categoryId 未入力）                 | 異常系 | ingredients.\*.categoryId が未入力             | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-105 | 【更新】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない）     | 異常系 | ingredients.\*.categoryId が UUID 形式でない   | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-106 | 【更新】 バリデーションエラー（ingredients.\*.quantity が数値でない）             | 異常系 | ingredients.\*.quantity が数値でない           | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-107 | 【更新】 バリデーションエラー（ingredients.\*.order が整数でない）                | 異常系 | ingredients.\*.order が整数でない              | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-108 | 【更新】 バリデーションエラー（ingredients.\*.order が負の値）                    | 異常系 | ingredients.\*.order が 0 未満の負の値         | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-109 | 【更新】 バリデーションエラー（steps が配列でない）                               | 異常系 | steps が配列でない                             | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-110 | 【更新】 バリデーションエラー（steps.\*.id が UUID 形式でない）                   | 異常系 | steps.\*.id が UUID 形式でない                 | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-111 | 【更新】 バリデーションエラー（steps.\*.instruction 未入力）                      | 異常系 | steps.\*.instruction が未入力                  | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-112 | 【更新】 バリデーションエラー（steps.\*.instruction が文字列でない）              | 異常系 | steps.\*.instruction が文字列でない            | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-113 | 【更新】 バリデーションエラー（steps.\*.instruction が 255 文字超過）             | 異常系 | steps.\*.instruction が 256 文字以上           | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-114 | 【更新】 バリデーションエラー（steps.\*.imageId が UUID 形式でない）              | 異常系 | steps.\*.imageId が UUID 形式でない            | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-115 | 【更新】 バリデーションエラー（steps.\*.order 未入力）                            | 異常系 | steps.\*.order が未入力                        | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-116 | 【更新】 バリデーションエラー（steps.\*.order が整数でない）                      | 異常系 | steps.\*.order が整数でない                    | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-117 | 【更新】 バリデーションエラー（steps.\*.order が負の値）                          | 異常系 | steps.\*.order が 0 未満の負の値               | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-118 | 【更新】 バリデーションエラー（memo が文字列でない）                              | 異常系 | memo が文字列でない                            | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-119 | 【更新】 バリデーションエラー（memo が 255 文字超過）                             | 異常系 | memo が 256 文字以上                           | HTTP 422 Validation Error             | `RecipeUpdateRequest::rules()` |
| 3-9-120 | 【更新】 存在しない食材単位 ID 指定                                               | 異常系 | 存在しない食材単位 ID を指定                   | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-121 | 【更新】 他グループの食材単位 ID 指定                                             | 異常系 | 他グループの食材単位 ID を指定                 | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-122 | 【更新】 存在しない食材カテゴリ ID 指定                                           | 異常系 | 存在しない食材カテゴリ ID を指定               | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-123 | 【更新】 他グループの食材カテゴリ ID 指定                                         | 異常系 | 他グループの食材カテゴリ ID を指定             | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-124 | 【更新】 存在しない料理カテゴリ ID 指定                                           | 異常系 | 存在しない料理カテゴリ ID を指定               | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-125 | 【更新】 他グループの料理カテゴリ ID 指定                                         | 異常系 | 他グループの料理カテゴリ ID を指定             | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-126 | 【更新】 存在しない画像 ID 指定（thumbnailId）                                    | 異常系 | 存在しない画像 ID を thumbnailId に指定        | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-127 | 【更新】 他グループの画像 ID 指定（thumbnailId）                                  | 異常系 | 他グループの画像 ID を thumbnailId に指定      | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-128 | 【更新】 存在しない画像 ID 指定（steps.\*.imageId）                               | 異常系 | 存在しない画像 ID を steps.\*.imageId に指定   | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-129 | 【更新】 他グループの画像 ID 指定（steps.\*.imageId）                             | 異常系 | 他グループの画像 ID を steps.\*.imageId に指定 | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-130 | 【更新】 存在しない料理更新                                                       | 異常系 | 存在しない料理 ID を提供                       | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-131 | 【更新】 他グループの料理更新                                                     | 異常系 | 他グループの料理 ID を提供                     | HTTP 404 Not Found                    | `RecipeController::update()`   |
| 3-9-132 | 【更新】 未認証ユーザー                                                           | 異常系 | 認証されていないユーザー                       | HTTP 401 Unauthorized                 | `RecipeController::update()`   |
| 3-9-133 | 【更新】 グループが存在しない                                                     | 異常系 | ユーザーにグループが紐づいていない             | HTTP 422 Unprocessable Entity         | `RecipeController::update()`   |
| 3-9-134 | 【更新】 データベース接続エラー                                                   | 異常系 | データベース接続が失敗                         | HTTP 500 Internal Server Error        | `RecipeController::update()`   |
| 3-9-135 | 【削除】 正常な料理削除                                                           | 正常系 | 有効な料理 ID を提供                           | HTTP 200 JSON success                 | `RecipeController::destroy()`  |
| 3-9-136 | 【削除】 削除成功メッセージの確認                                                 | 正常系 | 正常な料理削除後                               | 料理名を含むメッセージが返される      | `RecipeController::destroy()`  |
| 3-9-137 | 【削除】 存在しない料理削除                                                       | 異常系 | 存在しない料理 ID を提供                       | HTTP 404 Not Found                    | `RecipeController::destroy()`  |
| 3-9-138 | 【削除】 他グループの料理削除                                                     | 異常系 | 他グループの料理 ID を提供                     | HTTP 404 Not Found                    | `RecipeController::destroy()`  |
| 3-9-139 | 【削除】 未認証ユーザー                                                           | 異常系 | 認証されていないユーザー                       | HTTP 401 Unauthorized                 | `RecipeController::destroy()`  |
| 3-9-140 | 【削除】 グループが存在しない                                                     | 異常系 | ユーザーにグループが紐づいていない             | HTTP 422 Unprocessable Entity         | `RecipeController::destroy()`  |
| 3-9-141 | 【削除】 データベース接続エラー                                                   | 異常系 | データベース接続が失敗                         | HTTP 500 Internal Server Error        | `RecipeController::destroy()`  |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
