---
name: server-naming
description: サーバーサイド（Laravel/PHP）の命名ルール。メソッド名・変数名の選び方に迷ったとき、resolve など曖昧な動詞の代替を検討するときに使用する。
---

# サーバーサイド 命名規約

## `resolve` は原則使わない

`resolve` は意味が広すぎる（依存解決・衝突解消・値の導出・URL 解決など）ため、**新規コードでは使わない**。

代わりに **何をしているか** が伝わる動詞を選ぶ。

| やっていること | 推奨動詞 | 例 |
|---|---|---|
| 入力から出力を組み立てる | `build` | `buildImageUrl($path)` |
| 形式を変換する | `to` / `format` | `toImageUrl($path)`, `formatImageSrc($path)` |
| 生成する（署名付き URL 等） | `generate` | `generateImageUrl($path)` |
| 取得する | `get` / `fetch` | `getImageUrl($path)` |
| 正規化する | `normalize` | `normalizeRemoteImageUrl($url)` |

## 画像 URL の例（ImageService）

DB の相対パス → API レスポンス用 URL への変換:

- **推奨**: `generateImageUrl(?string $path): ?string`
- メソッド名に `Signed` は**入れない** — s3 時は署名付き、public 時は公開 URL と返却形式が異なるため
- 署名付きであることは PHPDoc や呼び出し元のコメントで補足する

```php
// formatImage() から呼ぶ
'src' => $this->generateImageUrl($image->src),
```

## 既存コードとの関係

既存の `resolveQuantityPair`（Quantity.php）や Laravel フレームワーク由来の `resolve()` は、触らない限りそのまま残してよい。新規追加・リネーム時に本ルールを適用する。
