# OpenAiRecipeParser テストケース詳細仕様

## 概要

OpenAI によるレシピ構造化（Phase2）の `OpenAiRecipeParser` 単体テスト。OpenAI API は `OpenAI::fake()` でモックし、OCR は `RecipeOcrInterface` をモックする。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 4-6-1 | 【parseImage】 OpenAI 構造化リクエストに config の max_tokens を付与する | 正常系 | structure_max_tokens=8192、有効な JSON レスポンス | Chat API に max_tokens=8192 を指定して呼び出す | `OpenAiRecipeParser::parseImage()` |
| 4-6-2 | 【parseImage】 ParsedRecipe 検証失敗時は 502 を投げる | 異常系 | ingredients が RECIPE_INGREDIENTS_MAX + 1 件の JSON | HttpException 502（server_error メッセージ） | `OpenAiRecipeParser::parseImage()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Unit/Services/Ai/OpenAiRecipeParserTest.php --stop-on-failure
```
