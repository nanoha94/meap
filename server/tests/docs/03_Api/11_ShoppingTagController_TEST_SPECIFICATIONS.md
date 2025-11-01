# ShoppingTagController テストケース詳細仕様

## 概要

ShoppingTagController のテストケースの詳細仕様を示します。買い物タグの一覧取得機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID      | テスト名                 | 種別   | 入力条件                           | 期待される出力                         | 該当メソッド                     |
| ------- | ------------------------ | ------ | ---------------------------------- | -------------------------------------- | -------------------------------- |
| 3-11-1  | 正常な買い物タグ一覧取得 | 正常系 | 認証済みユーザー                   | HTTP 200 JSON success                  | `ShoppingTagController::index()` |
| 3-11-2  | タグデータの取得確認     | 正常系 | 正常なタグ一覧取得後               | タグの ID と名前が正しく取得される     | `ShoppingTagController::index()` |
| 3-11-3  | タグ総数の確認           | 正常系 | 正常なタグ一覧取得後               | タグの総数が正しく返される             | `ShoppingTagController::index()` |
| 3-11-4  | レスポンス構造確認       | 正常系 | 正常なタグ一覧取得後               | 正しい構造でレスポンスが返される       | `ShoppingTagController::index()` |
| 3-11-5  | 空のタグリスト           | 正常系 | タグが存在しない場合               | 空の配列が返される                     | `ShoppingTagController::index()` |
| 3-11-6  | レスポンス形式確認       | 正常系 | 正常なタグ一覧取得後               | 正しい JSON 形式でレスポンスが返される | `ShoppingTagController::index()` |
| 3-11-7  | タグデータの並び順確認   | 正常系 | 正常なタグ一覧取得後               | タグが適切な順序で取得される           | `ShoppingTagController::index()` |
| 3-11-8  | 大量のタグデータ処理     | 正常系 | 大量のタグが存在する場合           | 全てのタグが正しく取得される           | `ShoppingTagController::index()` |
| 3-11-9  | 未認証ユーザー           | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized                  | `ShoppingTagController::index()` |
| 3-11-10 | グループが存在しない     | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity          | `ShoppingTagController::index()` |
| 3-11-11 | データベース接続エラー   | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error         | `ShoppingTagController::index()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
