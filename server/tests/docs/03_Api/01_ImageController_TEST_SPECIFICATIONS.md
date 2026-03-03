# ImageController テストケース詳細仕様

## 概要

ImageController のテストケースの詳細仕様を示します。画像の一括アップロードと一括削除機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                                  | 種別   | 入力条件                                                     | 期待される出力                                                      | 該当メソッド                       |
| ------ | ------------------------------------------------------------------------- | ------ | ------------------------------------------------------------ | ------------------------------------------------------------------- | ---------------------------------- |
| 3-1-1  | 【一括アップロード】 正常な画像アップロード（1 枚）                       | 正常系 | 有効な画像ファイル 1 枚を提供                                | HTTP 200 JSON success                                               | `ImageController::bulkUpload()`    |
| 3-1-2  | 【一括アップロード】 複数画像の一括アップロード                           | 正常系 | 複数の有効な画像ファイルを提供                               | 全ての画像が正常にアップロードされる                                | `ImageController::bulkUpload()`    |
| 3-1-3  | 【一括アップロード】 グループ ID 配下に直接保存されることを確認           | 正常系 | 画像をアップロード                                           | グループ ID 配下に直接保存される（ディレクトリ分けなし）            | `ImageController::bulkUpload()`    |
| 3-1-4  | 【一括アップロード】 upload_path 指定時に指定パスに保存される             | 正常系 | `upload_path=users` で画像1枚                                | src に `images/users/` が含まれる                                   | `ImageController::bulkUpload()`    |
| 3-1-5  | 【一括アップロード】 upload_path 未指定時にグループ ID 配下に保存される   | 正常系 | `upload_path` なしで画像1枚                                  | src に `images/groups/{group_id}/` が含まれる（従来動作）           | `ImageController::bulkUpload()`    |
| 3-1-6  | 【一括アップロード】 未認証ユーザー                                       | 異常系 | 認証されていないユーザー                                     | HTTP 401 Unauthorized                                               | `ImageController::bulkUpload()`    |
| 3-1-7  | 【一括アップロード】 グループが存在しない                                 | 異常系 | ユーザーにグループが紐づいていない                           | HTTP 422 Unprocessable Entity                                       | `ImageController::bulkUpload()`    |
| 3-1-8  | 【一括アップロード】 ImageService 例外（データベースエラー）              | 異常系 | ImageService でデータベースエラーが発生                      | HTTP 500 Internal Server Error                                      | `ImageController::bulkUpload()`    |
| 3-1-9  | 【一括アップロード】 ImageService 例外                                    | 異常系 | ImageService で例外が発生                                    | HTTP 500 Internal Server Error                                      | `ImageController::bulkUpload()`    |
| 3-1-10 | 【一括アップロード】 ファイルアップロード失敗                             | 異常系 | ファイルシステムへの書き込みが失敗                           | HTTP 500 Internal Server Error                                      | `ImageController::bulkUpload()`    |
| 3-1-11 | 【一括アップロード】 バリデーションエラー（ファイル配列バリデーション）   | 異常系 | ファイル配列以外の形式を提供                                 | HTTP 422 Validation Error                                           | `ImageBulkUploadRequest::rules()`  |
| 3-1-12 | 【一括アップロード】 バリデーションエラー（最小ファイル数制限）           | 異常系 | ファイルが提供されていない                                   | HTTP 422 Validation Error                                           | `ImageBulkUploadRequest::rules()`  |
| 3-1-13 | 【一括アップロード】 バリデーションエラー（最大ファイル数制限）           | 異常系 | 20 個を超えるファイルを提供                                  | HTTP 422 Validation Error                                           | `ImageBulkUploadRequest::rules()`  |
| 3-1-14 | 【一括アップロード】 バリデーションエラー（ファイルサイズ制限）           | 異常系 | 制限を超えるファイルサイズを提供                             | HTTP 422 Validation Error                                           | `ImageBulkUploadRequest::rules()`  |
| 3-1-15 | 【一括アップロード】 バリデーションエラー（upload_path パストラバーサル） | 異常系 | `upload_path=../etc`                                         | HTTP 422 Validation Error                                           | `ImageBulkUploadRequest::rules()`  |
| 3-1-16 | 【一括アップロード】 バリデーションエラー（upload_path 最大文字数超過）   | 異常系 | `upload_path` が256文字以上                                  | HTTP 422 Validation Error                                           | `ImageBulkUploadRequest::rules()`  |
| 3-1-17 | 【一括削除】 正常な画像削除（1 枚）                                       | 正常系 | 有効な画像 ID 1 つと related_id を提供（事前に紐づけ作成）   | HTTP 200 JSON success、紐づけが解除される、画像レコードは残る       | `ImageController::bulkDestroy()`   |
| 3-1-18 | 【一括削除】 複数画像の一括削除                                           | 正常系 | 複数の有効な画像 ID と related_id を提供（事前に紐づけ作成） | HTTP 200 JSON success、全ての紐づけが解除される、画像レコードは残る | `ImageController::bulkDestroy()`   |
| 3-1-19 | 【一括削除】 削除成功メッセージの確認                                     | 正常系 | 正常な画像削除後（事前に紐づけ作成）                         | 紐づけ解除件数を含む適切なメッセージが返される                      | `ImageController::bulkDestroy()`   |
| 3-1-20 | 【一括削除】 存在しない画像 ID の削除                                     | 正常系 | 存在しない画像 ID と related_id を提供                       | HTTP 200 削除数 0 件で正常終了                                      | `ImageController::bulkDestroy()`   |
| 3-1-21 | 【一括削除】 指定した related_id との紐づけのみを解除                     | 正常系 | 画像 ID と related_id を提供（他の紐づけあり）               | HTTP 200 削除数 1 件、指定した紐づけのみ解除、他の紐づけは残る      | `ImageController::bulkDestroy()`   |
| 3-1-22 | 【一括削除】 紐づけ解除のみ（他の紐づけなしでも物理削除は行わない）       | 正常系 | 画像 ID と related_id を提供（他の紐づけなし）               | HTTP 200 削除数 1 件、紐づけが解除される、画像レコードは残る        | `ImageController::bulkDestroy()`   |
| 3-1-23 | 【一括削除】 複数の画像で紐づけ解除のみ                                   | 正常系 | 複数の画像 ID と related_id を提供（一部は他の紐づけあり）   | HTTP 200 全ての紐づけが解除される、画像レコードは全て残る           | `ImageController::bulkDestroy()`   |
| 3-1-24 | 【一括削除】 未認証ユーザー                                               | 異常系 | 認証されていないユーザー                                     | HTTP 401 Unauthorized                                               | `ImageController::bulkDestroy()`   |
| 3-1-25 | 【一括削除】 グループが存在しない                                         | 異常系 | ユーザーにグループが紐づいていない                           | HTTP 422 Unprocessable Entity                                       | `ImageController::bulkDestroy()`   |
| 3-1-26 | 【一括削除】 ImageService 例外（データベースエラー）                      | 異常系 | ImageService でデータベースエラーが発生                      | HTTP 500 Internal Server Error                                      | `ImageController::bulkDestroy()`   |
| 3-1-27 | 【一括削除】 ImageService 例外                                            | 異常系 | ImageService で例外が発生                                    | HTTP 500 Internal Server Error                                      | `ImageController::bulkDestroy()`   |
| 3-1-28 | 【一括削除】 ファイル削除失敗                                             | 異常系 | ファイルシステムからの削除が失敗                             | HTTP 500 Internal Server Error                                      | `ImageController::bulkDestroy()`   |
| 3-1-29 | 【一括削除】 バリデーションエラー（削除 ID 配列バリデーション）           | 異常系 | 無効な ID 配列を提供                                         | HTTP 422 Validation Error                                           | `ImageBulkDestroyRequest::rules()` |
| 3-1-30 | 【一括削除】 バリデーションエラー（削除 ID 最小数制限）                   | 異常系 | 削除 ID が提供されていない                                   | HTTP 422 Validation Error                                           | `ImageBulkDestroyRequest::rules()` |
| 3-1-31 | 【一括削除】 バリデーションエラー（削除 ID UUID 形式バリデーション）      | 異常系 | 無効な UUID 形式の ID を提供                                 | HTTP 422 Validation Error                                           | `ImageBulkDestroyRequest::rules()` |
| 3-1-32 | 【一括削除】 バリデーションエラー（related_id 必須項目）                  | 異常系 | related_id が提供されていない                                | HTTP 422 Validation Error                                           | `ImageBulkDestroyRequest::rules()` |
| 3-1-33 | 【一括削除】 バリデーションエラー（related_id UUID 形式）                 | 異常系 | 無効な UUID 形式の related_id を提供                         | HTTP 422 Validation Error                                           | `ImageBulkDestroyRequest::rules()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
