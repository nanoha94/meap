# ExceptionHandlerTrait トレイトテストケース詳細仕様

## 目次

-   [概要](#概要)
-   [テストケース一覧表](#テストケース一覧表)
-   [テスト実行方法](#テスト実行方法)

---

## 概要

`ExceptionHandlerTrait`トレイトの動作を検証するための包括的なテストスイートを作成しました。各テストケースは、特定の入力に対して期待される出力を明確に定義し、トレイトの動作を詳細に検証します。

## テストケース一覧表

| ID    | テスト名                          | 種別     | 入力条件                                     | 期待される出力                                                         | 対応するコードレスポンス                                                  | 検証ポイント                 |
| ----- | --------------------------------- | -------- | -------------------------------------------- | ---------------------------------------------------------------------- | ------------------------------------------------------------------------- | ---------------------------- |
| 1-3-1 | ValidationException 処理テスト    | 例外処理 | POST `/api/users`<br>ValidationException     | - HTTP 422<br>- バリデーションエラーメッセージ<br>- ログにエラー詳細   | `ExceptionHandlerTrait.php`<br>handleException() メソッド (約 21-36 行目) | バリデーション例外処理       |
| 1-3-2 | ModelNotFoundException 処理テスト | 例外処理 | GET `/api/users/1`<br>ModelNotFoundException | - HTTP 404<br>- ユーザーが見つかりませんメッセージ<br>- ログに検索条件 | `ExceptionHandlerTrait.php`<br>handleException() メソッド (約 38-45 行目) | モデル未発見例外処理         |
| 1-3-3 | QueryException 処理テスト         | 例外処理 | GET `/api/users`<br>QueryException           | - HTTP 500<br>- データベースエラーメッセージ<br>- ログにエラー詳細     | `ExceptionHandlerTrait.php`<br>handleException() メソッド (約 48-54 行目) | クエリ例外処理               |
| 1-3-4 | 汎用例外処理テスト                | 例外処理 | POST `/api/process`<br>Exception             | - HTTP 500<br>- システムエラーメッセージ<br>- ログに例外詳細           | `ExceptionHandlerTrait.php`<br>handleException() メソッド (約 57-59 行目) | 汎用例外処理                 |
| 1-3-5 | カスタムステータスコードテスト    | 例外処理 | GET `/api/test`<br>カスタム例外（418）       | - HTTP 418<br>- カスタムエラーメッセージ                               | `ExceptionHandlerTrait.php`<br>handleException() メソッド (約 57-59 行目) | カスタムステータスコード取得 |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/02_run_logging_tests.sh
```

### ログ出力の確認

```bash
# リアルタイムでのログ確認
tail -f storage/logs/laravel.log

# 特定のログレベルの確認
grep 'ERROR' storage/logs/laravel.log
grep 'INFO' storage/logs/laravel.log
grep 'WARNING' storage/logs/laravel.log
```
