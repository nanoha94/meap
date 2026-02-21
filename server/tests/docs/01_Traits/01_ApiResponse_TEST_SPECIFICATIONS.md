# ApiResponse テストケース詳細仕様

## 目次

-   [概要](#概要)
-   [テストケース一覧表](#テストケース一覧表)
-   [テスト実行方法](#テスト実行方法)

---

## 概要

`ApiResponse`トレイトの動作を検証するための包括的なテストスイートを作成しました。各テストケースは、特定の入力に対して期待される出力を明確に定義し、トレイトの動作を詳細に検証します。

## テストケース一覧表

| ID     | テスト名                                                     | 種別     | 入力条件                             | 期待される出力                                                                             | 該当メソッド                    |
| ------ | ------------------------------------------------------------ | -------- | ------------------------------------ | ------------------------------------------------------------------------------------------ | ------------------------------- |
| 1-1-1  | 【successResponse】 成功レスポンステスト                     | 基本機能 | データとメッセージを入力             | - 成功フラグが true<br>- ステータスコード 200<br>- 入力データとメッセージが含まれる        | `ApiResponse::successResponse()` |
| 1-1-2  | 【successResponseWithWarning】 警告付き成功レスポンステスト   | 基本機能 | データ、メッセージ、警告を入力       | - 成功フラグが true<br>- ステータスコード 200<br>- 警告が含まれる                          | `ApiResponse::successResponseWithWarning()` |
| 1-1-3  | 【successResponseWithWarning】 警告なし成功レスポンステスト   | 基本機能 | データとメッセージを入力し、警告なし | - 成功フラグが true<br>- ステータスコード 200<br>- 警告が含まれない                        | `ApiResponse::successResponseWithWarning()` |
| 1-1-4  | 【createdResponse】 データ作成成功レスポンステスト            | 基本機能 | データを入力                         | - 成功フラグが true<br>- ステータスコード 201<br>- データ作成メッセージが含まれる          | `ApiResponse::createdResponse()` |
| 1-1-5  | 【updatedResponse】 データ更新成功レスポンステスト            | 基本機能 | データを入力                         | - 成功フラグが true<br>- ステータスコード 200<br>- データ更新メッセージが含まれる          | `ApiResponse::updatedResponse()` |
| 1-1-6  | 【deletedResponse】 データ削除成功レスポンステスト            | 基本機能 | メッセージを入力                     | - 成功フラグが true<br>- ステータスコード 200<br>- データ削除メッセージが含まれる          | `ApiResponse::deletedResponse()` |
| 1-1-7  | 【indexResponse】 データ一覧取得レスポンステスト              | 基本機能 | データと合計数を入力                 | - 成功フラグが true<br>- ステータスコード 200<br>- データと合計数が含まれる                | `ApiResponse::indexResponse()`  |
| 1-1-8  | 【indexResponse】 データ一覧取得レスポンステスト（total なし）| 基本機能 | データを入力                         | - 成功フラグが true<br>- ステータスコード 200<br>- データが含まれる                        | `ApiResponse::indexResponse()`  |
| 1-1-9  | 【showResponse】 データ詳細取得レスポンステスト               | 基本機能 | データを入力                         | - 成功フラグが true<br>- ステータスコード 200<br>- データが含まれる                        | `ApiResponse::showResponse()`   |
| 1-1-10 | 【errorResponse】 エラーレスポンステスト                      | 基本機能 | エラーメッセージを入力               | - 成功フラグが false<br>- ステータスコード 400<br>- エラーメッセージが含まれる             | `ApiResponse::errorResponse()`  |
| 1-1-11 | 【errorResponse】 エラー詳細付きエラーレスポンステスト        | 基本機能 | エラーメッセージとエラー詳細を入力   | - 成功フラグが false<br>- ステータスコード 400<br>- エラーメッセージとエラー詳細が含まれる | `ApiResponse::errorResponse()`  |
| 1-1-12 | 【errorResponse】 エラータイプ付きエラーレスポンステスト      | 基本機能 | エラーメッセージとエラータイプを入力 | - 成功フラグが false<br>- ステータスコード 400<br>- エラータイプが含まれる                 | `ApiResponse::errorResponse()`  |
| 1-1-13 | 【notFoundResponse】 データ未発見エラーレスポンステスト       | 基本機能 | メッセージを入力                     | - 成功フラグが false<br>- ステータスコード 404<br>- 未発見メッセージが含まれる             | `ApiResponse::notFoundResponse()`         |
| 1-1-14 | 【unauthorizedResponse】 認証エラーレスポンステスト           | 基本機能 | メッセージを入力                     | - 成功フラグが false<br>- ステータスコード 401<br>- 認証エラーメッセージが含まれる         | `ApiResponse::unauthorizedResponse()`    |
| 1-1-15 | 【forbiddenResponse】 権限エラーレスポンステスト             | 基本機能 | メッセージを入力                     | - 成功フラグが false<br>- ステータスコード 403<br>- 権限エラーメッセージが含まれる         | `ApiResponse::forbiddenResponse()`        |
| 1-1-16 | 【serverErrorResponse】 サーバーエラーレスポンステスト        | 基本機能 | メッセージを入力                     | - 成功フラグが false<br>- ステータスコード 500<br>- サーバーエラーメッセージが含まれる     | `ApiResponse::serverErrorResponse()`      |
| 1-1-17 | 【databaseErrorResponse】 データベースエラーレスポンステスト  | 基本機能 | メッセージを入力                     | - 成功フラグが false<br>- ステータスコード 500<br>- データベースエラーメッセージが含まれる | `ApiResponse::databaseErrorResponse()`   |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/01_run_traits_tests.sh
```
