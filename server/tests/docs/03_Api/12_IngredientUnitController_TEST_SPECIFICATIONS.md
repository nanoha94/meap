# IngredientUnitController テストケース詳細仕様

## 概要

IngredientUnitController のテストケースの詳細仕様を示します。食材単位の一覧取得機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                                           | 種別   | 入力条件                           | 期待される出力                            | 該当メソッド                        |
| ------ | -------------------------------------------------- | ------ | ---------------------------------- | ----------------------------------------- | ----------------------------------- |
| 3-12-1 | 【一覧取得】 正常な食材単位一覧取得                | 正常系 | 認証済みユーザー                   | HTTP 200 JSON success                     | `IngredientUnitController::index()` |
| 3-12-2 | 【一覧取得】 単位情報の並び順確認                  | 正常系 | 正常な単位一覧取得後               | order 順で単位が取得される                | `IngredientUnitController::index()` |
| 3-12-3 | 【一覧取得】 空の単位一覧                          | 正常系 | 単位が存在しない                   | HTTP 200 JSON success                     | `IngredientUnitController::index()` |
| 3-12-4 | 【一覧取得】 レスポンス形式確認                    | 正常系 | 正常な単位一覧取得後               | 正しい JSON 形式でレスポンスが返される    | `IngredientUnitController::index()` |
| 3-12-5 | 【一覧取得】 各フィールドの確認                    | 正常系 | 正常な単位一覧取得後               | id, name, position, requiresQuantity, order が正しく返される | `IngredientUnitController::index()` |
| 3-12-6 | 【一覧取得】 position フィールドの確認             | 正常系 | 正常な単位一覧取得後               | position が 'prefix' または 'suffix' で返される | `IngredientUnitController::index()` |
| 3-12-7 | 【一覧取得】 requiresQuantity フィールドの確認      | 正常系 | 正常な単位一覧取得後               | requiresQuantity が boolean で返される    | `IngredientUnitController::index()` |
| 3-12-8 | 【一覧取得】 他グループの単位は取得されない        | 正常系 | 他グループの単位が存在              | 自グループの単位のみが取得される          | `IngredientUnitController::index()` |
| 3-12-9 | 【一覧取得】 未認証ユーザー                        | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                     | `IngredientUnitController::index()` |
| 3-12-10 | 【一覧取得】 グループが存在しない                   | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity             | `IngredientUnitController::index()` |
| 3-12-11 | 【一覧取得】 データベース接続エラー                | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error            | `IngredientUnitController::index()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
