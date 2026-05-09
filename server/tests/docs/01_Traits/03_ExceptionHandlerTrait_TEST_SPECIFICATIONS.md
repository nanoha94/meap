# ExceptionHandlerTrait テストケース詳細仕様

## 目次

-   [概要](#概要)
-   [テストケース一覧表](#テストケース一覧表)
-   [テスト実行方法](#テスト実行方法)

---

## 概要

`ExceptionHandlerTrait`トレイトの動作を検証するための包括的なテストスイートを作成しました。各テストケースは、特定の入力に対して期待される出力を明確に定義し、トレイトの動作を詳細に検証します。

## テストケース一覧表

| ID    | テスト名                                                                 | 種別     | 入力条件                                     | 期待される出力                                                         | 該当メソッド                              |
| ----- | ------------------------------------------------------------------------ | -------- | -------------------------------------------- | ---------------------------------------------------------------------- | ----------------------------------------- |
| 1-3-1 | 【handleException】 ValidationException 処理テスト                       | 例外処理 | POST `/api/users`<br>ValidationException     | - HTTP 422<br>- バリデーションエラーメッセージ<br>- ログにエラー詳細   | `ExceptionHandlerTrait::handleException()` |
| 1-3-2 | 【handleException】 HttpException 処理テスト                             | 例外処理 | GET `/api/test`<br>HttpException             | - HTTP 指定コード<br>- HTTP エラーメッセージ<br>- ログにエラー詳細     | `ExceptionHandlerTrait::handleException()` |
| 1-3-3 | 【handleException】 ModelNotFoundException 処理テスト                    | 例外処理 | GET `/api/users/1`<br>ModelNotFoundException | - HTTP 404<br>- ユーザーが見つかりませんメッセージ<br>- ログに検索条件 | `ExceptionHandlerTrait::handleException()` |
| 1-3-4 | 【handleException】 QueryException 処理テスト                            | 例外処理 | GET `/api/users`<br>QueryException           | - HTTP 500<br>- データベースエラーメッセージ<br>- ログにエラー詳細     | `ExceptionHandlerTrait::handleException()` |
| 1-3-5 | 【handleGenericException】 汎用例外処理テスト                            | 例外処理 | POST `/api/process`<br>Exception             | - HTTP 500<br>- システムエラーメッセージ<br>- ログに例外詳細           | `ExceptionHandlerTrait::handleGenericException()` |
| 1-3-6 | 【getExceptionStatusCode】 カスタムステータスコードテスト                | 例外処理 | GET `/api/test`<br>カスタム例外（418）       | - HTTP 418<br>- カスタムエラーメッセージ                               | `ExceptionHandlerTrait::getExceptionStatusCode()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/01_run_traits_tests.sh
```
