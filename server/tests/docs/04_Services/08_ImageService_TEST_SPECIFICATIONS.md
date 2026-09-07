# ImageService テストケース詳細仕様

## 概要

`ImageService` の単体テスト。画像のアップロード・保存、相対パス `src` に対するスコープ検証・削除・一括削除を検証する。

## テストケース一覧表

| ID    | テスト名                                                                 | 種別   | 入力条件                                           | 期待される出力                                           | 該当メソッド                          |
| ----- | ------------------------------------------------------------------------ | ------ | -------------------------------------------------- | -------------------------------------------------------- | ------------------------------------- |
| 4-8-1 | 【画像アップロード】 クライアント拡張子に関わらず getimagesize 由来の拡張子で保存する | 正常系 | JPEG 内容だがファイル名が `.php` の UploadedFile   | 保存パスが `.jpg` となり `.php` を含まない `Image` が返る | `ImageService::uploadAndSaveImage()`  |
| 4-8-2 | 【画像アップロード】 正常な JPEG を保存できる                              | 正常系 | 正常な JPEG の UploadedFile                        | 寸法・パスが正しい `Image` が返る                         | `ImageService::uploadAndSaveImage()`  |
| 4-8-3 | 【リモート画像取得】 Google CDN URL から正常に画像を保存できる              | 正常系 | 許可済み Google CDN の HTTPS URL と PNG レスポンス   | `Image` が返り storage に保存される                       | `ImageService::downloadAndSaveImage()` |
| 4-8-4 | 【リモート画像取得】 許可されていないホストの URL は拒否する                  | 異常系 | 非 Google CDN の HTTPS URL                           | null が返り HTTP リクエストは送信されない                  | `ImageService::downloadAndSaveImage()` |
| 4-8-5 | 【リモート画像取得】 HTTP スキームの Google CDN URL は拒否する              | 異常系 | Google CDN だが HTTP スキームの URL                  | null が返り HTTP リクエストは送信されない                  | `ImageService::downloadAndSaveImage()` |
| 4-8-6 | 【画像取得】 相対パス src の画像をグループスコープで検証できる               | 正常系 | `images/groups/{group_id}/` 形式の相対パス src      | 検証済みコレクションに画像が含まれる                       | `ImageService::findImagesByIds()`     |
| 4-8-7 | 【画像取得】 相対パス src の画像をユーザースコープで検証できる               | 正常系 | `images/users/{user_id}/` 形式の相対パス src        | 検証済みコレクションに画像が含まれる                       | `ImageService::findImagesByIds()`     |
| 4-8-8 | 【画像取得】 相対パス src が他グループの場合は Not Found                    | 異常系 | 他グループ配下の相対パス src                         | `HttpException`（404）が送出される                        | `ImageService::findImagesByIds()`     |
| 4-8-9 | 【画像削除】 相対パス src の画像 mapping を解除できる                        | 正常系 | グループ配下の相対パス src と image_mappings 行      | mapping が解除され images レコードは残る                   | `ImageService::deleteImages()`        |
| 4-8-10 | 【画像削除】 相対パス src が他グループの場合は mapping を解除しない           | 正常系 | 他グループ配下の相対パス src                         | 解除件数 0、mapping は残る                                 | `ImageService::deleteImages()`        |
| 4-8-11 | 【グループ画像一括削除】 相対パス src の images レコードとディレクトリを削除する | 正常系 | グループ配下の相対パス src と storage ファイル       | images レコードとディレクトリが削除される                   | `ImageService::deleteImagesByGroup()` |
| 4-8-12 | 【ユーザー画像一括削除】 相対パス src の images レコードとディレクトリを削除する | 正常系 | ユーザー配下の相対パス src と storage ファイル       | images レコードとディレクトリが削除される                   | `ImageService::deleteImagesByUser()`  |
| 4-8-13 | 【画像URL生成】 public ディスクでは公開 URL を返す                          | 正常系 | 相対パス src の Image レコード                      | `formatImage()` が public ディスクの `url()` を返す          | `ImageService::formatImage()`         |
| 4-8-14 | 【画像URL生成】 s3 ディスクでは署名付き URL を返す                           | 正常系 | 相対パス src の Image レコード、`IMAGE_DISK=s3`     | `formatImage()` が署名付き URL（`?expiration=` 付き）を返す | `ImageService::formatImage()`         |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/ImageServiceTest.php --stop-on-failure
```
