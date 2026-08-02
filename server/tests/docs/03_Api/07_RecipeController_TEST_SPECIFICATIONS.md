# RecipeController テストケース詳細仕様

## 概要

RecipeController のテストケースの詳細仕様を示します。料理の一覧取得、作成、詳細取得、更新、削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
| ---- | -------- | ---- | -------- | -------------- | ------------ |
| 3-7-1 | 【一覧取得】 正常な料理一覧取得 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-2 | 【一覧取得】 レスポンス形式確認 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-3 | 【一覧取得】 sort=name&order=asc で名前昇順にソートされていること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-4 | 【一覧取得】 sort=name&order=desc で名前降順にソートされていること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-5 | 【一覧取得】 sort=created_at&order=desc で作成日降順にソートされていること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-6 | 【一覧取得】 sort=created_at&order=asc で作成日昇順にソートされていること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-7 | 【一覧取得】 sort=last_planned_date&order=desc で献立日降順、NULL は末尾になること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-8 | 【一覧取得】 sort=last_planned_date&order=asc で献立日昇順、NULL は末尾になること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-9 | 【一覧取得】 パラメータ未指定時のデフォルト（created_at desc）を確認 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-10 | 【一覧取得】 recipe_name を指定して料理名で絞り込みできること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-11 | 【一覧取得】 ingredient_name を指定して食材名で絞り込みできること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-12 | 【一覧取得】 category_ids を指定してカテゴリで絞り込みできること（指定したいずれかのカテゴリに属するレシピが返る） | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-13 | 【一覧取得】 複数の category_ids を指定して OR 条件で絞り込みできること | 正常系 | 複数の category_ids を OR 条件で指定 | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-14 | 【一覧取得】 last_planned_date_from / last_planned_date_to を指定して前回献立日で絞り込みできること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-15 | 【一覧取得】 last_planned_date_from のみ指定して前回献立日で絞り込みできること（その日以降） | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-16 | 【一覧取得】 last_planned_date_to のみ指定して前回献立日で絞り込みできること（その日以前） | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-17 | 【一覧取得】 複数フィルタパラメータを同時に指定した場合、AND 条件で絞り込みできること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-18 | 【一覧取得】 絞り込みパラメータをすべて指定した場合、AND 条件で絞り込みできること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-19 | 【一覧取得】 limit/offset 指定時に正しい件数・位置で取得できること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-20 | 【一覧取得】 limit のみ指定時にデフォルト offset=0 で取得できること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-21 | 【一覧取得】 offset のみ指定時にデフォルト limit=15 で取得できること | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::index()` |
| 3-7-22 | 【一覧取得】 未認証ユーザー | 異常系 | 認証されていないユーザー | HTTP 401 Unauthorized | `RecipeController::index()` |
| 3-7-23 | 【一覧取得】 グループが存在しない | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity | `RecipeController::index()` |
| 3-7-24 | 【一覧取得】 バリデーションエラー（limit が整数でない） | 異常系 | limit が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-25 | 【一覧取得】 バリデーションエラー（limit が 1 未満） | 異常系 | limit が 1 未満 の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-26 | 【一覧取得】 バリデーションエラー（limit が 100 超過） | 異常系 | limit が 100 超過 の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-27 | 【一覧取得】 バリデーションエラー（offset が整数でない） | 異常系 | offset が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-28 | 【一覧取得】 バリデーションエラー（offset が 0 未満） | 異常系 | offset が 0 未満 の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-29 | 【一覧取得】 バリデーションエラー（sort が文字列でない） | 異常系 | sort が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-30 | 【一覧取得】 バリデーションエラー（sort が不正な値） | 異常系 | sort が不正な値 の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-31 | 【一覧取得】 バリデーションエラー（order が文字列でない） | 異常系 | order が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-32 | 【一覧取得】 バリデーションエラー（order が不正な値） | 異常系 | order が不正な値 の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-33 | 【一覧取得】 バリデーションエラー（recipe_name が文字列でない） | 異常系 | recipe_name が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-34 | 【一覧取得】 バリデーションエラー（recipe_name が 255 文字超過） | 異常系 | recipe_name が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-35 | 【一覧取得】 バリデーションエラー（ingredient_name が文字列でない） | 異常系 | ingredient_name が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-36 | 【一覧取得】 バリデーションエラー（ingredient_name が 255 文字超過） | 異常系 | ingredient_name が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-37 | 【一覧取得】 バリデーションエラー（category_ids が配列でない） | 異常系 | category_ids が配列でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-38 | 【一覧取得】 バリデーションエラー（category_ids.* が UUID 形式でない） | 異常系 | category_ids.* が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-39 | 【一覧取得】 バリデーションエラー（category_ids.* が存在しない ID） | 異常系 | 存在しないカテゴリ ID を category_ids に指定 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-40 | 【一覧取得】 バリデーションエラー（last_planned_date_from が日付形式でない） | 異常系 | last_planned_date_from が日付形式でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-41 | 【一覧取得】 バリデーションエラー（last_planned_date_to が日付形式でない） | 異常系 | last_planned_date_to が日付形式でない の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-42 | 【一覧取得】 バリデーションエラー（last_planned_date_to が last_planned_date_from より前） | 異常系 | last_planned_date_to が last_planned_date_from より前 の入力 | HTTP 422 Validation Error | `RecipeController::index()` |
| 3-7-43 | 【一覧取得】 データベース接続エラー | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::index()` |
| 3-7-44 | 【一覧取得】 RecipeService 例外 | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::index()` |
| 3-7-45 | 【新規作成】 正常な料理作成 | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-46 | 【新規作成】 最小限のデータで料理作成 | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-47 | 【新規作成】 料理にカテゴリを紐づけ | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-48 | 【新規作成】 料理に食材を紐づけ | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-49 | 【新規作成】 最小限の必須フィールドのみで食材を紐づけ | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-50 | 【新規作成】 料理に手順を紐づけ | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-51 | 【新規作成】 最小限の必須フィールドのみで手順を紐づけ | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-52 | 【新規作成】 料理に画像を紐づけ | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-53 | 【新規作成】 requires_quantity=true の食材単位で数量指定 | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-54 | 【新規作成】 requires_quantity=false の食材単位で数量指定 | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-55 | 【新規作成】 requires_quantity=false の食材単位で数量省略 | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-56 | 【新規作成】 すべての項目を含む料理作成 | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-57 | 【新規作成】 servingCount が null でも正常に作成できる | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-58 | 【新規作成】 同一材料名で単位が異なる行は複数登録できる | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-59 | 【新規作成】 quantityDisplay に分数表記（1/2）を指定して保存・取得できる | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-60 | 【新規作成】 quantityDisplay に小数表記（0.5）を指定して保存・取得できる | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-61 | 【新規作成】 quantityDisplay のみ指定して保存・取得できる | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-62 | 【新規作成】 id 指定で既存食材を紐づけ（name 省略） | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-63 | 【新規作成】 categoryId/categoryName 省略時はデフォルトカテゴリーにフォールバック | 正常系 | 認証済みユーザー | HTTP 201 Created、レスポンス data に作成レシピの id が含まれる | `RecipeController::store()` |
| 3-7-64 | 【新規作成】 ingredientCategories を新規作成し categoryName で食材を紐づけ | 正常系 | ingredientCategories と categoryName を同梱 | HTTP 201、食材が指定カテゴリーに紐づく | `RecipeController::store()` |
| 3-7-65 | 【新規作成】 ingredientCategories に isDefault: true が1つ含まれている場合、そのカテゴリーが is_default=true で作成される | 正常系 | ingredientCategories に isDefault: true を1つ指定 | HTTP 201 Created、指定カテゴリーの isDefault が true | `RecipeController::store()` |
| 3-7-66 | 【新規作成】 未認証ユーザー | 異常系 | 認証されていないユーザー | HTTP 401 Unauthorized | `RecipeController::store()` |
| 3-7-67 | 【新規作成】 グループが存在しない | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity | `RecipeController::store()` |
| 3-7-68 | 【新規作成】 バリデーションエラー（name 未入力） | 異常系 | name 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-69 | 【新規作成】 バリデーションエラー（name が文字列でない） | 異常系 | name が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-70 | 【新規作成】 バリデーションエラー（name が 255 文字超過） | 異常系 | name が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-71 | 【新規作成】 バリデーションエラー（url が文字列でない） | 異常系 | url が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-72 | 【新規作成】 バリデーションエラー（url が 2048 文字超過） | 異常系 | url が 2048 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-73 | 【新規作成】 バリデーションエラー（thumbnailId が UUID 形式でない） | 異常系 | thumbnailId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-74 | 【新規作成】 バリデーションエラー（categoryIds が配列でない） | 異常系 | categoryIds が配列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-75 | 【新規作成】 バリデーションエラー（categoryIds.* が UUID 形式でない） | 異常系 | categoryIds.* が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-76 | 【新規作成】 バリデーションエラー（categoryIds.* 未入力） | 異常系 | categoryIds.* 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-77 | 【新規作成】 バリデーションエラー（ingredientCategories が配列でない） | 異常系 | ingredientCategories が配列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-78 | 【新規作成】 バリデーションエラー（ingredientCategories.\*.id が UUID 形式でない） | 異常系 | ingredientCategories.\*.id が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-79 | 【新規作成】 バリデーションエラー（ingredientCategories.\*.name 未入力） | 異常系 | ingredientCategories.\*.name 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-80 | 【新規作成】 バリデーションエラー（ingredientCategories.\*.name が文字列でない） | 異常系 | ingredientCategories.\*.name が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-81 | 【新規作成】 バリデーションエラー（ingredientCategories.\*.name が 255 文字超過） | 異常系 | ingredientCategories.\*.name が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-82 | 【新規作成】 バリデーションエラー（ingredientCategories.\*.order 未入力） | 異常系 | ingredientCategories.\*.order 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-83 | 【新規作成】 バリデーションエラー（ingredientCategories.\*.order が整数でない） | 異常系 | ingredientCategories.\*.order が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-84 | 【新規作成】 バリデーションエラー（ingredientCategories.\*.order が負の値） | 異常系 | ingredientCategories.\*.order が負の値 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-85 | 【新規作成】 バリデーションエラー（ingredientCategories.\*.name が重複） | 異常系 | ingredientCategories.\*.name が重複 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-86 | 【新規作成】 バリデーションエラー（ingredientCategories.\*.id が重複） | 異常系 | ingredientCategories.\*.id が重複 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-87 | 【新規作成】 バリデーションエラー（ingredientCategories に isDefault: true が0個） | 異常系 | ingredientCategories に isDefault: true が0個 | HTTP 422 Validation Error、`ingredientCategories` | `RecipeController::store()` |
| 3-7-88 | 【新規作成】 バリデーションエラー（ingredientCategories に isDefault: true が2個以上） | 異常系 | ingredientCategories に isDefault: true が2個以上 | HTTP 422 Validation Error、`ingredientCategories` | `RecipeController::store()` |
| 3-7-89 | 【新規作成】 バリデーションエラー（ingredients が配列でない） | 異常系 | ingredients が配列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-90 | 【新規作成】 バリデーションエラー（ingredients.\*.id が UUID 形式でない） | 異常系 | ingredients.\*.id が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-91 | 【新規作成】 バリデーションエラー（ingredients.\*.id と ingredients.\*.name が両方未指定） | 異常系 | ingredients.\*.id と ingredients.\*.name が両方未指定 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-92 | 【新規作成】 バリデーションエラー（ingredients.\*.name が文字列でない） | 異常系 | ingredients.\*.name が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-93 | 【新規作成】 バリデーションエラー（ingredients.\*.name が 255 文字超過） | 異常系 | ingredients.\*.name が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-94 | 【新規作成】 バリデーションエラー（ingredients.\*.unitId 未入力） | 異常系 | ingredients.\*.unitId 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-95 | 【新規作成】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない） | 異常系 | ingredients.\*.unitId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-96 | 【新規作成】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない） | 異常系 | ingredients.\*.categoryId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-97 | 【新規作成】 バリデーションエラー（ingredients.\*.categoryName が文字列でない） | 異常系 | ingredients.\*.categoryName が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-98 | 【新規作成】 バリデーションエラー（ingredients.\*.categoryName が 255 文字超過） | 異常系 | ingredients.\*.categoryName が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-99 | 【新規作成】 バリデーションエラー（ingredients.\*.categoryName が ingredientCategories に含まれない） | 異常系 | ingredients.\*.categoryName が ingredientCategories に含まれない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-100 | 【新規作成】 バリデーションエラー（ingredients.\*.quantityDisplay が parse 不可） | 異常系 | ingredients.\*.quantityDisplay が parse 不可 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-101 | 【新規作成】 バリデーションエラー（ingredients.\*.quantityDisplay が文字列でない） | 異常系 | ingredients.\*.quantityDisplay が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-102 | 【新規作成】 バリデーションエラー（ingredients.\*.quantityDisplay が 50 文字超過） | 異常系 | ingredients.\*.quantityDisplay が 50 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-103 | 【新規作成】 バリデーションエラー（ingredients.\*.order が整数でない） | 異常系 | ingredients.\*.order が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-104 | 【新規作成】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で数量省略） | 異常系 | ingredients\*.requires_quantity=true の単位で quantityDisplay を省略 | HTTP 422 Validation Error、`ingredients.*.quantityDisplay` | `RecipeController::store()` |
| 3-7-105 | 【新規作成】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で quantityDisplay が空文字） | 異常系 | ingredients\*.requires_quantity=true の単位で quantityDisplay="" を指定 | HTTP 422 Validation Error、`ingredients.*.quantityDisplay` | `RecipeController::store()` |
| 3-7-106 | 【新規作成】 バリデーションエラー（ingredients.\*.order が負の値） | 異常系 | ingredients.\*.order が 0 未満の負の値 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-107 | 【新規作成】 バリデーションエラー（steps が配列でない） | 異常系 | steps が配列でない | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-108 | 【新規作成】 バリデーションエラー（steps.\*.id が UUID 形式でない） | 異常系 | steps.\*.id が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-109 | 【新規作成】 バリデーションエラー（steps.\*.instruction 未入力） | 異常系 | steps.\*.instruction 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-110 | 【新規作成】 バリデーションエラー（steps.\*.instruction が文字列でない） | 異常系 | steps.\*.instruction が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-111 | 【新規作成】 バリデーションエラー（steps.\*.instruction が 255 文字超過） | 異常系 | steps.\*.instruction が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-112 | 【新規作成】 バリデーションエラー（steps.\*.imageId が UUID 形式でない） | 異常系 | steps.\*.imageId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-113 | 【新規作成】 バリデーションエラー（steps.\*.order 未入力） | 異常系 | steps.\*.order 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-114 | 【新規作成】 バリデーションエラー（steps.\*.order が整数でない） | 異常系 | steps.\*.order が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-115 | 【新規作成】 バリデーションエラー（steps.\*.order が負の値） | 異常系 | steps.\*.order が負の値 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-116 | 【新規作成】 バリデーションエラー（memo が文字列でない） | 異常系 | memo が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-117 | 【新規作成】 バリデーションエラー（memo が 255 文字超過） | 異常系 | memo が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-118 | 【新規作成】 バリデーションエラー（servingCount が整数でない） | 異常系 | servingCount が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-119 | 【新規作成】 バリデーションエラー（servingCount が 1 未満） | 異常系 | servingCount が 1 未満 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-120 | 【新規作成】 バリデーションエラー（cookingTime が整数でない） | 異常系 | cookingTime が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-121 | 【新規作成】 バリデーションエラー（cookingTime が 0 未満） | 異常系 | cookingTime が 0 未満 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-122 | 【新規作成】 バリデーションエラー（ownerUserId 未入力） | 異常系 | ownerUserId 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-123 | 【新規作成】 バリデーションエラー（ownerUserId が UUID 形式でない） | 異常系 | ownerUserId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-124 | 【新規作成】 バリデーションエラー（ingredients 同一 name・unitId・category の組み合わせが重複） | 異常系 | ingredients 同一 name・unitId・category の組み合わせが重複 の入力 | HTTP 422 Validation Error | `RecipeController::store()` |
| 3-7-125 | 【新規作成】 存在しない食材単位 ID 指定 | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-126 | 【新規作成】 他グループの食材単位 ID 指定 | 異常系 | 他グループの食材単位 ID を指定 | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-127 | 【新規作成】 存在しない食材カテゴリ ID 指定 | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-128 | 【新規作成】 他レシピの食材カテゴリ ID 指定 | 異常系 | 他レシピの食材カテゴリ ID を指定 | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-129 | 【新規作成】 レシピ内に存在しない categoryName 指定 | 異常系 | レシピ内に存在しない categoryName を指定 | HTTP 404 Not Found、食材カテゴリー未発見メッセージ | `RecipeController::store()` |
| 3-7-130 | 【新規作成】 存在しない料理カテゴリ ID 指定 | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-131 | 【新規作成】 他グループの料理カテゴリ ID 指定 | 異常系 | 他グループの料理カテゴリ ID を指定 | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-132 | 【新規作成】 存在しない画像 ID 指定（thumbnailId） | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-133 | 【新規作成】 他グループの画像 ID 指定（thumbnailId） | 異常系 | 他グループの画像 ID を thumbnailId に指定 | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-134 | 【新規作成】 存在しない画像 ID 指定（steps.\*.imageId） | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-135 | 【新規作成】 他グループの画像 ID 指定（steps.\*.imageId） | 異常系 | 他グループの画像 ID を steps.\*.imageId に指定 | HTTP 404 Not Found | `RecipeController::store()` |
| 3-7-136 | 【新規作成】 存在しない食材 ID 指定 | 異常系 | 存在しない食材 ID を指定 | HTTP 500 Internal Server Error | `RecipeController::store()` |
| 3-7-137 | 【新規作成】 他グループの食材 ID 指定 | 異常系 | 他グループの食材 ID を指定 | HTTP 500 Internal Server Error | `RecipeController::store()` |
| 3-7-138 | 【新規作成】 データベース接続エラー | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::store()` |
| 3-7-139 | 【新規作成】 料理作成失敗 | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::store()` |
| 3-7-140 | 【新規作成】 食材紐づけ失敗 | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::store()` |
| 3-7-141 | 【新規作成】 手順紐づけ失敗 | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::store()` |
| 3-7-142 | 【新規作成】 画像紐づけ失敗 | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::store()` |
| 3-7-143 | 【新規作成】 ImageService 例外 | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::store()` |
| 3-7-144 | 【詳細取得】 正常な料理詳細取得 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::show()` |
| 3-7-145 | 【詳細取得】 すべての項目を含む料理詳細取得 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::show()` |
| 3-7-146 | 【詳細取得】 quantity のみ保存された既存データは quantityDisplay を補完して返る | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::show()` |
| 3-7-147 | 【詳細取得】 未認証ユーザー | 異常系 | 認証されていないユーザー | HTTP 401 Unauthorized | `RecipeController::show()` |
| 3-7-148 | 【詳細取得】 グループが存在しない | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity | `RecipeController::show()` |
| 3-7-149 | 【詳細取得】 存在しない料理詳細取得 | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::show()` |
| 3-7-150 | 【詳細取得】 他グループの料理詳細取得 | 異常系 | 他グループの料理 ID を提供 | HTTP 404 Not Found | `RecipeController::show()` |
| 3-7-151 | 【詳細取得】 データベース接続エラー | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::show()` |
| 3-7-152 | 【更新】 正常な料理更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-153 | 【更新】 最小限のデータで料理更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-154 | 【更新】 料理のカテゴリ更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-155 | 【更新】 料理の食材更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-156 | 【更新】 最小限の必須フィールドのみで食材を更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-157 | 【更新】 料理の手順更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-158 | 【更新】 最小限の必須フィールドのみで手順を更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-159 | 【更新】 手順の画像を削除（imageId が null） | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-160 | 【更新】 手順の画像を削除（imageId キーが存在しない） | 正常系 | 既存の画像がある手順で imageId キーを省略 | 手順の画像が削除される | `RecipeController::update()` |
| 3-7-158 | 【更新】 料理の画像更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-159 | 【更新】 サムネイルを削除（thumbnailId が null） | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-160 | 【更新】 サムネイルを削除（thumbnailId キーが存在しない） | 正常系 | 既存のサムネイルがある料理で thumbnailId キーを省略 | サムネイルが削除される | `RecipeController::update()` |
| 3-7-161 | 【更新】 更新成功メッセージの確認 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-162 | 【更新】 requires_quantity=true の食材単位で数量指定 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-163 | 【更新】 requires_quantity=false の食材単位で数量指定 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-164 | 【更新】 requires_quantity=false の食材単位で数量省略 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-165 | 【更新】 すべての項目を含む料理更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-166 | 【更新】 servingCount が null でも正常に更新できる | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-170 | 【更新】 同一グループの他ユーザーの料理更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-171 | 【更新】 同一材料名で単位が異なる行は複数登録できる | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-172 | 【更新】 quantityDisplay を変更できる | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-173 | 【更新】 id 指定で既存食材カテゴリーを更新 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-174 | 【更新】 categoryId でレシピ内カテゴリーに食材を紐づけ | 正常系 | 既存レシピ内カテゴリーの categoryId を指定 | HTTP 200、食材が指定カテゴリーに紐づく | `RecipeController::update()` |
| 3-7-175 | 【更新】 ingredientCategories を新規追加（id 省略） | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-176 | 【更新】 非デフォルト食材カテゴリーを削除 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-177 | 【更新】 categoryName で DB 上の既存カテゴリーに食材を紐づけ | 正常系 | ingredientCategories 省略で categoryName を指定 | HTTP 200、DB 上の既存カテゴリーに紐づく | `RecipeController::update()` |
| 3-7-178 | 【更新】 ingredients を空配列で指定しても既存食材は削除されない | 正常系 | ingredients を空配列で指定 | HTTP 200、既存食材が維持される | `RecipeController::update()` |
| 3-7-179 | 【更新】 categoryId/categoryName 省略時はデフォルトカテゴリーにフォールバック | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::update()` |
| 3-7-180 | 【更新】 未認証ユーザー | 異常系 | 認証されていないユーザー | HTTP 401 Unauthorized | `RecipeController::update()` |
| 3-7-181 | 【更新】 グループが存在しない | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity | `RecipeController::update()` |
| 3-7-182 | 【更新】 バリデーションエラー（name 未入力） | 異常系 | name 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-183 | 【更新】 バリデーションエラー（name が文字列でない） | 異常系 | name が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-184 | 【更新】 バリデーションエラー（name が 255 文字超過） | 異常系 | name が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-185 | 【更新】 バリデーションエラー（url が文字列でない） | 異常系 | url が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-186 | 【更新】 バリデーションエラー（url が 2048 文字超過） | 異常系 | url が 2048 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-187 | 【更新】 バリデーションエラー（thumbnailId が UUID 形式でない） | 異常系 | thumbnailId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-188 | 【更新】 バリデーションエラー（categoryIds が配列でない） | 異常系 | categoryIds が配列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-189 | 【更新】 バリデーションエラー（categoryIds.\* が UUID 形式でない） | 異常系 | categoryIds.\* が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-190 | 【更新】 バリデーションエラー（categoryIds.* 未入力） | 異常系 | categoryIds.* 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-191 | 【更新】 バリデーションエラー（ingredientCategories が配列でない） | 異常系 | ingredientCategories が配列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-192 | 【更新】 バリデーションエラー（ingredientCategories.\*.id が UUID 形式でない） | 異常系 | ingredientCategories.\*.id が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-193 | 【更新】 バリデーションエラー（ingredientCategories.\*.name 未入力） | 異常系 | ingredientCategories.\*.name 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-194 | 【更新】 バリデーションエラー（ingredientCategories.\*.name が文字列でない） | 異常系 | ingredientCategories.\*.name が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-195 | 【更新】 バリデーションエラー（ingredientCategories.\*.name が 255 文字超過） | 異常系 | ingredientCategories.\*.name が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-196 | 【更新】 バリデーションエラー（ingredientCategories.\*.order 未入力） | 異常系 | ingredientCategories.\*.order 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-197 | 【更新】 バリデーションエラー（ingredientCategories.\*.order が整数でない） | 異常系 | ingredientCategories.\*.order が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-198 | 【更新】 バリデーションエラー（ingredientCategories.\*.order が負の値） | 異常系 | ingredientCategories.\*.order が負の値 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-199 | 【更新】 バリデーションエラー（ingredientCategories.\*.name が重複） | 異常系 | ingredientCategories.\*.name が重複 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-200 | 【更新】 バリデーションエラー（ingredientCategories.\*.id が重複） | 異常系 | ingredientCategories.\*.id が重複 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-201 | 【更新】 バリデーションエラー（ingredients が配列でない） | 異常系 | ingredients が配列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-202 | 【更新】 バリデーションエラー（ingredients.\*.id が UUID 形式でない） | 異常系 | ingredients.\*.id が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-203 | 【更新】 バリデーションエラー（ingredients.\*.id と ingredients.\*.name が両方未指定） | 異常系 | ingredients.\*.id と ingredients.\*.name が両方未指定 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-204 | 【更新】 バリデーションエラー（ingredients.\*.name が文字列でない） | 異常系 | ingredients.\*.name が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-205 | 【更新】 バリデーションエラー（ingredients.\*.name が 255 文字超過） | 異常系 | ingredients.\*.name が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-206 | 【更新】 バリデーションエラー（ingredients.\*.unitId 未入力） | 異常系 | ingredients.\*.unitId 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-207 | 【更新】 バリデーションエラー（ingredients.\*.unitId が UUID 形式でない） | 異常系 | ingredients.\*.unitId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-208 | 【更新】 バリデーションエラー（ingredients.\*.categoryId が UUID 形式でない） | 異常系 | ingredients.\*.categoryId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-209 | 【更新】 バリデーションエラー（ingredients.\*.categoryName が文字列でない） | 異常系 | ingredients.\*.categoryName が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-210 | 【更新】 バリデーションエラー（ingredients.\*.categoryName が 255 文字超過） | 異常系 | ingredients.\*.categoryName が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-211 | 【更新】 バリデーションエラー（ingredients.\*.categoryName が ingredientCategories に含まれない） | 異常系 | ingredients.\*.categoryName が ingredientCategories に含まれない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-212 | 【更新】 バリデーションエラー（ingredients.\*.quantityDisplay が parse 不可） | 異常系 | ingredients.\*.quantityDisplay が parse 不可 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-213 | 【更新】 バリデーションエラー（ingredients.\*.quantityDisplay が文字列でない） | 異常系 | ingredients.\*.quantityDisplay が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-214 | 【更新】 バリデーションエラー（ingredients.\*.quantityDisplay が 50 文字超過） | 異常系 | ingredients.\*.quantityDisplay が 50 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-215 | 【更新】 バリデーションエラー（requires_quantity=true の単位で quantityDisplay を null に指定） | 異常系 | requires_quantity=true の単位で quantityDisplay を null に指定 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-216 | 【更新】 バリデーションエラー（ingredients.\*.order が整数でない） | 異常系 | ingredients.\*.order が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-217 | 【更新】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で数量省略） | 異常系 | ingredients\*.requires_quantity=true の単位で quantityDisplay を省略 | HTTP 422 Validation Error、`ingredients.*.quantityDisplay` | `RecipeController::update()` |
| 3-7-218 | 【更新】 バリデーションエラー（ingredients\*.requires_quantity=true の単位で quantityDisplay が空文字） | 異常系 | ingredients\*.requires_quantity=true の単位で quantityDisplay="" を指定 | HTTP 422 Validation Error、`ingredients.*.quantityDisplay` | `RecipeController::update()` |
| 3-7-219 | 【更新】 バリデーションエラー（ingredients.\*.order が負の値） | 異常系 | ingredients.\*.order が 0 未満の負の値 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-220 | 【更新】 バリデーションエラー（steps が配列でない） | 異常系 | steps が配列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-221 | 【更新】 バリデーションエラー（steps.\*.id が UUID 形式でない） | 異常系 | steps.\*.id が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-222 | 【更新】 バリデーションエラー（steps.\*.instruction 未入力） | 異常系 | steps.\*.instruction 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-223 | 【更新】 バリデーションエラー（steps.\*.instruction が文字列でない） | 異常系 | steps.\*.instruction が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-224 | 【更新】 バリデーションエラー（steps.\*.instruction が 255 文字超過） | 異常系 | steps.\*.instruction が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-225 | 【更新】 バリデーションエラー（steps.\*.imageId が UUID 形式でない） | 異常系 | steps.\*.imageId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-226 | 【更新】 バリデーションエラー（steps.\*.order 未入力） | 異常系 | steps.\*.order 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-227 | 【更新】 バリデーションエラー（steps.\*.order が整数でない） | 異常系 | steps.\*.order が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-228 | 【更新】 バリデーションエラー（steps.\*.order が負の値） | 異常系 | steps.\*.order が負の値 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-229 | 【更新】 バリデーションエラー（memo が文字列でない） | 異常系 | memo が文字列でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-230 | 【更新】 バリデーションエラー（memo が 255 文字超過） | 異常系 | memo が 255 文字超過 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-231 | 【更新】 バリデーションエラー（servingCount が整数でない） | 異常系 | servingCount が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-232 | 【更新】 バリデーションエラー（servingCount が 1 未満） | 異常系 | servingCount が 1 未満 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-233 | 【更新】 バリデーションエラー（cookingTime が整数でない） | 異常系 | cookingTime が整数でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-234 | 【更新】 バリデーションエラー（cookingTime が 0 未満） | 異常系 | cookingTime が 0 未満 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-235 | 【更新】 バリデーションエラー（ownerUserId 未入力） | 異常系 | ownerUserId 未入力 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-236 | 【更新】 バリデーションエラー（ownerUserId が UUID 形式でない） | 異常系 | ownerUserId が UUID 形式でない の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-237 | 【更新】 バリデーションエラー（ingredients 同一 name・unitId・category の組み合わせが重複） | 異常系 | ingredients 同一 name・unitId・category の組み合わせが重複 の入力 | HTTP 422 Validation Error | `RecipeController::update()` |
| 3-7-238 | 【更新】 存在しない食材単位 ID 指定 | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-239 | 【更新】 他グループの食材単位 ID 指定 | 異常系 | 他グループの食材単位 ID を指定 | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-240 | 【更新】 存在しない食材カテゴリ ID 指定 | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-241 | 【更新】 他レシピの食材カテゴリ ID 指定 | 異常系 | 他レシピの食材カテゴリ ID を指定 | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-242 | 【更新】 存在しない料理カテゴリ ID 指定 | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-243 | 【更新】 他グループの料理カテゴリ ID 指定 | 異常系 | 他グループの料理カテゴリ ID を指定 | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-244 | 【更新】 存在しない画像 ID 指定（thumbnailId） | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-245 | 【更新】 他グループの画像 ID 指定（thumbnailId） | 異常系 | 他グループの画像 ID を thumbnailId に指定 | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-246 | 【更新】 存在しない画像 ID 指定（steps.\*.imageId） | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-247 | 【更新】 他グループの画像 ID 指定（steps.\*.imageId） | 異常系 | 他グループの画像 ID を steps.\*.imageId に指定 | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-248 | 【更新】 存在しない料理更新 | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-249 | 【更新】 他グループの料理更新 | 異常系 | 他グループの料理 ID を提供 | HTTP 404 Not Found | `RecipeController::update()` |
| 3-7-250 | 【更新】 当該レシピに存在しない ingredientCategories[].id 指定 | 異常系 | 当該レシピに存在しない ingredientCategories[].id を指定 | HTTP 404 Not Found、食材カテゴリー未発見メッセージ | `RecipeController::update()` |
| 3-7-251 | 【更新】 レシピ内に存在しない categoryName 指定 | 異常系 | レシピ内に存在しない categoryName を指定 | HTTP 404 Not Found、食材カテゴリー未発見メッセージ | `RecipeController::update()` |
| 3-7-252 | 【更新】 ingredientCategories を空配列で指定（デフォルト削除試行） | 異常系 | ingredientCategories を空配列で指定 | HTTP 400 Bad Request、削除不可メッセージ | `RecipeController::update()` |
| 3-7-253 | 【更新】 is_default カテゴリーは削除不可 | 異常系 | 認証済みユーザー | HTTP 400 Bad Request、削除不可メッセージ | `RecipeController::update()` |
| 3-7-254 | 【更新】 存在しない食材 ID 指定 | 異常系 | 存在しない食材 ID を指定 | HTTP 500 Internal Server Error | `RecipeController::update()` |
| 3-7-255 | 【更新】 他グループの食材 ID 指定 | 異常系 | 他グループの食材 ID を指定 | HTTP 500 Internal Server Error | `RecipeController::update()` |
| 3-7-256 | 【更新】 データベース接続エラー | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::update()` |
| 3-7-257 | 【削除】 正常な料理削除 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::destroy()` |
| 3-7-258 | 【削除】 削除成功メッセージの確認 | 正常系 | 認証済みユーザー | HTTP 200 JSON success | `RecipeController::destroy()` |
| 3-7-259 | 【削除】 未認証ユーザー | 異常系 | 認証されていないユーザー | HTTP 401 Unauthorized | `RecipeController::destroy()` |
| 3-7-260 | 【削除】 グループが存在しない | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity | `RecipeController::destroy()` |
| 3-7-261 | 【削除】 存在しない料理削除 | 異常系 | 認証済みユーザー | HTTP 404 Not Found | `RecipeController::destroy()` |
| 3-7-262 | 【削除】 他グループの料理削除 | 異常系 | 他グループの料理 ID を提供 | HTTP 404 Not Found | `RecipeController::destroy()` |
| 3-7-263 | 【削除】 同一グループの他ユーザーの料理削除 | 異常系 | 同一グループ内の他ユーザーの料理 ID を提供 | HTTP 403 Forbidden | `RecipeController::destroy()` |
| 3-7-264 | 【削除】 データベース接続エラー | 異常系 | 認証済みユーザー | HTTP 500 Internal Server Error | `RecipeController::destroy()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/RecipeControllerTest.php --stop-on-failure
```
