# ImageController テストケース詳細仕様

## 概要

ImageController のテストケースの詳細仕様を示します。画像の一括アップロードと一括削除機能を検証し、システムの安定性と安全性を確保します。また、ImageService の単体テストも含めて、サービス層の機能も網羅的に検証します。

## テストケース一覧表

| ID     | テスト名                                                                    | 種別   | 入力条件                             | 期待される出力                           | 該当メソッド                       |
| ------ | --------------------------------------------------------------------------- | ------ | ------------------------------------ | ---------------------------------------- | ---------------------------------- |
| 3-1-1  | 【一括アップロード】 正常な画像アップロード（1 枚）                         | 正常系 | 有効な画像ファイル 1 枚を提供        | HTTP 200 JSON success                    | `ImageController::bulkUpload()`    |
| 3-1-2  | 【一括アップロード】 複数画像の一括アップロード                             | 正常系 | 複数の有効な画像ファイルを提供       | 全ての画像が正常にアップロードされる     | `ImageController::bulkUpload()`    |
| 3-1-3  | 【一括アップロード】 ディレクトリ指定アップロード                           | 正常系 | ディレクトリを指定してアップロード   | 指定されたディレクトリに画像が保存される | `ImageController::bulkUpload()`    |
| 3-1-4  | 【一括アップロード】 デフォルトディレクトリアップロード                     | 正常系 | ディレクトリ未指定でアップロード     | 'general' ディレクトリに画像が保存される | `ImageController::bulkUpload()`    |
| 3-1-5  | 【一括アップロード】 未認証ユーザー                                         | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                    | `ImageController::bulkUpload()`    |
| 3-1-6  | 【一括アップロード】 グループが存在しない                                   | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity            | `ImageController::bulkUpload()`    |
| 3-1-7  | 【一括アップロード】 データベース接続エラー                                 | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error           | `ImageController::bulkUpload()`    |
| 3-1-8  | 【一括アップロード】 ImageService 例外                                      | 異常系 | ImageService で例外が発生            | HTTP 500 Internal Server Error           | `ImageController::bulkUpload()`    |
| 3-1-9  | 【一括アップロード】 ファイルアップロード失敗                               | 異常系 | ファイルシステムへの書き込みが失敗   | HTTP 500 Internal Server Error           | `ImageController::bulkUpload()`    |
| 3-1-10 | 【一括アップロード】 バリデーションエラー（ファイルサイズ制限）             | 異常系 | 制限を超えるファイルサイズを提供     | HTTP 422 Validation Error                | `ImageBulkUploadRequest::rules()`  |
| 3-1-11 | 【一括アップロード】 バリデーションエラー（最大ファイル数制限）             | 異常系 | 20 個を超えるファイルを提供          | HTTP 422 Validation Error                | `ImageBulkUploadRequest::rules()`  |
| 3-1-12 | 【一括アップロード】 バリデーションエラー（最小ファイル数制限）             | 異常系 | ファイルが提供されていない           | HTTP 422 Validation Error                | `ImageBulkUploadRequest::rules()`  |
| 3-1-13 | 【一括アップロード】 バリデーションエラー（ファイル配列バリデーション）     | 異常系 | ファイル配列以外の形式を提供         | HTTP 422 Validation Error                | `ImageBulkUploadRequest::rules()`  |
| 3-1-14 | 【一括アップロード】 バリデーションエラー（ディレクトリ文字数制限）         | 異常系 | 255 文字を超えるディレクトリ名を提供 | HTTP 422 Validation Error                | `ImageBulkUploadRequest::rules()`  |
| 3-1-15 | 【一括アップロード】 バリデーションエラー（ディレクトリ形式バリデーション） | 異常系 | 文字列以外のディレクトリを提供       | HTTP 422 Validation Error                | `ImageBulkUploadRequest::rules()`  |
| 3-1-16 | 【一括削除】 正常な画像削除（1 枚）                                         | 正常系 | 有効な画像 ID 1 つを提供             | HTTP 200 JSON success                    | `ImageController::bulkDestroy()`   |
| 3-1-17 | 【一括削除】 複数画像の一括削除                                             | 正常系 | 複数の有効な画像 ID を提供           | 全ての画像が正常に削除される             | `ImageController::bulkDestroy()`   |
| 3-1-18 | 【一括削除】 削除成功メッセージの確認                                       | 正常系 | 正常な画像削除後                     | 削除件数を含む適切なメッセージが返される | `ImageController::bulkDestroy()`   |
| 3-1-19 | 【一括削除】 未認証ユーザー                                                 | 異常系 | 認証されていないユーザー             | HTTP 401 Unauthorized                    | `ImageController::bulkDestroy()`   |
| 3-1-20 | 【一括削除】 グループが存在しない                                           | 異常系 | ユーザーにグループが紐づいていない   | HTTP 422 Unprocessable Entity            | `ImageController::bulkDestroy()`   |
| 3-1-21 | 【一括削除】 データベース接続エラー                                         | 異常系 | データベース接続が失敗               | HTTP 500 Internal Server Error           | `ImageController::bulkDestroy()`   |
| 3-1-22 | 【一括削除】 ImageService 例外                                              | 異常系 | ImageService で例外が発生            | HTTP 500 Internal Server Error           | `ImageController::bulkDestroy()`   |
| 3-1-23 | 【一括削除】 ファイル削除失敗                                               | 異常系 | ファイルシステムからの削除が失敗     | HTTP 500 Internal Server Error           | `ImageController::bulkDestroy()`   |
| 3-1-24 | 【一括削除】 存在しない画像 ID の削除                                       | 正常系 | 存在しない画像 ID を提供             | HTTP 200 削除数 0 件で正常終了           | `ImageController::bulkDestroy()`   |
| 3-1-25 | 【一括削除】 バリデーションエラー（削除 ID 配列バリデーション）             | 異常系 | 無効な ID 配列を提供                 | HTTP 422 Validation Error                | `ImageBulkDestroyRequest::rules()` |
| 3-1-26 | 【一括削除】 バリデーションエラー（削除 ID 最小数制限）                     | 異常系 | 削除 ID が提供されていない           | HTTP 422 Validation Error                | `ImageBulkDestroyRequest::rules()` |
| 3-1-27 | 【一括削除】 バリデーションエラー（削除 ID UUID 形式バリデーション）        | 異常系 | 無効な UUID 形式の ID を提供         | HTTP 422 Validation Error                | `ImageBulkDestroyRequest::rules()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
