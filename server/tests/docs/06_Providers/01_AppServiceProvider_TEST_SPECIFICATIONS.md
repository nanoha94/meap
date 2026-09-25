# AppServiceProvider テストケース詳細仕様

## 概要

`AppServiceProvider::boot()` の基本動作を検証するテスト。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 6-1-1 | 【起動】 boot が例外なく完了する | 正常系 | 通常のテスト環境 | 例外なく boot 完了 | `AppServiceProvider::boot()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Providers/AppServiceProviderTest.php --stop-on-failure
```
