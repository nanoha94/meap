# MasterController テストケース詳細仕様

## 概要

マスターデータ取得 API（`GET /master`）のテスト。グループ単位の 7 種マスタ（ユーザー・料理カテゴリ・食材カテゴリ・食材単位・献立カテゴリ・買い物カテゴリ・買い物タグ）を一括取得する。`MasterService` はキャッシュ付きで各マスタサービスの `index()` を集約する。異常系のサービス例外テストでは `MasterService` をモックする。

## エンドポイント

| メソッド | パス | コントローラメソッド |
|---|---|---|
| GET | `/master` | `__invoke()` |

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 3-16-1 | 【マスターデータ取得】 正常にマスターデータを取得できる | 正常系 | 認証済み・メール認証済みユーザー、グループ所属 | HTTP 200、success=true、マスターデータ JSON | `MasterController::__invoke()` |
| 3-16-2 | 【マスターデータ取得】 レスポンスに全 7 種のキーが含まれる | 正常系 | 認証済み・メール認証済みユーザー、グループ所属 | HTTP 200、data に users / recipeCategories / ingredientCategories / ingredientUnits / mealCategories / shoppingCategories / shoppingTags が含まれる | `MasterController::__invoke()` |
| 3-16-3 | 【マスターデータ取得】 未認証 | 異常系 | 認証なし | HTTP 401 | `MasterController::__invoke()` |
| 3-16-4 | 【マスターデータ取得】 メール未認証 | 異常系 | 認証済みユーザー（email_verified_at が null） | HTTP 409、メール未確認メッセージ | `MasterController::__invoke()` |
| 3-16-5 | 【マスターデータ取得】 グループに所属していない | 異常系 | グループ未所属の認証済みユーザー | HTTP 422、グループ未所属メッセージ | `MasterController::__invoke()` |
| 3-16-6 | 【マスターデータ取得】 サービス例外 | 異常系 | MasterService::index が例外を投げる | HTTP 500 | `MasterController::__invoke()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/MasterControllerTest.php --stop-on-failure
```
