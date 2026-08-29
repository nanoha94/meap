# ShoppingItemController テストケース詳細仕様

## 概要

ShoppingItemController のテストケースの詳細仕様を示します。買い物アイテムの一覧取得、一括作成、一括更新、一括削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                            | 種別   | 入力条件                                                       | 期待される出力                                   | 該当メソッド                              |
| ------ | ------------------------------------------------------------------- | ------ | -------------------------------------------------------------- | ------------------------------------------------ | ----------------------------------------- |
| 3-9-1  | 【一覧取得】 正常な買い物アイテム一覧取得                           | 正常系 | 認証済み、bulkStore で order 等を指定して同一カテゴリに2件作成済み | HTTP 200 JSON success                            | `ShoppingItemController::index()`         |
| 3-9-2  | 【一覧取得】 カテゴリ別アイテム取得確認                             | 正常系 | 2カテゴリに bulkStore で各1件（order 等指定）作成済み              | 両カテゴリのアイテムが取得できる                 | `ShoppingItemController::index()`         |
| 3-9-3  | 【一覧取得】 アイテムの並び順確認                                   | 正常系 | 同一カテゴリに bulkStore で order 0,1,2 で3件作成済み            | 指定した order 順で並ぶ                          | `ShoppingItemController::index()`         |
| 3-9-4  | 【一覧取得】 レスポンス形式確認                                     | 正常系 | bulkStore で order 等を指定して1件作成済み                     | 正しい JSON 形式でレスポンスが返される           | `ShoppingItemController::index()`         |
| 3-9-5  | 【一覧取得】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                                       | HTTP 401 Unauthorized                            | `ShoppingItemController::index()`         |
| 3-9-6  | 【一覧取得】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない                             | HTTP 422 Unprocessable Entity                    | `ShoppingItemController::index()`         |
| 3-9-7  | 【一覧取得】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                                         | HTTP 500 Internal Server Error                   | `ShoppingItemController::index()`         |
| 3-9-8  | 【一覧取得】 ShoppingService 例外                                   | 異常系 | ShoppingService で例外が発生                                   | HTTP 500 Internal Server Error                   | `ShoppingItemController::index()`         |
| 3-9-9  | 【一括作成】 1件の一括作成（タグなし）                              | 正常系 | data に name, categoryId, order, isPinned, isChecked を含む1件 | HTTP 201 Created                                 | `ShoppingItemController::bulkStore()`     |
| 3-9-10 | 【一括作成】 1件の一括作成（タグあり）                              | 正常系 | 上記に加え tags を指定した1件                                  | タグ付きでアイテムが正常に作成される             | `ShoppingItemController::bulkStore()`     |
| 3-9-11 | 【一括作成】 id 指定でタグを紐づけ（name 省略）                    | 正常系 | 既存タグの id のみ指定（name 省略）                            | HTTP 201 Created、タグ付きでアイテムが正常に作成される | `ShoppingItemController::bulkStore()`     |
| 3-9-12 | 【一括作成】 タグ未指定でアイテム作成（tags 省略）                  | 正常系 | data に1件、order 等は指定し tags のみ省略                     | タグなしでアイテムが正常に作成される             | `ShoppingItemController::bulkStore()`     |
| 3-9-13 | 【一括作成】 タグ空配列でアイテム作成（tags=[]）                    | 正常系 | data に1件、order 等を指定し tags=[]                           | タグなしでアイテムが正常に作成される             | `ShoppingItemController::bulkStore()`     |
| 3-9-14 | 【一括作成】 タグ null でアイテム作成（tags=null）                  | 正常系 | data に1件、order 等を指定し tags=null                         | タグなしでアイテムが正常に作成される             | `ShoppingItemController::bulkStore()`     |
| 3-9-15 | 【一括作成】 数量情報の確認                                         | 正常系 | data に isPinned・isChecked・order を含む1件で作成             | index で isPinned・isChecked・order が取得できる | `ShoppingItemController::bulkStore()`     |
| 3-9-16 | 【一括作成】 複数件の一括作成                                       | 正常系 | 同一カテゴリで order を連番にした複数件                        | HTTP 201 Created                                 | `ShoppingItemController::bulkStore()`     |
| 3-9-17 | 【一括作成】 一括作成成功メッセージの確認                           | 正常系 | 有効な一括作成リクエスト1件送信後                              | 作成件数を含む適切なメッセージが返される         | `ShoppingItemController::bulkStore()`     |
| 3-9-18 | 【一括作成】 バリデーションエラー（data 未入力）                    | 異常系 | data フィールドが未入力                                        | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-19 | 【一括作成】 バリデーションエラー（data が配列でない）              | 異常系 | data が配列でない（文字列など）                                | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-20 | 【一括作成】 バリデーションエラー（data が空配列）                  | 異常系 | data が空配列（min:1 違反）                                    | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-21 | 【一括作成】 バリデーションエラー（data が件数上限超過）            | 異常系 | data が 501 件（max:500 違反）                                 | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-22 | 【一括作成】 バリデーションエラー（name 未入力）                    | 異常系 | data.\*.name のみ未入力（order 等は有効）                      | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-23 | 【一括作成】 バリデーションエラー（name が文字列でない）            | 異常系 | order 等は有効で data.\*.name が非文字列                       | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-24 | 【一括作成】 バリデーションエラー（name が 255 文字超過）           | 異常系 | order 等は有効で data.\*.name が 256 文字以上                  | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-25 | 【一括作成】 バリデーションエラー（categoryId 未入力）              | 異常系 | order 等は有効で data.\*.categoryId のみ未入力                 | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-26 | 【一括作成】 バリデーションエラー（categoryId が UUID 形式でない）  | 異常系 | order 等は有効で data.\*.categoryId が不正 UUID                | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-27 | 【一括作成】 バリデーションエラー（tags が配列でない）              | 異常系 | order 等は有効で data.\*.tags が配列でない                     | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-28 | 【一括作成】 バリデーションエラー（tags.id が UUID 形式でない）     | 異常系 | order 等は有効で data.\*.tags.\*.id が不正                     | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-29 | 【一括作成】 バリデーションエラー（tags.id と tags.name が両方未指定） | 異常系 | order 等は有効で data.\*.tags.\*.id と data.\*.tags.\*.name が両方未指定 | HTTP 422 Validation Error、`data.*.tags.*.name` に id_or_name_required | `ShoppingItemBulkStoreRequest::withValidator()` |
| 3-9-30 | 【一括作成】 バリデーションエラー（tags.name が文字列でない）       | 異常系 | order 等は有効で data.\*.tags.\*.name が非文字列               | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-31 | 【一括作成】 バリデーションエラー（tags.name が 255 文字超過）      | 異常系 | order 等は有効で data.\*.tags.\*.name が 256 文字以上          | HTTP 422 Validation Error                        | `ShoppingItemBulkStoreRequest::rules()`   |
| 3-9-32 | 【一括作成】 存在しないカテゴリ ID                                  | 異常系 | order 等は有効だが categoryId が存在しない UUID                | HTTP 404 Not Found                               | `ShoppingItemController::bulkStore()`     |
| 3-9-33 | 【一括作成】 未認証ユーザー                                         | 異常系 | 認証なしで order 等を含む有効な data を送信                    | HTTP 401 Unauthorized                            | `ShoppingItemController::bulkStore()`     |
| 3-9-34 | 【一括作成】 グループが存在しない                                   | 異常系 | グループ未所属ユーザーが有効な data を送信                     | HTTP 422 Unprocessable Entity                    | `ShoppingItemController::bulkStore()`     |
| 3-9-35 | 【一括作成】 サービス例外                                           | 異常系 | 有効な data で bulkCreate が例外                               | HTTP 500 Internal Server Error                   | `ShoppingItemController::bulkStore()`     |
| 3-9-36 | 【一括更新】 正常な買い物アイテム一括更新                           | 正常系 | 有効なアイテムデータ配列を提供                                 | HTTP 200 JSON success                            | `ShoppingItemController::bulkUpdate()`    |
| 3-9-37 | 【一括更新】 一括更新成功メッセージの確認                           | 正常系 | 正常な一括更新後                                               | 更新件数を含む適切なメッセージが返される         | `ShoppingItemController::bulkUpdate()`    |
| 3-9-38 | 【一括更新】 既存タグを ID 未指定・同名で更新                       | 正常系 | 既存タグと同じ名前を ID 未指定で提供                           | 既存タグ ID が再利用され新規作成されない         | `ShoppingItemController::bulkUpdate()`    |
| 3-9-39 | 【一括更新】 新規タグを ID 未指定で追加                             | 正常系 | 新しいタグ名を ID 未指定で提供                                 | 新しいタグが作成されアイテムに紐づく             | `ShoppingItemController::bulkUpdate()`    |
| 3-9-40 | 【一括更新】 既存タグと新規タグを混在させた更新                     | 正常系 | 既存・新規タグを混在させて提供                                 | 既存タグは再利用、新規タグは作成される           | `ShoppingItemController::bulkUpdate()`    |
| 3-9-41 | 【一括更新】 タグ未指定でアイテム更新（tags 省略）                  | 正常系 | tags フィールドを省略してアイテム更新                          | タグなしでアイテムが正常に更新される             | `ShoppingItemController::bulkUpdate()`    |
| 3-9-42 | 【一括更新】 id 指定でタグを紐づけ（name 省略）                    | 正常系 | 既存タグの id のみ指定（name 省略）                            | HTTP 200 JSON success、タグ付きでアイテムが正常に更新される | `ShoppingItemController::bulkUpdate()`    |
| 3-9-43 | 【一括更新】 タグ空配列でアイテム更新（tags=[]）                    | 正常系 | tags フィールドを空配列で送信                                  | タグなしでアイテムが正常に更新される             | `ShoppingItemController::bulkUpdate()`    |
| 3-9-44 | 【一括更新】 タグ null でアイテム更新（tags=null）                  | 正常系 | tags フィールドを null で送信                                  | タグなしでアイテムが正常に更新される             | `ShoppingItemController::bulkUpdate()`    |
| 3-9-45 | 【一括更新】 存在しないアイテムの更新                               | 異常系 | 存在しない ID を含むデータ配列                                 | HTTP 404 Not Found                               | `ShoppingItemController::bulkUpdate()`    |
| 3-9-46 | 【一括更新】 他グループのアイテム更新                               | 異常系 | 他グループの ID を含むデータ配列                               | HTTP 404 Not Found                               | `ShoppingItemController::bulkUpdate()`    |
| 3-9-47 | 【一括更新】 存在しないタグ ID を指定                               | 異常系 | 存在しないタグ ID を指定                                       | HTTP 500 Internal Server Error                   | `ShoppingItemController::bulkUpdate()`    |
| 3-9-48 | 【一括更新】 バリデーションエラー（data 未入力）                    | 異常系 | data フィールドが未入力                                        | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-49 | 【一括更新】 バリデーションエラー（data が配列でない）              | 異常系 | data が配列でない（文字列など）                                | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-50 | 【一括更新】 バリデーションエラー（data が空配列）                  | 異常系 | data が空配列（min:1 違反）                                    | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-51 | 【一括更新】 バリデーションエラー（id 未入力）                      | 異常系 | data.\*.id が未入力                                            | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-52 | 【一括更新】 バリデーションエラー（id が UUID 形式でない）          | 異常系 | data.\*.id が UUID 形式でない                                  | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-53 | 【一括更新】 バリデーションエラー（name 未入力）                    | 異常系 | data.\*.name が未入力                                          | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-54 | 【一括更新】 バリデーションエラー（name が文字列でない）            | 異常系 | data.\*.name が文字列でない                                    | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-55 | 【一括更新】 バリデーションエラー（name が 255 文字超過）           | 異常系 | data.\*.name が 256 文字以上                                   | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-56 | 【一括更新】 バリデーションエラー（categoryId 未入力）              | 異常系 | data.\*.categoryId が未入力                                    | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-57 | 【一括更新】 バリデーションエラー（categoryId が UUID 形式でない）  | 異常系 | data.\*.categoryId が UUID 形式でない                          | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-58 | 【一括更新】 バリデーションエラー（isPinned 未入力）                | 異常系 | data.\*.isPinned が未入力                                      | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-59 | 【一括更新】 バリデーションエラー（isPinned が boolean 型でない）   | 異常系 | data.\*.isPinned が boolean 型でない                           | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-60 | 【一括更新】 バリデーションエラー（isChecked 未入力）               | 異常系 | data.\*.isChecked が未入力                                     | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-61 | 【一括更新】 バリデーションエラー（isChecked が boolean 型でない）  | 異常系 | data.\*.isChecked が boolean 型でない                          | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-62 | 【一括更新】 バリデーションエラー（order 未入力）                   | 異常系 | data.\*.order が未入力                                         | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-63 | 【一括更新】 バリデーションエラー（order が数値でない）             | 異常系 | data.\*.order が数値でない                                     | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-64 | 【一括更新】 バリデーションエラー（order が負の値）                 | 異常系 | data.\*.order が 0 未満の負の値                                | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-65 | 【一括更新】 バリデーションエラー（tags が配列でない）              | 異常系 | data.\*.tags が配列でない                                      | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-66 | 【一括更新】 バリデーションエラー（tags.id が UUID 形式でない）     | 異常系 | data._.tags._.id が UUID 形式でない                            | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-67 | 【一括更新】 バリデーションエラー（tags.id と tags.name が両方未指定） | 異常系 | data.\*.tags.\*.id と data.\*.tags.\*.name が両方未指定         | HTTP 422 Validation Error、`data.*.tags.*.name` に id_or_name_required | `ShoppingItemBulkUpdateRequest::withValidator()` |
| 3-9-68 | 【一括更新】 バリデーションエラー（tags.name が文字列でない）       | 異常系 | data._.tags._.name が文字列でない                              | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-69 | 【一括更新】 バリデーションエラー（tags.name が 255 文字超過）      | 異常系 | data._.tags._.name が 256 文字以上                             | HTTP 422 Validation Error                        | `ShoppingItemBulkUpdateRequest::rules()`  |
| 3-9-70 | 【一括更新】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                                       | HTTP 401 Unauthorized                            | `ShoppingItemController::bulkUpdate()`    |
| 3-9-71 | 【一括更新】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない                             | HTTP 422 Unprocessable Entity                    | `ShoppingItemController::bulkUpdate()`    |
| 3-9-72 | 【一括更新】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                                         | HTTP 500 Internal Server Error                   | `ShoppingItemController::bulkUpdate()`    |
| 3-9-73 | 【一括更新】 アイテム更新失敗                                       | 異常系 | ShoppingItem::update() が失敗                                  | HTTP 500 Internal Server Error                   | `ShoppingItemController::bulkUpdate()`    |
| 3-9-74 | 【一括削除】 正常な買い物アイテム一括削除                           | 正常系 | 有効なアイテム ID 配列を提供                                   | HTTP 200 JSON success                            | `ShoppingItemController::bulkDestroy()`   |
| 3-9-75 | 【一括削除】 一括削除成功メッセージの確認                           | 正常系 | 正常な一括削除後                                               | 削除件数を含む適切なメッセージが返される         | `ShoppingItemController::bulkDestroy()`   |
| 3-9-76 | 【一括削除】 存在しないアイテムの削除                               | 異常系 | 存在しない ID を含む配列を提供                                 | HTTP 404 Not Found                               | `ShoppingItemController::bulkDestroy()`   |
| 3-9-77 | 【一括削除】 他グループのアイテム削除                               | 異常系 | 他グループの ID を含む配列を提供                               | HTTP 404 Not Found                               | `ShoppingItemController::bulkDestroy()`   |
| 3-9-78 | 【一括削除】 バリデーションエラー（IDs 未入力）                     | 異常系 | ids フィールドが未入力                                         | HTTP 422 Validation Error                        | `ShoppingItemBulkDestroyRequest::rules()` |
| 3-9-79 | 【一括削除】 バリデーションエラー（IDs が配列でない）               | 異常系 | ids が配列でない（文字列など）                                 | HTTP 422 Validation Error                        | `ShoppingItemBulkDestroyRequest::rules()` |
| 3-9-80 | 【一括削除】 バリデーションエラー（IDs が空配列）                   | 異常系 | ids が空配列（min:1 違反）                                     | HTTP 422 Validation Error                        | `ShoppingItemBulkDestroyRequest::rules()` |
| 3-9-81 | 【一括削除】 バリデーションエラー（ids が件数上限超過）             | 異常系 | ids が 501 件（max:500 違反）                                  | HTTP 422 Validation Error                        | `ShoppingItemBulkDestroyRequest::rules()` |
| 3-9-82 | 【一括削除】 バリデーションエラー（ID が UUID 形式でない）          | 異常系 | ids.\* が UUID 形式でない                                      | HTTP 422 Validation Error                        | `ShoppingItemBulkDestroyRequest::rules()` |
| 3-9-83 | 【一括削除】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー                                       | HTTP 401 Unauthorized                            | `ShoppingItemController::bulkDestroy()`   |
| 3-9-84 | 【一括削除】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない                             | HTTP 422 Unprocessable Entity                    | `ShoppingItemController::bulkDestroy()`   |
| 3-9-85 | 【一括削除】 データベース接続エラー                                 | 異常系 | データベース接続が失敗                                         | HTTP 500 Internal Server Error                   | `ShoppingItemController::bulkDestroy()`   |
| 3-9-86 | 【一括削除】 アイテム削除失敗                                       | 異常系 | ShoppingItem::delete() が失敗                                  | HTTP 500 Internal Server Error                   | `ShoppingItemController::bulkDestroy()`   |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/ShoppingItemControllerTest.php --stop-on-failure
```
