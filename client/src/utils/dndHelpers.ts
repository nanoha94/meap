/**
 * ドラッグ中のアイテムのインデックスを取得
 * @param activeId ドラッグ中のアイテムID
 * @param itemIdKey 検索に使うプロパティキー（省略時は 'id'）
 * @returns { activeIndex: number; activeItem: T | undefined } ドラッグ中のアイテムのインデックスとアイテム
 */
export const getDragActiveItem = <TItem extends { id: string }>(
    currentItems: TItem[],
    activeId: string,
    itemIdKey?: keyof TItem,
): { activeIndex: number; activeItem: TItem | undefined } => {
    const key = (itemIdKey ?? 'id') as keyof TItem;
    // ドラッグ中のアイテムとドロップ先のアイテムを取得
    const activeItem = currentItems.find(
        item => (item as Record<string, unknown>)[key as string] === activeId,
    );
    // ドラッグ中のアイテムが見つからない場合、処理を終了
    if (!activeItem) return { activeIndex: -1, activeItem: undefined };

    // ドラッグ中のアイテムのインデックスを取得
    const activeIndex = currentItems.indexOf(activeItem);

    return { activeIndex, activeItem };
};

/**
 * ドロップ先のアイテムのインデックスとカテゴリーIDを取得
 * @param overId ドロップ先のID
 * @param itemIdKey 検索に使うプロパティキー（省略時は 'id'）
 * @returns { overIndex: number; overCategoryId: string } ドロップ先のアイテムのインデックスとカテゴリーID
 */
export const getDragOverItem = <
    TItem extends { id: string; categoryId: string },
    TCategory extends { id: string },
>(
    currentItems: TItem[],
    categories: TCategory[],
    overId: string,
    itemIdKey?: keyof TItem,
): { overIndex: number; overCategoryId: string } => {
    const key = (itemIdKey ?? 'id') as keyof TItem;
    // ドロップ先がアイテムかカテゴリーかを判断
    const isOverItem: boolean = currentItems.some(
        v => (v as Record<string, unknown>)[key as string] === overId,
    );

    // ドロップ先がアイテムの場合
    if (isOverItem) {
        // ドロップ先のアイテムを取得
        const overItem: TItem | undefined = currentItems.find(
            item => (item as Record<string, unknown>)[key as string] === overId,
        );
        // ドロップ先のアイテムが見つからない場合、処理を終了
        if (!overItem) return { overIndex: -1, overCategoryId: '' };
        // ドロップ先のカテゴリーIDを取得
        const overCategoryId: string = overItem.categoryId;
        // ドロップ先のアイテムのインデックスを取得
        const overIndex: number = currentItems.indexOf(overItem);
        return { overIndex, overCategoryId };
    }
    // ドロップ先がカテゴリーの場合
    else {
        // カテゴリーごとのアイテム数を取得
        const itemCountsInCategory = categories.map(v => ({
            id: v.id,
            count: currentItems.filter(item => item.categoryId === v.id).length,
        }));
        // ドロップ先のカテゴリーを取得
        const overCategory: TCategory | undefined = categories.find(
            v => v.id === overId,
        );
        // ドロップ先のカテゴリーが見つからない場合、処理を終了
        if (!overCategory) return { overIndex: -1, overCategoryId: '' };
        // ドロップ先のカテゴリーIDを取得
        const overCategoryId: string = overCategory.id;
        // ドロップ先のカテゴリーのアイテム数を取得
        const overIndex: number = itemCountsInCategory.reduce(
            (acc, current) => {
                // 該当カテゴリーIDが見つかったら、それまでの累積値を返す
                if (current.id === overCategoryId) {
                    return acc;
                }
                // 該当カテゴリーIDが見つかるまで、countを累積
                return acc + current.count;
            },
            0,
        );
        return { overIndex, overCategoryId };
    }
};

/**
 * IDでアイテムを取得
 * @param items アイテムの配列
 * @param itemId アイテムID
 * @param itemIdKey 検索に使うプロパティキー（省略時は 'id'）
 * @returns 見つかったアイテム、見つからない場合はundefined
 */
export const getItemById = <TItem extends { id: string }>(
    items: TItem[],
    itemId: string | null,
    itemIdKey?: keyof TItem,
): TItem | undefined => {
    if (!itemId) return undefined;
    const key = (itemIdKey ?? 'id') as keyof TItem;
    return items.find(
        item => (item as Record<string, unknown>)[key as string] === itemId,
    );
};

/**
 * アイテムIDからカテゴリーを取得
 * @param items アイテムの配列
 * @param itemId アイテムID
 * @param categories カテゴリーの配列
 * @param itemIdKey 検索に使うプロパティキー（省略時は 'id'）
 * @returns 見つかったカテゴリー、見つからない場合はundefined
 */
export const getCategoryByItemId = <
    TItem extends { id: string; categoryId: string },
    TCategory extends { id: string },
>(
    items: TItem[],
    itemId: string | null,
    categories: TCategory[],
    itemIdKey?: keyof TItem,
): TCategory | undefined => {
    const item = getItemById(items, itemId, itemIdKey);
    if (!item) return undefined;
    return categories.find(category => category.id === item.categoryId);
};

/**
 * カテゴリーに属するアイテムを取得
 * @param array アイテムの配列
 * @param categoryId カテゴリーID
 * @returns カテゴリーに属するアイテムの配列
 */
export const getItemsInCategory = <TItem extends { categoryId: string }>(
    array: TItem[],
    categoryId: string,
): TItem[] => {
    return array.filter(item => item.categoryId === categoryId);
};

/**
 * カテゴリーに属するアイテムの最後の直後インデックスを取得（フォーム全体の配列での位置）
 * @param array アイテムの配列
 * @param categories カテゴリーの配列
 * @param categoryId カテゴリーID
 * @returns カテゴリーに属するアイテムの最後の直後インデックス
 */
export const getInsertIndexForCategory = <TItem extends { categoryId: string }, TCategory extends { id: string }>(
    array: TItem[],
    categories: TCategory[],
    categoryId: string,
): number => {
    const lastInCategory = array.reduce<number>(
        (lastIdx, meal, idx) => (meal.categoryId === categoryId ? idx : lastIdx),
        -1,
    );
    if (lastInCategory >= 0) return lastInCategory + 1;
    // カテゴリに1件もない場合は、カテゴリ順でそのカテゴリが始まる位置を算出
    const categoryIndex = categories.findIndex(c => c.id === categoryId);
    return categories.slice(0, categoryIndex).reduce(
        (sum, c) => sum + array.filter(m => m.categoryId === c.id).length,
        0,
    );
};