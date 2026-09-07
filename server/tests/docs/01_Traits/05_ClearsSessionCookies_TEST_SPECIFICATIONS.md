# ClearsSessionCookies テストケース詳細仕様

## 概要

`ClearsSessionCookies` トレイトの動作を検証するためのテストスイートです。ログアウトやアカウント削除時にレスポンスへ付与するセッション・CSRF用Cookie削除の付与を検証します。

## テストケース一覧表

| ID    | テスト名                                                       | 種別   | 入力条件                           | 期待される出力                                                                 | 該当メソッド                              |
| ----- | -------------------------------------------------------------- | ------ | ---------------------------------- | ------------------------------------------------------------------------------ | ----------------------------------------- |
| 1-5-1 | 【clearSessionCookiesOnResponse】 レスポンスにセッション・XSRF-TOKEN削除用Cookieが付与される | 正常系 | JsonResponse を渡してメソッド実行 | レスポンスに `session.cookie` および `XSRF-TOKEN` の削除用Cookie（有効期限 -1）が含まれる | `ClearsSessionCookies::clearSessionCookiesOnResponse()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Traits/ClearsSessionCookiesTest.php --stop-on-failure
```
