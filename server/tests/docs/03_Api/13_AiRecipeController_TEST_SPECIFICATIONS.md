# AiRecipeController テストケース詳細仕様

## 概要

AI レシピ画像解析 API（`POST /ai/recipes/parse`）のテスト。AI 利用回数トラッキング・利用制限チェックを含む。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 3-13-1 | 【AIレシピ画像解析】 正常に画像を解析できる | 正常系 | 有効な画像、利用回数に余裕あり | HTTP 200、解析結果 JSON、利用回数が 1 増える | `AiRecipeController::parse()` |
| 3-13-2 | 【AIレシピ画像解析】 未認証 | 異常系 | 認証なし | HTTP 401 | `AiRecipeController::parse()` |
| 3-13-3 | 【AIレシピ画像解析】 バリデーションエラー（image 未指定） | 異常系 | image を送信しない | HTTP 422、image のバリデーションエラー | `AiRecipeController::parse()` |
| 3-13-4 | 【AIレシピ画像解析】 バリデーションエラー（image が画像ファイルでない） | 異常系 | PDF ファイルを送信 | HTTP 422、image のバリデーションエラー | `AiRecipeController::parse()` |
| 3-13-5 | 【AIレシピ画像解析】 バリデーションエラー（image の MIME 形式不正） | 異常系 | 許可外形式（GIF）の画像を送信 | HTTP 422、image のバリデーションエラー | `AiRecipeController::parse()` |
| 3-13-6 | 【AIレシピ画像解析】 バリデーションエラー（image のファイルサイズ超過） | 異常系 | 10MB を超えるファイルを送信 | HTTP 422、image のバリデーションエラー | `AiRecipeController::parse()` |
| 3-13-7 | 【AIレシピ画像解析】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `AiRecipeController::parse()` |
| 3-13-8 | 【AIレシピ画像解析】 月次利用上限超過 | 異常系 | ai_monthly_remaining=0 かつ ai_pack_remaining=0 | HTTP 429、月次上限メッセージ | `AiRecipeController::parse()` |
| 3-13-9 | 【AIレシピ画像解析】 AI 解析失敗時に利用回数が返却される | 異常系 | パーサーが例外を投げる | HTTP 502、利用回数が消費前に戻る | `AiRecipeController::parse()` |
| 3-13-10 | 【AIレシピ画像解析】 短時間の連続リクエストでレート制限 | 異常系 | 1分あたりの上限を超えて連続リクエスト | HTTP 429、一時停止メッセージ | `AiRecipeController::parse()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/AiRecipeControllerTest.php --stop-on-failure
```
