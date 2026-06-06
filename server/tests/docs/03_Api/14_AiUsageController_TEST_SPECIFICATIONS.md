# AiUsageController テストケース詳細仕様

## 概要

AI 利用状況取得 API（`GET /ai/usage`）のテスト。課金周期に基づく利用回数・次回リセット日時の表示を含む。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 3-13-1 | 【AI利用状況取得】 正常に利用状況を取得できる | 正常系 | 認証済みユーザー | HTTP 200、利用状況 JSON | `AiUsageController::show()` |
| 3-13-2 | 【AI利用状況取得】 リセット待ちの古い利用回数を同期して返す | 正常系 | ai_usage_reset_at を過去に設定 | HTTP 200、usageCount=0 | `AiUsageController::show()` |
| 3-13-3 | 【AI利用状況取得】 未認証 | 異常系 | 認証なし | HTTP 401 | `AiUsageController::show()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Api/AiUsageControllerTest.php --stop-on-failure
./vendor/bin/sail test tests/Feature/Services/AiUsageServiceTest.php --stop-on-failure
```
