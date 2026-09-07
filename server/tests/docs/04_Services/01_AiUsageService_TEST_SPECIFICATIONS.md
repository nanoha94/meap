# AiUsageService テストケース詳細仕様

## 概要

AI 利用回数の消費・返却・リセット・ステータス取得を担う `AiUsageService` の単体テスト。

## プラン変更時の月間残数ルール

| 変更 | 条件 | 月間残数 | 備考 |
|---|---|---|---|
| FREE → 有料 | 常時 | 新プラン上限 | `adjustMonthlyRemainingForPlanChange()` |
| 有料 → FREE | ai_usage_reset_at 以前（周期内） | 変更なし | GracePeriod 中は有料分を維持 |
| 有料 → FREE | ai_usage_reset_at 以降（周期終了直後） | 0 | `adjustMonthlyRemainingForPlanChange()`（Webhook 時点） |
| 有料 → FREE | 周期終了後・次回操作時 | 3 | `resetFreeMonthlyUsageIfNeeded()`（`getUsageStatus()` / `consumeUsage()`） |
| 有料 → 有料（アップグレード） | 常時 | 新上限 | `adjustMonthlyRemainingForPlanChange()` |
| 有料 → 有料（ダウングレード） | 周期内 | 変更なし | 旧プランの残数を維持 |
| 有料 → 有料（ダウングレード） | 周期終了後 | 変更なし | 新上限は `renewBillingPeriod()` |

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 4-1-1 | 【利用回数消費】 利用回数を消費できる | 正常系 | デフォルト状態 | ai_monthly_remaining が 1 減少 | `AiUsageService::consumeUsage()` |
| 4-1-2 | 【利用回数消費】 次回リセット日時を過ぎるとフリー枠がリセットされる | 正常系 | ai_monthly_remaining=0、過去の reset_at | リセットで3、1回消費後残2 | `AiUsageService::consumeUsage()` |
| 4-1-3 | 【利用回数消費】 数か月未使用でも次回操作時にフリー枠がリセットされる | 正常系 | ai_monthly_remaining=1、5か月前の reset_at | リセットで3、1回消費後残2 | `AiUsageService::consumeUsage()` |
| 4-1-4 | 【利用回数消費】 月次上限到達後は買い切り残高から消費する | 正常系 | 月間0、パック5 | パック4 | `AiUsageService::consumeUsage()` |
| 4-1-5 | 【利用回数消費】 月次枠に余裕がある間は ai_monthly_remaining を減算する | 正常系 | 月間2、パック5 | 月間1、パック5 | `AiUsageService::consumeUsage()` |
| 4-1-6 | 【利用回数消費】 月次枠・買い切り残高ともに不足で 429 を投げる | 異常系 | FREE、月間0、パック0 | HttpException 429 | `AiUsageService::consumeUsage()` |
| 4-1-7 | 【利用回数消費】 有料プランで月次枠・買い切り残高ともに不足で 429 を投げる | 異常系 | STANDARD、月間0、パック0 | HttpException 429 | `AiUsageService::consumeUsage()` |
| 4-1-8 | 【利用回数返却】 消費分を返却できる | 正常系 | consume 後 refund | ai_monthly_remaining=3 | `AiUsageService::refundUsage()` |
| 4-1-9 | 【利用回数返却】 リセット後の消費を返却できる | 正常系 | リセット後 consume → refund | ai_monthly_remaining=3 | `AiUsageService::refundUsage()` |
| 4-1-10 | 【利用回数返却】 買い切り残高消費分を返却できる | 正常系 | パック消費後 refund | パックが元に戻る | `AiUsageService::refundUsage()` |
| 4-1-11 | 【利用状況取得】 リセット待ちのフリー枠を同期する | 正常系 | 月間0、過去の reset_at | monthlyRemaining=3 | `AiUsageService::getUsageStatus()` |
| 4-1-12 | 【利用状況取得】 解約後周期内はフリー月次リセットで上書きされない | 正常系 | FREE 残20、reset_at 未来 | 残20 | `AiUsageService::getUsageStatus()` |
| 4-1-13 | 【利用状況取得】 周期終了後にフリー月次リセットで枠が復帰する | 正常系 | FREE 残0、reset_at 過去 | 残3 | `AiUsageService::getUsageStatus()` |
| 4-1-14 | 【課金周期更新】 Stripe の課金周期でリセットする | 正常系 | STANDARD、月間0 | ai_monthly_remaining=30 | `AiUsageService::renewBillingPeriod()` |
| 4-1-15 | 【課金周期更新】 月間残ありでもプラン上限にリセットする | 正常系 | STANDARD、月間20 | ai_monthly_remaining=30 | `AiUsageService::renewBillingPeriod()` |
| 4-1-16 | 【課金周期更新】 Pro プランで課金周期をリセットする | 正常系 | PRO、月間10 | ai_monthly_remaining=50 | `AiUsageService::renewBillingPeriod()` |
| 4-1-17 | 【課金周期更新】 請求周期更新で ai_usage_reset_at を新しい請求日に更新する | 正常系 | STANDARD、旧 reset_at 未来 | reset_at=新請求日、残30 | `AiUsageService::renewBillingPeriod()` |
| 4-1-18 | 【課金周期更新】 ダウングレード後の初回請求で新プラン上限にリセットする | 正常系 | STANDARD、旧 PRO 残40 | 残30、reset_at=新請求日 | `AiUsageService::renewBillingPeriod()` |
| 4-1-19 | 【プラン変更】 フリー使い切りからスタンダードへ変更すると新プラン上限が付与される | 正常系 | FREE 残0 | 残30 | `AiUsageService::adjustMonthlyRemainingForPlanChange()` |
| 4-1-20 | 【プラン変更】 フリー残数があっても新プラン上限のみ付与される | 正常系 | FREE 残2 | 残30 | `AiUsageService::adjustMonthlyRemainingForPlanChange()` |
| 4-1-21 | 【プラン変更】 スタンダード解約からフリーへ変更しても周期内は残数を維持する | 正常系 | STANDARD 残20、周期内 | 残20 | `AiUsageService::adjustMonthlyRemainingForPlanChange()` |
| 4-1-22 | 【プラン変更】 スタンダード解約からフリーへ変更し周期終了後は月間残数0になる | 正常系 | STANDARD 残20、周期終了後 | 残0（次回操作時に3、→4-1-13） | `AiUsageService::adjustMonthlyRemainingForPlanChange()` |
| 4-1-23 | 【プラン変更】 同一プランへの変更では残数を変更しない | 正常系 | STANDARD 残20、同一プラン | 残20 | `AiUsageService::adjustMonthlyRemainingForPlanChange()` |
| 4-1-24 | 【プラン変更】 スタンダードから Pro へアップグレードすると新プラン上限が付与される | 正常系 | STANDARD 残20 | 残50 | `AiUsageService::adjustMonthlyRemainingForPlanChange()` |
| 4-1-25 | 【プラン変更】 Pro からスタンダードへダウングレード予約しても周期内は残数を維持する | 正常系 | PRO 残40、周期内 | 残40 | `AiUsageService::adjustMonthlyRemainingForPlanChange()` |
| 4-1-26 | 【プラン変更】 アップグレード後の請求周期更新で残数と請求日が更新される | 正常系 | STANDARD→PRO、plan 更新後 invoice.paid | 残50、reset_at=新請求日 | `AiUsageService::adjustMonthlyRemainingForPlanChange()` + `AiUsageService::renewBillingPeriod()` |
| 4-1-27 | 【プラン変更】 ダウングレード予約から初回請求まで残数を維持し請求後に新上限へリセットする | 正常系 | PRO→STANDARD、周期末請求 | 請求前残40、請求後残30 | `AiUsageService::adjustMonthlyRemainingForPlanChange()` + `AiUsageService::renewBillingPeriod()` |
| 4-1-28 | 【グループ作成】 AI 利用トラッキングの初期値を設定する | 正常系 | createGroup | 月間3、reset_at=現時点+1か月 | `Group::createGroup()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/AiUsageServiceTest.php --stop-on-failure
```
