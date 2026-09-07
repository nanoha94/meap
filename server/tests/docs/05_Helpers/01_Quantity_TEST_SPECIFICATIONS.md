# Quantity テストケース詳細仕様

## 概要

材料数量のパース・正規化・表示を担う `Quantity` ヘルパーの単体テスト。分数・帯分数・小数・整数の相互変換、`requiresQuantity` に応じた正規化、手入力用（display バリデーションあり）と AI 用（バリデーションなし）の正規化を検証する。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 5-1-1 | 【parseQuantityDisplayToNumber】 分数をパースする | 正常系 | `"1/2"`, `"2/3"`, `"3/4"` | 0.5, round(2/3, 3), 0.75 | `Quantity::parseQuantityDisplayToNumber()` |
| 5-1-2 | 【parseQuantityDisplayToNumber】 帯分数をパースする | 正常系 | `"1 1/2"`, `"1と1/2"`, `"2 1/4"`, `"2と1/4"` | 1.5, 1.5, 2.25, 2.25 | `Quantity::parseQuantityDisplayToNumber()` |
| 5-1-3 | 【parseQuantityDisplayToNumber】 小数をパースする | 正常系 | `"1.5"`, `".5"` | 1.5, 0.5 | `Quantity::parseQuantityDisplayToNumber()` |
| 5-1-4 | 【parseQuantityDisplayToNumber】 整数をパースする | 正常系 | `"2"`, `"200"` | 2.0, 200.0 | `Quantity::parseQuantityDisplayToNumber()` |
| 5-1-5 | 【parseQuantityDisplayToNumber】 前後の空白をトリムする | 正常系 | `"  1/2  "` | 0.5 | `Quantity::parseQuantityDisplayToNumber()` |
| 5-1-6 | 【parseQuantityDisplayToNumber】 空欄・不正値・負数・ゼロ除算は null | 異常系 | `""`, `"   "`, `"-1"`, `"abc"`, `"1/0"`, `"1 1/0"`, `"1と1/0"` | いずれも null | `Quantity::parseQuantityDisplayToNumber()` |
| 5-1-7 | 【formatQuantityDisplay】 既知分数は分数表記にする | 正常系 | 0.5, 1.5, 2.0 | `"1/2"`, `"1と1/2"`, `"2"` | `Quantity::formatQuantityDisplay()` |
| 5-1-8 | 【formatQuantityDisplay】 10 以上は小数表記にフォールバックする | 正常系 | 200.0 | `"200"` | `Quantity::formatQuantityDisplay()` |
| 5-1-9 | 【formatQuantityDisplay】 null は空文字 | 正常系 | null | `""` | `Quantity::formatQuantityDisplay()` |
| 5-1-10 | 【normalizeQuantityDisplay】 表記種別に応じて正規化する | 正常系 | `("1/2", 0.5)`, `("1.50", 1.5)`, `("2", 2.0)`, `("1 1/2", 1.5)`, `("1と1/2", 1.5)` | `"1/2"`, `"1.5"`, `"2"`, `"1 1/2"`, `"1と1/2"` | `Quantity::normalizeQuantityDisplay()` |
| 5-1-11 | 【normalizeQuantityDisplay】 空欄は null | 異常系 | `("", 1.0)` | null | `Quantity::normalizeQuantityDisplay()` |
| 5-1-12 | 【normalizeQuantityFromDisplay】 requiresQuantity=false のとき両方 null | 正常系 | `("1/2", false)`, `(null, false)` | quantity=null, quantityDisplay=null | `Quantity::normalizeQuantityFromDisplay()` |
| 5-1-13 | 【normalizeQuantityFromDisplay】 display から quantity と display を導出する | 正常系 | `("1/2", true)`, `("200", true)` | (0.5, `"1/2"`), (200.0, `"200"`) | `Quantity::normalizeQuantityFromDisplay()` |
| 5-1-14 | 【normalizeQuantityFromDisplay】 不正 display は ValidationException | 異常系 | `("abc", true)` | ValidationException | `Quantity::normalizeQuantityFromDisplay()` |
| 5-1-15 | 【normalizeQuantityFromDisplay】 display 未指定は ValidationException | 異常系 | `(null, true)` | ValidationException | `Quantity::normalizeQuantityFromDisplay()` |
| 5-1-16 | 【normalizeQuantityFromDisplay】 カスタム errorKey を ValidationException に含める | 異常系 | `("abc", true, "ingredients.0.quantityDisplay")` | errors に `ingredients.0.quantityDisplay` キー、`invalid_quantity_display` メッセージ | `Quantity::normalizeQuantityFromDisplay()` |
| 5-1-17 | 【normalizeQuantityPair】 requiresQuantity=false のとき両方 null | 正常系 | `(1.0, "1", false)` | quantity=null, quantityDisplay=null | `Quantity::normalizeQuantityPair()` |
| 5-1-18 | 【normalizeQuantityPair】 display から quantity と display を導出する | 正常系 | `(null, "1/2", true)` | quantity=0.5, quantityDisplay=`"1/2"` | `Quantity::normalizeQuantityPair()` |
| 5-1-19 | 【normalizeQuantityPair】 quantity のみのとき display を補完する | 正常系 | `(1.0, null, true)` | quantity=1.0, quantityDisplay=`"1"` | `Quantity::normalizeQuantityPair()` |
| 5-1-20 | 【normalizeQuantityPair】 不正 display は quantity から補完する | 正常系 | `(0.5, "abc", true)` | quantity=0.5, quantityDisplay=`"1/2"` | `Quantity::normalizeQuantityPair()` |
| 5-1-21 | 【normalizeQuantityPair】 数量なしは両方 null | 正常系 | `(null, null, true)` | quantity=null, quantityDisplay=null | `Quantity::normalizeQuantityPair()` |
| 5-1-22 | 【normalizeQuantityPair】 quantity と display が矛盾する場合は display を優先する | 正常系 | `(1.0, "1/2", true)`, `(1.5, "2 1/2", true)`, `(0.5, "1と1/2", true)` | (0.5, `"1/2"`), (2.5, `"2 1/2"`), (1.5, `"1と1/2"`) | `Quantity::normalizeQuantityPair()` |
| 5-1-23 | 【normalizeQuantityPair】 quantity と display が一致する場合は display から導出する | 正常系 | `(0.5, "1/2", true)` | quantity=0.5, quantityDisplay=`"1/2"` | `Quantity::normalizeQuantityPair()` |
| 5-1-24 | 【stripUnitFromDisplay】 単位マスタの position に応じて単位名を除去する | 正常系 | prefix: `("大さじ1", "大さじ", "prefix")` ほか。suffix: `("1個", "個", "suffix")`, `("200g", "g", "suffix")` ほか。`unitPosition` が `null` のときは除去しない | prefix は数値部分のみ、suffix は数値部分のみ | `Quantity::stripUnitFromDisplay()` |
| 5-1-25 | 【parseQuantityDisplayToNumber】 全角スペース区切りの帯分数をパースする | 正常系 | `"1\u30001/2"`（U+3000 全角スペース） | 1.5 | `Quantity::parseQuantityDisplayToNumber()` |
| 5-1-26 | 【parseQuantityDisplayToNumber】 全角数字・全角スラッシュの分数をパースする | 正常系 | `"１/２"` | 0.5 | `Quantity::parseQuantityDisplayToNumber()` |
| 5-1-27 | 【parseQuantityDisplayToNumber】 全角表記の帯分数をパースする | 正常系 | `"１と１／２"` | 1.5 | `Quantity::parseQuantityDisplayToNumber()` |
| 5-1-28 | 【normalizeQuantityDisplay】 全角スペース区切りの帯分数を半角に正規化する | 正常系 | `("1\u30001/2", 1.5)` | `"1 1/2"` | `Quantity::normalizeQuantityDisplay()` |
| 5-1-29 | 【normalizeQuantityDisplay】 全角表記の帯分数を半角に正規化する | 正常系 | `("１と１／２", 1.5)` | `"1と1/2"` | `Quantity::normalizeQuantityDisplay()` |
| 5-1-30 | 【normalizeQuantityPair】 全角表記の display を半角に正規化する | 正常系 | `(1.5, "１\u3000１/２", true)` | quantity=1.5, quantityDisplay=`"1 1/2"` | `Quantity::normalizeQuantityPair()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/05_run_helpers_tests.sh
```

個別ファイルのみ実行する場合:

```bash
cd server
./vendor/bin/sail test tests/Unit/Helpers/QuantityTest.php --stop-on-failure
```
