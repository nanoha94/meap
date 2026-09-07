# ParsedRecipe テストケース詳細仕様

## 概要

OpenAI 構造化レスポンスを `ParsedRecipe` に変換する `ParsedRecipe::fromArray()` の件数・文字列長検証をテストする。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 7-1-1 | 【fromArray】 正常な OpenAI レスポンスを ParsedRecipe に変換できる | 正常系 | 上限内の name / ingredients / steps | ParsedRecipe インスタンス | `ParsedRecipe::fromArray()` |
| 7-1-2 | 【fromArray】 ingredients が件数上限超過のとき InvalidArgumentException | 異常系 | ingredients が RECIPE_INGREDIENTS_MAX + 1 件 | InvalidArgumentException（材料は100個以下で指定してください。） | `ParsedRecipe::fromArray()` |
| 7-1-3 | 【fromArray】 steps が件数上限超過のとき InvalidArgumentException | 異常系 | steps が RECIPE_STEPS_MAX + 1 件 | InvalidArgumentException（手順は100個以下で指定してください。） | `ParsedRecipe::fromArray()` |
| 7-1-4 | 【fromArray】 name が文字列上限超過のとき InvalidArgumentException | 異常系 | name が STRING_MAX + 1 文字 | InvalidArgumentException（レシピ名は255文字以内で指定してください。） | `ParsedRecipe::fromArray()` |
| 7-1-5 | 【fromArray】 ingredients.*.name が文字列上限超過のとき InvalidArgumentException | 異常系 | ingredients.0.name が STRING_MAX + 1 文字 | InvalidArgumentException（材料名は255文字以内で指定してください。） | `ParsedRecipe::fromArray()` |
| 7-1-6 | 【fromArray】 steps.*.instruction が文字列上限超過のとき InvalidArgumentException | 異常系 | steps.0.instruction が STRING_MAX + 1 文字 | InvalidArgumentException（調理手順は255文字以内で指定してください。） | `ParsedRecipe::fromArray()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Unit/Data/ParsedRecipeTest.php --stop-on-failure
```
