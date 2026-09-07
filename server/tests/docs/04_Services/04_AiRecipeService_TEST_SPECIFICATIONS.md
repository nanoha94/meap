# AiRecipeService テストケース詳細仕様

## 概要

AI レシピ画像解析結果（`ParsedRecipe`）を、グループの単位マスタ（`ingredient_units`）に基づいて正規化する `AiRecipeService` の単体テスト。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 4-4-1 | 【normalizeParsedRecipe】 quantity のみの材料は DB 単位に基づき display を補完する | 正常系 | quantity=1、quantityDisplay=null、unitName=個（requires_quantity=true） | quantity=1、quantityDisplay="1" | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-2 | 【normalizeParsedRecipe】 requires_quantity=false の DB 単位は両方 null | 正常系 | quantity=1、quantityDisplay="1"、unitName=適量（requires_quantity=false） | quantity=null、quantityDisplay=null | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-3 | 【normalizeParsedRecipe】 グループ独自の requires_quantity=false 単位を反映する | 正常系 | グループに requires_quantity=false の独自単位「たっぷり」を追加 | quantity=null、quantityDisplay=null | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-4 | 【normalizeParsedRecipe】 DB に存在しない unitName は requires_quantity=true として正規化する | 正常系 | quantity=200、quantityDisplay=null、unitName=グラム（DB 未登録） | quantity=200、quantityDisplay="200"、unitName は入力のまま | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-5 | 【normalizeParsedRecipe】 帯分数 display のスペース区切りを保持する | 正常系 | quantity=1.5、quantityDisplay="1 1/2"、unitName=個 | quantity=1.5、quantityDisplay="1 1/2" | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-6 | 【normalizeParsedRecipe】 帯分数 display の「と」区切りを保持する | 正常系 | quantity=1.5、quantityDisplay="1と1/2"、unitName=大さじ | quantity=1.5、quantityDisplay="1と1/2" | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-7 | 【normalizeParsedRecipe】 quantity と display が矛盾する場合は display を優先する | 正常系 | quantity=1、quantityDisplay="1/2"、unitName=大さじ | quantity=0.5、quantityDisplay="1/2" | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-8 | 【normalizeParsedRecipe】 quantityDisplay に混入した prefix 単位名を除去する | 正常系 | quantity=1、quantityDisplay="大さじ1"、unitName=大さじ | quantity=1、quantityDisplay="1" | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-9 | 【normalizeParsedRecipe】 quantityDisplay に混入した suffix 単位名を除去する | 正常系 | quantity=1、quantityDisplay="1個"、unitName=個 | quantity=1、quantityDisplay="1" | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-10 | 【normalizeParsedRecipe】 全角 quantityDisplay を半角に正規化する | 正常系 | quantity=1.5、quantityDisplay="１と１／２"、unitName=大さじ | quantity=1.5、quantityDisplay="1と1/2" | `AiRecipeService::normalizeParsedRecipe()` |
| 4-4-11 | 【normalizeParsedRecipe】 帯分数 display の分数部分だけ quantity に入っている場合は display を優先する | 正常系 | サラダ油 quantity=0.5 display="1/2"、しょうゆ quantity=0.5 display="1と1/2"、いずれも unitName=大さじ | サラダ油は 0.5/"1/2"、しょうゆは 1.5/"1と1/2" | `AiRecipeService::normalizeParsedRecipe()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Unit/Services/Ai/AiRecipeServiceTest.php --stop-on-failure
```
