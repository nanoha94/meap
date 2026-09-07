# AiRecipeController テストケース詳細仕様

## 概要

AI レシピ解析 API（`POST /ai/recipes/parse-img`、`POST /ai/recipes/parse-url`）のテスト。AI 利用回数トラッキング・利用制限チェックを含む。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 3-13-1 | 【AIレシピ画像解析】 正常に画像を解析できる | 正常系 | 有効な画像、利用回数に余裕あり | HTTP 200、解析結果 JSON（quantity のみの材料は quantityDisplay が補完される）、利用回数が 1 増える | `AiRecipeController::parseImage()` |
| 3-13-2 | 【AIレシピ画像解析】 quantityDisplay がレスポンスに含まれる | 正常系 | パーサーが quantityDisplay 付きの材料を返す（`1/2`、`1と1/2` など） | HTTP 200、ingredients[].quantityDisplay が返る | `AiRecipeController::parseImage()` |
| 3-13-3 | 【AIレシピ画像解析】 適量の材料は quantity / quantityDisplay が両方 null | 正常系 | パーサーが適量単位の材料を返す | HTTP 200、DB 単位マスタの requires_quantity に基づき ingredients[].quantity と quantityDisplay が null | `AiRecipeController::parseImage()` |
| 3-13-4 | 【AIレシピ画像解析】 quantity のみの分数は quantityDisplay が補完される | 正常系 | パーサーが quantity=0.5、quantityDisplay=null の材料を返す | HTTP 200、ingredients[].quantity=0.5、quantityDisplay="1/2" | `AiRecipeController::parseImage()` |
| 3-13-5 | 【AIレシピ画像解析】 quantity と display が矛盾する場合は display を優先する | 正常系 | パーサーが quantity=1、quantityDisplay="1/2"、unitName=大さじ の材料を返す | HTTP 200、ingredients[].quantity=0.5、quantityDisplay="1/2" | `AiRecipeController::parseImage()` |
| 3-13-6 | 【AIレシピ画像解析】 未認証 | 異常系 | 認証なし | HTTP 401 | `AiRecipeController::parseImage()` |
| 3-13-7 | 【AIレシピ画像解析】 バリデーションエラー（image 未指定） | 異常系 | image を送信しない | HTTP 422、image のバリデーションエラー | `AiRecipeController::parseImage()` |
| 3-13-8 | 【AIレシピ画像解析】 バリデーションエラー（image が画像ファイルでない） | 異常系 | PDF ファイルを送信 | HTTP 422、image のバリデーションエラー | `AiRecipeController::parseImage()` |
| 3-13-9 | 【AIレシピ画像解析】 バリデーションエラー（image の MIME 形式不正） | 異常系 | 許可外形式（GIF）の画像を送信 | HTTP 422、image のバリデーションエラー | `AiRecipeController::parseImage()` |
| 3-13-10 | 【AIレシピ画像解析】 バリデーションエラー（image のファイルサイズ超過） | 異常系 | 10MB を超えるファイルを送信 | HTTP 422、image のバリデーションエラー | `AiRecipeController::parseImage()` |
| 3-13-11 | 【AIレシピ画像解析】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `AiRecipeController::parseImage()` |
| 3-13-12 | 【AIレシピ画像解析】 月次利用上限超過 | 異常系 | ai_monthly_remaining=0 かつ ai_pack_remaining=0 | HTTP 429、月次上限メッセージ | `AiRecipeController::parseImage()` |
| 3-13-13 | 【AIレシピ画像解析】 AI 解析失敗時に利用回数が返却される | 異常系 | パーサーが例外を投げる | HTTP 502、利用回数が消費前に戻る | `AiRecipeController::parseImage()` |
| 3-13-14 | 【AIレシピ画像解析】 短時間の連続リクエストでレート制限 | 異常系 | 1分あたりの上限を超えて連続リクエスト | HTTP 429、一時停止メッセージ | `AiRecipeController::parseImage()` |
| 3-13-15 | 【AIレシピURL解析】 正常にURLからレシピを解析できる | 正常系 | 有効な URL、利用回数に余裕あり | HTTP 200、解析結果 JSON、利用回数が 1 減る | `AiRecipeController::parseUrl()` |
| 3-13-16 | 【AIレシピURL解析】 未認証 | 異常系 | 認証なし | HTTP 401 | `AiRecipeController::parseUrl()` |
| 3-13-17 | 【AIレシピURL解析】 バリデーションエラー（url 未指定） | 異常系 | url を送信しない | HTTP 422、url のバリデーションエラー | `AiRecipeController::parseUrl()` |
| 3-13-18 | 【AIレシピURL解析】 バリデーションエラー（url が URL 形式でない） | 異常系 | 不正な URL 形式を送信 | HTTP 422、url のバリデーションエラー | `AiRecipeController::parseUrl()` |
| 3-13-19 | 【AIレシピURL解析】 バリデーションエラー（url が 2048 文字を超える） | 異常系 | 2048 文字を超える URL を送信 | HTTP 422、url のバリデーションエラー | `AiRecipeController::parseUrl()` |
| 3-13-20 | 【AIレシピURL解析】 バリデーションエラー（url が http スキーム） | 異常系 | http スキームの URL を送信 | HTTP 422、url のバリデーションエラー | `AiRecipeController::parseUrl()` |
| 3-13-21 | 【AIレシピURL解析】 バリデーションエラー（url が localhost） | 異常系 | `https://localhost/...` を送信 | HTTP 422、url のバリデーションエラー | `AiRecipeController::parseUrl()` |
| 3-13-22 | 【AIレシピURL解析】 バリデーションエラー（url がメタデータ IP） | 異常系 | `https://169.254.169.254/...` を送信 | HTTP 422、url のバリデーションエラー | `AiRecipeController::parseUrl()` |
| 3-13-23 | 【AIレシピURL解析】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `AiRecipeController::parseUrl()` |
| 3-13-24 | 【AIレシピURL解析】 月次利用上限超過 | 異常系 | ai_monthly_remaining=0 かつ ai_pack_remaining=0 | HTTP 429、月次上限メッセージ | `AiRecipeController::parseUrl()` |
| 3-13-25 | 【AIレシピURL解析】 AI 解析失敗時に利用回数が返却される | 異常系 | パーサーが例外を投げる | HTTP 502、利用回数が消費前に戻る | `AiRecipeController::parseUrl()` |
| 3-13-26 | 【AIレシピURL解析】 短時間の連続リクエストでレート制限 | 異常系 | 1分あたりの上限を超えて連続リクエスト | HTTP 429、一時停止メッセージ | `AiRecipeController::parseUrl()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/AiRecipeControllerTest.php --stop-on-failure
```
