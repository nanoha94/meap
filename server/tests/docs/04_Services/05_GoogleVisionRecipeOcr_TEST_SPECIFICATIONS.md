# GoogleVisionRecipeOcr テストケース詳細仕様

## 概要

Google Cloud Vision API を用いてレシピ画像から OCR テキストを抽出する `GoogleVisionRecipeOcr` の単体テスト。HTTP レスポンスは `Http::fake()` でモックする。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 4-5-1 | 【extract】 API レスポンスから OCR テキストを返す | 正常系 | API キー設定済み、Vision API が fullTextAnnotation.text を返す | 抽出テキスト文字列 | `GoogleVisionRecipeOcr::extract()` |
| 4-5-2 | 【extract】 DOCUMENT_TEXT_DETECTION と languageHints を指定して Vision API を呼び出す | 正常系 | base64 画像と mimeType | 正しい URL・リクエストボディで POST | `GoogleVisionRecipeOcr::extract()` |
| 4-5-3 | 【extract】 API キー未設定時は 502 を投げる | 異常系 | api_key が空 | HttpException 502（server_error メッセージ） | `GoogleVisionRecipeOcr::extract()` |
| 4-5-4 | 【extract】 HTTP 通信失敗時は 502 を投げる | 異常系 | Http クライアントが ConnectionException を投げる | HttpException 502（server_error メッセージ） | `GoogleVisionRecipeOcr::extract()` |
| 4-5-5 | 【extract】 Vision API が HTTP エラーを返した場合は 502 を投げる | 異常系 | Vision API が HTTP 500 を返す | HttpException 502（server_error メッセージ） | `GoogleVisionRecipeOcr::extract()` |
| 4-5-6 | 【extract】 レスポンスのトップレベル error.message がある場合は 502 を投げる | 異常系 | レスポンス body に error.message | HttpException 502（server_error メッセージ） | `GoogleVisionRecipeOcr::extract()` |
| 4-5-7 | 【extract】 responses.0.error.message がある場合は 502 を投げる | 異常系 | レスポンス body に responses.0.error.message | HttpException 502（server_error メッセージ） | `GoogleVisionRecipeOcr::extract()` |
| 4-5-8 | 【extract】 fullTextAnnotation.text が空の場合は 502 を投げる | 異常系 | fullTextAnnotation.text が空文字 | HttpException 502（server_error メッセージ） | `GoogleVisionRecipeOcr::extract()` |
| 4-5-9 | 【extract】 fullTextAnnotation.text が存在しない場合は 502 を投げる | 異常系 | responses.0 に fullTextAnnotation なし | HttpException 502（server_error メッセージ） | `GoogleVisionRecipeOcr::extract()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Unit/Services/Ai/GoogleVisionRecipeOcrTest.php --stop-on-failure
```
