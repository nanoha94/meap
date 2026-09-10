---
name: client-naming
description: client（Next.js/React）の命名ルール。boolean を返す関数・変数の is プレフィックスなど、ts/tsx の名前付けに迷ったときに適用する。
---

# Client 命名規約

## boolean を返す関数は `is` プレフィックス

**真偽値を返す関数** は `is` で始める。`requires` / `has` / `can` など動詞始まりにしない。

| 避ける | 推奨 |
|---|---|
| `pathnameRequiresSessionInAuthLayout()` | `isSessionRequiredPathInAuthLayout()` |
| `requiresAuth()` | `isAuthRequired()` |

- 引数を判定する関数: `is` + 判定内容（例: `isSafeRedirectPath`, `isAllowedStripeUrl`）
- 状態を表す変数・引数も同様: `isAuthPage`, `isLoading`

## 既存コードとの関係

既存の `hasAuthCookie` など、文脈上 `has` が自然なものは触らない限りそのまま残してよい。**新規追加・リネーム時** に本ルールを適用する。
