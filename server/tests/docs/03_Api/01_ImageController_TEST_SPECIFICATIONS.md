# ImageController テストケース詳細仕様

## 概要

ImageController のテストケースの詳細仕様を示します。グループ画像の一括アップロード（`POST /images/groups/upload-bulk`）とユーザー画像のアップロード（`POST /images/users/upload`）を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                                                  | 種別   | 入力条件                                                                                     | 期待される出力                                                      | 該当メソッド                              |
| ------ | ------------------------------------------------------------------------- | ------ | -------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- | ----------------------------------------- |
| 3-1-1  | 【一括アップロード】 正常な画像アップロード（1 枚）                       | 正常系 | 有効な画像ファイル 1 枚を提供                                                                | HTTP 200 JSON success                                               | `ImageController::bulkUploadForGroup()`   |
| 3-1-2  | 【一括アップロード】 複数画像の一括アップロード                           | 正常系 | 複数の有効な画像ファイルを提供                                                               | 全ての画像が正常にアップロードされる                                | `ImageController::bulkUploadForGroup()`   |
| 3-1-3  | 【一括アップロード】 グループ ID 配下に直接保存されることを確認           | 正常系 | 画像をアップロード                                                                           | グループ ID 配下に直接保存される（ディレクトリ分けなし）            | `ImageController::bulkUploadForGroup()`   |
| 3-1-4  | 【一括アップロード】 upload_path を送っても無視され groups 配下に保存される | 正常系 | 旧クライアント互換で `upload_path=users` を付与して画像1枚                                      | HTTP 200 JSON success、`images/groups/{group_id}/` に保存           | `ImageController::bulkUploadForGroup()`   |
| 3-1-5  | 【一括アップロード】 upload_path 未指定時にグループ ID 配下に保存される   | 正常系 | `upload_path` なしで画像1枚                                                                  | src に `images/groups/{group_id}/` が含まれる（従来動作）           | `ImageController::bulkUploadForGroup()`   |
| 3-1-6  | 【一括アップロード】 アップロードした画像から Exif が削除される           | 正常系 | Exif（撮影日時等）を埋めた JPEG を送信                                                       | 保存先ファイルを `exif_read_data` で読み取っても Exif が取得できない | `ImageController::bulkUploadForGroup()`   |
| 3-1-7  | 【一括アップロード】 長辺 2000px を超える画像は 2000px に縮小される       | 正常系 | 3000x1500 の画像を送信                                                                       | 保存後の Image の width=2000 height=1000                           | `ImageController::bulkUploadForGroup()`   |
| 3-1-8  | 【一括アップロード】 長辺 2000px 以下の画像はそのままのサイズで保存される | 正常系 | 1000x500 の画像を送信                                                                        | 保存後の Image の width=1000 height=500                             | `ImageController::bulkUploadForGroup()`   |
| 3-1-9  | 【一括アップロード】 PNG / WebP もリサイズと再保存ができる                | 正常系 | 3000x1500 の PNG または WebP を送信                                                          | 保存後 width=2000 height=1000、元フォーマットの MIME が維持される   | `ImageController::bulkUploadForGroup()`   |
| 3-1-10 | 【一括アップロード】 未認証ユーザー                                       | 異常系 | 認証されていないユーザー                                                                     | HTTP 401 Unauthorized                                               | `ImageController::bulkUploadForGroup()`   |
| 3-1-11 | 【一括アップロード】 バリデーションエラー（ファイル配列バリデーション）   | 異常系 | ファイル配列以外の形式を提供                                                                 | HTTP 422 Validation Error                                           | `ImageController::bulkUploadForGroup()`   |
| 3-1-12 | 【一括アップロード】 バリデーションエラー（最小ファイル数制限）           | 異常系 | ファイルが提供されていない                                                                   | HTTP 422 Validation Error                                           | `ImageController::bulkUploadForGroup()`   |
| 3-1-13 | 【一括アップロード】 バリデーションエラー（最大ファイル数制限）           | 異常系 | 20 個を超えるファイルを提供                                                                  | HTTP 422 Validation Error                                           | `ImageController::bulkUploadForGroup()`   |
| 3-1-14 | 【一括アップロード】 バリデーションエラー（ファイルサイズ制限）           | 異常系 | 制限を超えるファイルサイズを提供                                                             | HTTP 422 Validation Error                                           | `ImageController::bulkUploadForGroup()`   |
| 3-1-17 | 【一括アップロード】 グループが存在しない                                 | 異常系 | ユーザーにグループが紐づいていない                                                           | HTTP 422 Unprocessable Entity                                       | `ImageController::bulkUploadForGroup()`   |
| 3-1-18 | 【一括アップロード】 ImageService 例外（データベースエラー）              | 異常系 | ImageService でデータベースエラーが発生                                                      | HTTP 500 Internal Server Error                                      | `ImageController::bulkUploadForGroup()`   |
| 3-1-19 | 【一括アップロード】 ImageService 例外                                    | 異常系 | ImageService で例外が発生                                                                    | HTTP 500 Internal Server Error                                      | `ImageController::bulkUploadForGroup()`   |
| 3-1-20 | 【一括アップロード】 ファイルアップロード失敗                             | 異常系 | ファイルシステムへの書き込みが失敗                                                           | HTTP 500 Internal Server Error                                      | `ImageController::bulkUploadForGroup()`   |
| 3-1-21 | 【アップロード】 正常な画像アップロード                                   | 正常系 | 有効な画像ファイル 1 枚を提供                                                                | HTTP 200 JSON success、単体 `data` オブジェクト                     | `ImageController::uploadForUser()`        |
| 3-1-22 | 【アップロード】 メール未確認ユーザー                                     | 異常系 | メール未確認の認証済みユーザーが画像 1 枚を提供                                              | HTTP 409、メール未確認メッセージ                                    | `ImageController::uploadForUser()`        |
| 3-1-23 | 【アップロード】 未認証ユーザー                                           | 異常系 | 認証されていないユーザー                                                                     | HTTP 401 Unauthorized                                               | `ImageController::uploadForUser()`        |
| 3-1-24 | 【アップロード】 バリデーションエラー（image 必須）                       | 異常系 | `image` が提供されていない                                                                   | HTTP 422 Validation Error                                           | `ImageController::uploadForUser()`        |
| 3-1-25 | 【アップロード】 バリデーションエラー（ファイル形式不正）                 | 異常系 | 画像以外のファイルを提供                                                                     | HTTP 422 Validation Error                                           | `ImageController::uploadForUser()`        |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/ImageControllerTest.php --stop-on-failure
```
