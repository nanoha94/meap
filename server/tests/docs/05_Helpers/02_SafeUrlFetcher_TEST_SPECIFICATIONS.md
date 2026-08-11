# SafeUrlFetcher テストケース詳細仕様

## 概要

SSRF 対策付き URL フェッチャー `SafeUrlFetcher` の単体テスト。https スキーム制限、解決後 IP の拒否、リダイレクト無効でのフェッチを検証する。

## テストケース一覧表

| ID | テスト名 | 種別 | 入力条件 | 期待される出力 | 該当メソッド |
|----|----------|------|----------|----------------|--------------|
| 5-2-1 | 【isBlockedIp】 private / loopback / link-local IP を拒否する | 正常系 | 127.0.0.1, 10.0.0.1, 192.168.1.1, 169.254.169.254, ::1 | いずれも true | `SafeUrlFetcher::isBlockedIp()` |
| 5-2-2 | 【isBlockedIp】 公開 IP は許可する | 正常系 | 8.8.8.8, 1.1.1.1 | false | `SafeUrlFetcher::isBlockedIp()` |
| 5-2-3 | 【validateUrl】 http スキームを拒否する | 異常系 | `http://example.com/recipe` | エラーメッセージ | `SafeUrlFetcher::validateUrl()` |
| 5-2-4 | 【validateUrl】 localhost を拒否する | 異常系 | `https://localhost/recipe` | エラーメッセージ | `SafeUrlFetcher::validateUrl()` |
| 5-2-5 | 【validateUrl】 メタデータ IP を拒否する | 異常系 | `https://169.254.169.254/meta-data` | エラーメッセージ | `SafeUrlFetcher::validateUrl()` |
| 5-2-6 | 【validateUrl】 公開 HTTPS URL は許可する | 正常系 | `https://example.com/recipe` | null | `SafeUrlFetcher::validateUrl()` |
| 5-2-7 | 【fetch】 リダイレクトレスポンスを拒否する | 異常系 | 302 レスポンス | SafeUrlFetchException | `SafeUrlFetcher::fetch()` |
| 5-2-8 | 【fetch】 正常レスポンスのボディを返す | 正常系 | 200 レスポンス | HTML 文字列 | `SafeUrlFetcher::fetch()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Unit/Helpers/SafeUrlFetcherTest.php --stop-on-failure
```
