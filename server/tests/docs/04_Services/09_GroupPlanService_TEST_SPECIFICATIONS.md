# GroupPlanService テストケース詳細仕様

## 概要

`Group.plan` の更新と、プラン変更に伴う月間 AI 利用残数の調整を担う `GroupPlanService` の単体テスト。課金 API・Webhook など複数経路から共通利用される。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|---|---|---|---|---|---|
| 4-9-1 | 【プラン更新】 FREE から STANDARD へ変更すると月間残数が新上限になる | 正常系 | FREE、ai_monthly_remaining=0 | plan=STANDARD、ai_monthly_remaining=30 | `GroupPlanService::update()` |
| 4-9-2 | 【プラン更新】 同一プランへの更新では月間残数を変更しない | 正常系 | STANDARD、ai_monthly_remaining=20 | plan=STANDARD、ai_monthly_remaining=20 | `GroupPlanService::update()` |
| 4-9-3 | 【プラン更新】 STANDARD から FREE へ周期内変更では月間残数を維持する | 正常系 | STANDARD、ai_monthly_remaining=20、ai_usage_reset_at が未来 | plan=FREE、ai_monthly_remaining=20 | `GroupPlanService::update()` |
| 4-9-4 | 【プラン更新】 STANDARD から FREE へ周期終了後は月間残数を 0 にする | 正常系 | STANDARD、ai_monthly_remaining=20、ai_usage_reset_at が過去 | plan=FREE、ai_monthly_remaining=0 | `GroupPlanService::update()` |
| 4-9-5 | 【プラン更新】 プラン変更しても ai_pack_remaining は変更しない | 正常系 | ai_pack_remaining=15、FREE→STANDARD | ai_pack_remaining=15 | `GroupPlanService::update()` |
| 4-9-6 | 【プラン更新】 削除済み Group はサイレントにスキップする | 異常系 | トランザクション内で Group が存在しない ID | 例外を投げずに処理終了 | `GroupPlanService::update()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/GroupPlanServiceTest.php --stop-on-failure
```
