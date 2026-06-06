# AiUsageService テストケース詳細仕様

## 概要

AI 利用回数の消費・返却・リセット・ステータス取得を担う `AiUsageService` の単体テスト。  
コントローラー経由ではテストできないビジネスロジック（利用回数消費・返却・課金周期更新・リセット日計算）を直接検証する。

## テストケース一覧表


| ID    | テスト名                                | 種別  | 入力条件                                      | 期待される出力                                          | 該当メソッド                                 |
| ----- | ----------------------------------- | --- | ----------------------------------------- | ------------------------------------------------ | -------------------------------------- |
| 4-1-1 | 【利用回数消費】 利用回数を消費できる                 | 正常系 | デフォルト状態のグループ                              | ai_usage_count が 1 増加                            | `AiUsageService::consumeUsage()`       |
| 4-1-2 | 【利用回数消費】 次回リセット日時を過ぎるとカウンターがリセットされる | 正常系 | ai_usage_reset_at を過去に設定、ai_usage_count=3 | ai_usage_count=1、ai_usage_reset_at が未来に更新        | `AiUsageService::consumeUsage()`       |
| 4-1-3 | 【利用回数消費】 数か月未使用でも次回操作時に正しくリセットされる   | 正常系 | ai_usage_reset_at を5か月前に設定、ai_usage_count=2 | ai_usage_count=1、ai_usage_reset_at が未来かつ元の日を保持  | `AiUsageService::consumeUsage()`       |
| 4-1-4 | 【利用回数消費】 月次上限超過で 429 を投げる           | 異常系 | FREE プラン、ai_usage_count=上限値               | HttpException 429                                | `AiUsageService::consumeUsage()`       |
| 4-1-5 | 【利用状況取得】 リセット待ちの古い利用回数を同期する         | 正常系 | ai_usage_reset_at を過去に設定、ai_usage_count=3 | usageCount=0、DB も同期                              | `AiUsageService::getUsageStatus()`     |
| 4-1-6 | 【課金周期更新】 Stripe の課金周期でリセットする        | 正常系 | ai_usage_count=3、periodEnd を指定            | ai_usage_count=0、ai_usage_reset_at=periodEnd     | `AiUsageService::renewBillingPeriod()` |
| 4-1-7 | 【利用回数返却】 消費分を返却できる                  | 正常系 | consumeUsage 後に refundUsage               | ai_usage_count=0                                 | `AiUsageService::refundUsage()`        |
| 4-1-8 | 【利用回数返却】 数か月未使用後の返却でカウントが負にならない   | 正常系 | ai_usage_reset_at を3か月前に設定、ai_usage_count=2 | ai_usage_count=0（リセット後の返却で負にならない）            | `AiUsageService::refundUsage()`        |
| 4-1-9 | 【グループ作成】 AI 利用トラッキングの初期値を設定する       | 正常系 | Group::createGroup() 実行                   | plan=FREE、ai_usage_count=0、ai_usage_reset_at が未来 | `Group::createGroup()`                 |


## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/AiUsageServiceTest.php --stop-on-failure
```
