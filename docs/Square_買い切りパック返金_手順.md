# Square 買い切りパック返金手順

誤ってパックを重複で購入してしまった場合の返金対応です。返金はこのケースに限定します。ユーザー画面からの取り消しはありません。Square ダッシュボードでその決済を返金し、同じ作業の中で meap の残り回数を減らします。

返金の単位は常にパック購入 1 件分（ライト 10 回 or バリュー 30 回）の全額です。最小の返金額はライト 1 件の 400 円です。

アプリは返金を検知しません。ダッシュボードで返金しただけでは `groups.ai_pack_remaining` はそのままです。回数だけ減らして返金を忘れると、支払った回数だけ失われます。

対象は買い切りパックの都度決済です。月額プランの解約はプラン管理画面のダウングレードで行います。

関連:

- [課金 E2E 手順](Square_課金_E2E_手順.md)
- [Railway 本番マイグレーション手順](Railway_本番マイグレーション手順.md)

## 環境を揃える

問い合わせの環境と、操作する Square・DB を一致させます。

| 問い合わせ | Square ダッシュボード | Neon ブランチ |
| ---------- | --------------------- | ------------- |
| 本番 | Production（[取引](https://app.squareup.com/dashboard/sales/transactions)） | `main`（本番） |
| ステージング | Sandbox（[取引](https://app.squareupsandbox.com/dashboard/sales/transactions)） | `staging` |

DB の操作は [Neon Console](https://console.neon.tech/) の **SQL Editor** で行います。操作前にブランチ名が意図した側であることを確認します。Sandbox の返金で本番ブランチを触ること、本番の返金でステージングブランチを触ることはしません。

## パックの対応

回数はコード上の固定値です。金額の既定値は次のとおりで、本番の環境変数で変わっていることがあります。返金する円は Square 上のその決済の金額を正とします。

| 内容（`billing_histories.description`） | 付与回数 | 既定の金額 | Square の参照 ID |
| --------------------------------------- | -------- | ---------- | ---------------- |
| 買い切りパック ライト | 10 | 400 円 | `pack:light` |
| 買い切りパック バリュー | 30 | 800 円 | `pack:value` |

決済のメモ（note）は JSON です。`type` が `pack`、`group_id` がグループ ID、`pack_type` が `light` または `value` です。

回数はグループ単位の合算です。どの購入分を使ったかは区別できません。同じグループのメンバー全員の残りが減ります。月間枠（`ai_monthly_remaining`）は変えません。

## 1. 対象の決済と残数を確認する

Neon Console の SQL Editor で、ブランチが意図した側であることを確認してから実行します。メールアドレスを問い合わせの本人のものに置き換えます。

```sql
SELECT g.id          AS group_id,
       g.ai_pack_remaining,
       g.square_customer_id,
       bh.square_payment_id,
       bh.description,
       bh.created_at
  FROM users u
  JOIN group_user_mappings gu ON gu.user_id = u.id
  JOIN groups g      ON g.id = gu.group_id
  LEFT JOIN billing_histories bh
         ON bh.group_id = g.id AND bh.type = 'pack'
 WHERE u.email = 'user@example.com'
 ORDER BY g.id, bh.id;
```

控えるものは次です。

- `groups.id`
- `ai_pack_remaining`（減算前の残数）
- 取り消す 1 件の `square_payment_id` と description
- その行の `created_at`

`billing_histories` の行は消しません。請求履歴の表示名がこの行を参照しています。

## 2. 減らす回数と返金額を決める

取り消すパックの付与回数を `付与`、その決済の金額（円）を `金額`、現在の `ai_pack_remaining` を `残数` とします。

重複購入の取り消しなので、常にパック 1 件分をまるごと返します。

```text
前提:    残数 ≥ 付与
返す回数 = 付与（常に全量）
返金額   = 金額（常に全額）
減算後   = 残数 - 付与
```

`残数 < 付与` のときは重複分がすでに消費されています。返金せず、問い合わせにその旨を返します。

例（ライト 400 円・10 回を 1 件取り消す）:

| 購入 | 残数 | 返す回数 | 返金額 | 減算後 |
| ---- | ---- | -------- | ------ | ------ |
| ライト 2 件、未使用 | 20 | 10 | 400 円 | 10 |
| ライト 2 件、3 回使用済み | 17 | 10 | 400 円 | 7 |
| ライト 2 件、残り 4 回 | 4 | — | 返金不可 | — |

例（バリュー 800 円・30 回を 1 件取り消す）:

| 購入 | 残数 | 返す回数 | 返金額 | 減算後 |
| ---- | ---- | -------- | ------ | ------ |
| バリュー 2 件、未使用 | 60 | 30 | 800 円 | 30 |
| バリュー 2 件、10 回使用済み | 50 | 30 | 800 円 | 20 |
| バリュー 2 件、残り 10 回 | 10 | — | 返金不可 | — |

同じ日に同額のパックが複数あるときは、`square_payment_id` が一致する 1 件だけを返します。

返金は決済日からおおよそ 1 年以内です。ダッシュボードの取引に返金操作が出ることを、回数を減らす前に確認します。ボタンが無い決済は DB を変えません。

## 3. 残り回数を減らす

`GROUP_UUID` と減算する回数を、手順 2 で決めた値に置き換えます。`WHERE ai_pack_remaining >= 10` により、残数が足りないときは 0 行更新になるため安全です。

```sql
BEGIN;

-- 減算（残数チェック付き）
UPDATE groups
   SET ai_pack_remaining = ai_pack_remaining - 10,
       updated_at = NOW()
 WHERE id = 'GROUP_UUID'
   AND ai_pack_remaining >= 10;

-- 更新件数が 1 であることを確認する
-- 0 件のときは残数不足なので ROLLBACK する

-- 減算後の値が手順 2 の想定と一致することを確認する
SELECT id, ai_pack_remaining FROM groups WHERE id = 'GROUP_UUID';

COMMIT;
```

問題があれば `COMMIT` の代わりに `ROLLBACK` を実行します。ここから Square の返金までを続けます。間を空けると、返金前に利用で残数がさらに減ります。

## 4. Square ダッシュボードで返金する

手順「環境を揃える」の取引一覧を開きます。

1. 日時（`created_at` に近いもの）と金額で取引を絞る
2. 取引を開き、次がすべて一致する 1 件であることを確認する
   - 支払い ID が `square_payment_id`
   - メモの `group_id` と `pack_type`
   - 参照 ID が `pack:light` または `pack:value`
3. **返金** を開く
4. その決済の全額を返金する
5. 理由は「重複購入の取り消し」など短い文にする
6. 返金を確定し、取引が返金済みになることを確認する

カードへの反映はカード会社経由で数日かかることがあります。

返金を確定できなかったときは、すぐに回数を戻します。`GROUP_UUID` と加算する回数は手順 3 と同じ値です。

```sql
BEGIN;

UPDATE groups
   SET ai_pack_remaining = ai_pack_remaining + 10,
       updated_at = NOW()
 WHERE id = 'GROUP_UUID';

-- 手順 3 の減算前の値に戻っていることを確認する
SELECT id, ai_pack_remaining FROM groups WHERE id = 'GROUP_UUID';

COMMIT;
```

## 5. 問い合わせへの返信

返金した金額と、減った回数を伝えます。

プラン管理の請求履歴には、返金後もその行が残ります。アプリは完了済みの決済を履歴に出すだけで、返金済みかどうかは見ていません。入金の根拠は Square の返金です。
