# ImageService テストケース詳細仕様

## 概要

画像アップロード・保存を担う `ImageService` の単体テスト。`uploadAndSaveImage()` がクライアント提供の拡張子ではなく `getimagesize()` 由来の拡張子で保存することを検証する。

## テストケース一覧表

| ID    | テスト名                                                                 | 種別   | 入力条件                                           | 期待される出力                                           | 該当メソッド                          |
| ----- | ------------------------------------------------------------------------ | ------ | -------------------------------------------------- | -------------------------------------------------------- | ------------------------------------- |
| 4-8-1 | 【画像アップロード】 クライアント拡張子に関わらず getimagesize 由来の拡張子で保存する | 正常系 | JPEG 内容だがファイル名が `.php` の UploadedFile   | 保存パスが `.jpg` となり `.php` を含まない `Image` が返る | `ImageService::uploadAndSaveImage()`  |
| 4-8-2 | 【画像アップロード】 正常な JPEG を保存できる                              | 正常系 | 正常な JPEG の UploadedFile                        | 寸法・パスが正しい `Image` が返る                         | `ImageService::uploadAndSaveImage()`  |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/ImageServiceTest.php --stop-on-failure
```
