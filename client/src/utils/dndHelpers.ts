/**
 * ドラッグ中のアイテムのインデックスを取得
 * @param activeId ドラッグ中のアイテムID
 * @returns { activeIndex: number; activeItem: T | undefined } ドラッグ中のアイテムのインデックスとアイテム
 */
export const getDragActiveItem = <TItem extends { id: string }>(
    currentItems: TItem[],
    activeId: string,
): { activeIndex: number; activeItem: TItem | undefined } => {
    // ドラッグ中のアイテムとドロップ先のアイテムを取得
    const activeItem = currentItems.find(item => item.id === activeId);
    // ドラッグ中のアイテムが見つからない場合、処理を終了
    if (!activeItem) return { activeIndex: -1, activeItem: undefined };

    // ドラッグ中のアイテムのインデックスを取得
    const activeIndex = currentItems.indexOf(activeItem);

    return { activeIndex, activeItem };
};

/**
 * ドロップ先のアイテムのインデックスとカテゴリーIDを取得
 * @param overId ドロップ先のID
 * @returns { overIndex: number; overCategoryId: string } ドロップ先のアイテムのインデックスとカテゴリーID
 */
export const getDragOverItem = <
    TItem extends { id: string; categoryId: string },
    TCategory extends { id: string },
>(
    currentItems: TItem[],
    categories: TCategory[],
    overId: string,
): { overIndex: number; overCategoryId: string } => {
    // ドロップ先がアイテムかカテゴリーかを判断
    const isOverItem: boolean = currentItems.some(v => v.id === overId);

    // ドロップ先がアイテムの場合
    if (isOverItem) {
        // ドロップ先のアイテムを取得
        const overItem: TItem | undefined = currentItems.find(
            item => item.id === overId,
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
 * @returns 見つかったアイテム、見つからない場合はundefined
 */
export const getItemById = <TItem extends { id: string }>(
    items: TItem[],
    itemId: string | null,
): TItem | undefined => {
    if (!itemId) return undefined;
    return items.find(item => item.id === itemId);
};

/**
 * アイテムIDからカテゴリーを取得
 * @param items アイテムの配列
 * @param itemId アイテムID
 * @param categories カテゴリーの配列
 * @returns 見つかったカテゴリー、見つからない場合はundefined
 */
export const getCategoryByItemId = <
    TItem extends { id: string; categoryId: string },
    TCategory extends { id: string },
>(
    items: TItem[],
    itemId: string | null,
    categories: TCategory[],
): TCategory | undefined => {
    const item = getItemById(items, itemId);
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
