---
name: client-colors
description: client（Next.js/React）のカラー設計ルール。tsx/ts の className や colorVariant、Tailwind カラークラスを編集・追加するときに適用する。
---

# Client カラールール

`client/src` 配下で色を指定するときは、`tailwind.config.js` のトークンに従う。標準 Tailwind の `gray-500` など番号付き gray や、未定義の `text-gray-dark` は使わない。

## 色の役割

| トークン | 用途 |
|---------|------|
| **primary.main** (#8A6A4E) | ボタン（保存・追加・購入等）、本文リンク、UI 状態（ナビ現在地・今日・選択枠）、操作アイコン |
| **primary.light / background** | ページ地、選択中の面、ホバー地 |
| **gray** | キャンセル・副次ボタン、補助テキスト、補助アイコン |
| **alert** | 削除ボタン、エラーメッセージ |
| **accent** | **「おすすめ」バッジのみ** |
| **secondary** | 装飾のみ。**ボタン・操作要素には使わない** |
| **black** | 見出し・本文、戻る/閉じる/メニューアイコン |
| **white** | ボタン上の文字・アイコン、カード背景 |

## ボタンの colorVariant

`Button` / `ButtonLink` / `TextButton` / `HeaderTextButton` は次の 3 値のみ:

- **primary**（デフォルト）: 結果を生むアクション（保存・追加・送信・購入・登録・確認）
- **gray**: キャンセル・副次操作
- **alert**: 削除・破壊的操作

```tsx
// ✅ 保存ボタン（デフォルトで primary）
<Button type="submit">保存する</Button>

// ✅ キャンセル
<Button colorVariant={COLOR_VARIANT.GRAY}>キャンセル</Button>

// ✅ 削除確認
<Button colorVariant={COLOR_VARIANT.ALERT}>削除する</Button>
```

## 使い分けの判断基準

- **primary**: 押すとデータが作られる・変わる（ダイアログ内外を問わない）
- **gray**: 何も起こらず閉じる・副次
- **alert**: 消える・破壊的操作
- **コンテキスト内ナビ**（月送り・ページネーション）: `primary.main` のアイコン/テキスト。ボタンコンポーネントは使わない
- **accent**: LP や PackPurchase の「おすすめ」バッジ（`Label` の `ACCENT`）のみ

## 禁止・注意

```tsx
// ❌ ボタンに secondary / accent
<Button colorVariant={COLOR_VARIANT.SECONDARY} />
<TextButton colorVariant={COLOR_VARIANT.ACCENT} />

// ❌ accent をリンク・背景・枠に
<a className="text-accent-main">詳細</a>
<div className="bg-accent-background" />

// ❌ 未定義クラス
<h2 className="text-gray-800" />
<p className="text-gray-500" />

// ✅ 本文リンク
<Link className="text-primary-main underline">利用規約</Link>

// ✅ おすすめバッジ
<Label label="おすすめ" colorVariant={COLOR_VARIANT.ACCENT} />
```

## チェックポイント

- [ ] ボタンは primary / gray / alert のみ
- [ ] 緑（secondary）はボタン・操作要素に使っていない
- [ ] 赤（accent）は「おすすめ」バッジ以外に使っていない（alert の削除・エラーは除く）
- [ ] 本文リンクは `text-primary-main underline`
- [ ] 定義済みトークン（`gray-main`, `gray-background` 等）を使っている
