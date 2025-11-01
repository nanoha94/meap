# MasterController テストケース詳細仕様

## 概要

MasterController のテストケースの詳細仕様を示します。マスターデータの取得機能を検証し、システムの安定性と安全性を確保します。

## テストケース一覧表

| ID     | テスト名                         | 種別   | 入力条件                           | 期待される出力                   | 該当メソッド                   |
| ------ | -------------------------------- | ------ | ---------------------------------- | -------------------------------- | ------------------------------ |
| 3-4-1  | 正常なマスターデータ取得         | 正常系 | 認証済みユーザー                   | HTTP 200 JSON success            | `MasterController::__invoke()` |
| 3-4-2  | レシピカテゴリデータ取得確認     | 正常系 | 正常なマスターデータ取得後         | レシピカテゴリが正しく取得される | `MasterController::__invoke()` |
| 3-4-3  | 食材カテゴリデータ取得確認       | 正常系 | 正常なマスターデータ取得後         | 食材カテゴリが正しく取得される   | `MasterController::__invoke()` |
| 3-4-4  | 食材単位データ取得確認           | 正常系 | 正常なマスターデータ取得後         | 食材単位が正しく取得される       | `MasterController::__invoke()` |
| 3-4-5  | コース種別データ取得確認         | 正常系 | 正常なマスターデータ取得後         | コース種別が正しく取得される     | `MasterController::__invoke()` |
| 3-4-6  | 買い物カテゴリデータ取得確認     | 正常系 | 正常なマスターデータ取得後         | 買い物カテゴリが正しく取得される | `MasterController::__invoke()` |
| 3-4-7  | 買い物タグデータ取得確認         | 正常系 | 正常なマスターデータ取得後         | 買い物タグが正しく取得される     | `MasterController::__invoke()` |
| 3-4-8  | データの並び順確認               | 正常系 | 正常なマスターデータ取得後         | 各データが order 順で取得される  | `MasterController::__invoke()` |
| 3-4-9  | 買い物カテゴリのフォーマット確認 | 正常系 | 正常なマスターデータ取得後         | isDefault が boolean で返される  | `MasterController::__invoke()` |
| 3-4-10 | レスポンス構造確認               | 正常系 | 正常なマスターデータ取得後         | 正しい構造でレスポンスが返される | `MasterController::__invoke()` |
| 3-4-11 | 空のマスターデータ               | 正常系 | マスターデータが存在しない         | 空の配列が返される               | `MasterController::__invoke()` |
| 3-4-12 | 未認証ユーザー                   | 異常系 | 認証されていないユーザー           | HTTP 401 Unauthorized            | `MasterController::__invoke()` |
| 3-4-13 | グループが存在しない             | 異常系 | ユーザーにグループが紐づいていない | HTTP 422 Unprocessable Entity    | `MasterController::__invoke()` |
| 3-4-14 | データベース接続エラー           | 異常系 | データベース接続が失敗             | HTTP 500 Internal Server Error   | `MasterController::__invoke()` |
| 3-4-15 | レシピカテゴリ取得失敗           | 異常系 | レシピカテゴリ取得で例外           | HTTP 500 Internal Server Error   | `MasterController::__invoke()` |
| 3-4-16 | 食材カテゴリ取得失敗             | 異常系 | 食材カテゴリ取得で例外             | HTTP 500 Internal Server Error   | `MasterController::__invoke()` |
| 3-4-17 | 食材単位取得失敗                 | 異常系 | 食材単位取得で例外                 | HTTP 500 Internal Server Error   | `MasterController::__invoke()` |
| 3-4-18 | コース種別取得失敗               | 異常系 | コース種別取得で例外               | HTTP 500 Internal Server Error   | `MasterController::__invoke()` |
| 3-4-19 | 買い物カテゴリ取得失敗           | 異常系 | 買い物カテゴリ取得で例外           | HTTP 500 Internal Server Error   | `MasterController::__invoke()` |
| 3-4-20 | 買い物タグ取得失敗               | 異常系 | 買い物タグ取得で例外               | HTTP 500 Internal Server Error   | `MasterController::__invoke()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/03_run_api_tests.sh
```
