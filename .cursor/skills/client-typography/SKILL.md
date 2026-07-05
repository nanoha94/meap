---
name: client-typography
description: client（Next.js/React）の Tailwind タイポグラフィルール。tsx/tsx の className を編集・追加するとき、font-semibold の使用可否や font-weight の選び方に迷ったときに適用する。
---

# Client タイポグラフィルール

`client/src` 配下の `.tsx` / `.ts` で Tailwind の **font-weight** を指定するときは以下に従う。

## font-semibold は使わない

- **`font-semibold`（font-weight: 600）を className に追加しない**
- 新規コード・既存コードの修正どちらでも、`font-semibold` は **`font-bold` または font-weight なし** に置き換える

### 理由

- プロジェクトフォント（Noto Sans JP）は **400 / 700 のみ** を読み込んでいる（`constants/fonts.ts`）
- `font-semibold`（600）は Web フォントに存在しないため、ブラウザによる **疑似ボールド** になり表示がぶれる

### 置き換え

| 意図 | 使うクラス |
|------|-----------|
| 通常テキスト | 指定なし（`font-normal` 相当） |
| 強調・見出し・ラベル | `font-bold` |

```tsx
// ❌
<p className="text-sm font-semibold">解約予定</p>

// ✅
<p className="text-sm font-bold">解約予定</p>
```

## チェックポイント

- [ ] `font-semibold` を追加・残していない
- [ ] 強調には `font-bold` を使っている
- [ ] 見出し・ラベル・数値の強調も `font-bold` で統一している
