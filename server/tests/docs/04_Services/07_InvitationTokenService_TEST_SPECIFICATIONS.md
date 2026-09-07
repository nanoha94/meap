# InvitationTokenService テストケース詳細仕様

## 概要

招待トークンの生成・ルックアップを担う `InvitationTokenService` の単体テスト。`token_lookup` 列によるインデックス付きルックアップと、`createWithExpiration` の重複検知を検証する。

## テストケース一覧表

| ID     | テスト名                                                         | 種別   | 入力条件                                                         | 期待される出力                                      | 該当メソッド                                   |
| ------ | ---------------------------------------------------------------- | ------ | ---------------------------------------------------------------- | --------------------------------------------------- | ---------------------------------------------- |
| 4-7-1  | 【トークンルックアップ】 平文トークンに一致するレコードを返す    | 正常系 | DB に保存済みの平文トークン                                      | 一致する `InvitationToken` が返る                   | `InvitationTokenService::findByPlainToken()`   |
| 4-7-2  | 【トークンルックアップ】 存在しないトークンは null               | 正常系 | DB に存在しない平文トークン                                      | null                                                | `InvitationTokenService::findByPlainToken()`   |
| 4-7-3  | 【トークンルックアップ】 同一 prefix でハッシュ不一致は null     | 正常系 | 同一 `token_lookup` を持つ別平文トークン                         | null                                                | `InvitationTokenService::findByPlainToken()`   |
| 4-7-4  | 【トークン生成】 既存トークンと衝突時に再試行して保存する        | 正常系 | 1 回目生成トークンが既存と衝突、2 回目は未使用                   | 新規トークンが保存され平文が返る                    | `InvitationTokenService::createWithExpiration()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./vendor/bin/sail test tests/Feature/Services/InvitationTokenServiceTest.php --stop-on-failure
```
