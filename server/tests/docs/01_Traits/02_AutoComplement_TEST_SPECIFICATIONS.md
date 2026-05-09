# AutoComplement テストケース詳細仕様

## 目次

-   [概要](#概要)
-   [テストケース一覧表](#テストケース一覧表)
-   [テスト実行方法](#テスト実行方法)

---

## 概要

`AutoComplement`トレイトの動作を検証するための包括的なテストスイートを作成しました。各テストケースは、特定の入力に対して期待される出力を明確に定義し、トレイトの動作を詳細に検証します。

## テストケース一覧表

| ID    | テスト名                                                                 | 種別     | 入力条件                                                  | 期待される出力                               | 該当メソッド                    |
| ----- | ------------------------------------------------------------------------ | -------- | --------------------------------------------------------- | -------------------------------------------- | ------------------------------- |
| 1-2-1 | 【findOrCreateIds】 既存アイテム ID 取得テスト                           | 基本機能 | 既存のアイテム ID を含むアイテムリストとグループ          | - 既存のアイテム ID が返される               | `AutoComplement::findOrCreateIds()` |
| 1-2-2 | 【findOrCreateIds】 新規アイテム作成テスト                               | 基本機能 | 新規のアイテム名を含むアイテムリストとグループ            | - 新規アイテムが作成され、その ID が返される | `AutoComplement::findOrCreateIds()` |
| 1-2-3 | 【findOrCreateIds】 既存アイテム名での新規作成テスト                     | 基本機能 | ID が指定されず、既存の名前を含むアイテムリストとグループ | - 既存のアイテム ID が返される               | `AutoComplement::findOrCreateIds()` |
| 1-2-4 | 【findOrCreateIds】 空のアイテムリストテスト                             | 基本機能 | 空のアイテムリストとグループ                              | - 空の配列が返される                         | `AutoComplement::findOrCreateIds()` |
| 1-2-5 | 【findOrCreateIds】 無効な ID データ型テスト                              | 異常系   | 文字列以外の ID を含むアイテムリストとグループ            | - InvalidArgumentException がスローされる    | `AutoComplement::findOrCreateIds()` |
| 1-2-6 | 【findOrCreateIds】 存在しない ID テスト                                 | 異常系   | 存在しない ID を含むアイテムリストとグループ              | - InvalidArgumentException がスローされる    | `AutoComplement::findOrCreateIds()` |
| 1-2-7 | 【findOrCreateIds】 インデックス付き戻り値テスト                          | 基本機能 | 複数のアイテムを含むリストとグループ                      | - インデックスをキーとした連想配列が返される | `AutoComplement::findOrCreateIds()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/01_run_traits_tests.sh
```
