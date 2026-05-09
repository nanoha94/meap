---
name: client-import-rules
description: client（Next.js/React）のインポート順・フォーマットルール。tsx/ts の先頭の 'use client' と import を編集・追加するときに適用する。
---

# Client インポートルール

`client/src` 配下の `.tsx` / `.ts` で、ファイル先頭の **ディレクティブと import の並び** は以下に従う。

## 順序

1. **ディレクティブ**（該当する場合のみ）
   - `'use client';` は 1 行目。シングルクォートで記述する。

2. **空行 1 行**

3. **外部インポート**（`node_modules` 由来）
   - 先頭は **React**：`import React from 'react';` または `import { ... } from 'react';`
   - それ以外は **パス・モジュール名の ABC 順**

4. **空行 1 行**

5. **内部インポート**（`@/` や相対パス）
   - **ABC 順**（パスまたはエイリアスで比較）

## 例（参考: IngredientCategoryEditForm.tsx）

```tsx
'use client';

import React from 'react';
import { CirclePlus } from 'lucide-react';
import { Controller, useFieldArray, useForm } from 'react-hook-form';

import {
    Button,
    DndSortableList,
    GrippableHorizontalItem,
    TextButton,
} from '@/components';
import { BUTTON_TYPE, BUTTON_VARIANT, COLOR_VARIANT, DND_SORTABLE_LIST_TYPE, TMP_ID_PREFIX } from '@/constants';
import { useDialog } from '@/hooks';
import { defaultIngredientCategory, useIngredientCategoryApi } from '@/models/ingredient';
import { IIngredientCategory } from '@/types';
```

## チェックポイント

- [ ] `'use client';` は 1 行目でシングルクォート
- [ ] ディレクティブの直後に空行が 1 行
- [ ] 外部 import の先頭が React、以降は ABC 順
- [ ] 外部と内部の間に空行が 1 行
- [ ] 内部 import は ABC 順（`@/components` → `@/constants` → …）
