---
name: client-file-structure
description: client（Next.js/React）の tsx/ts ファイル構成ルール。ページやコンポーネントファイルを編集・新規作成するとき、export default の位置やサブコンポーネントの記述順に迷ったときに適用する。
---

# Client ファイル構成ルール

`client/src` 配下の `.tsx` / `.ts` で、**1 ファイルにメインの export default とサブコンポーネントが同居する場合** は以下に従う。

## 順序

1. **ディレクティブ**（該当する場合のみ）
2. **import**
3. **定数・型・データ**（ページ固有の配列、設定値など）
4. **メインコンポーネント**（`export default` するコンポーネント）
5. **`export default`**
6. **サブコンポーネント**（メインからのみ使う補助コンポーネント）
7. **サブコンポーネント専用の定数・型**（上記サブコンポーネントの直下または直前）

## 原則

- **`export default` はファイル内で最優先の export 位置** とする。サブコンポーネントより上に置く。
- **サブコンポーネントは `export default` の下** に記述する。
- サブコンポーネント専用の定数・型は、原則として **そのサブコンポーネントの近く**（同ブロック内）に置く。

## 例

```tsx
'use client';

import React from 'react';

import { Button } from '@/components';

const ITEMS = ['a', 'b'] as const;

const Page = () => (
    <ul>
        {ITEMS.map((item) => (
            <ListItem key={item}>{item}</ListItem>
        ))}
    </ul>
);

export default Page;

type ListItemProps = {
    children: React.ReactNode;
};

const ListItem = ({ children }: ListItemProps) => <li>{children}</li>;
```

## チェックポイント

- [ ] `export default` がサブコンポーネントより上にある
- [ ] サブコンポーネントを `export default` の上に置いていない
- [ ] ページ固有データ・メインコンポーネントは `export default` より上にある
