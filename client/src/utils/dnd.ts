import { ICategoryWithItems } from '@/types/api/common';
import { IDndMoveReturnData, IDragOverTargetReturnData } from '@/types/dnd';
import { arrayMove } from '@dnd-kit/sortable';

/**
 * アイテムIDからカテゴリーIDを取得
 * @param currentData アイテムを持つ現在のカテゴリーのリスト
 * @param itemId アイテムID
 * @returns カテゴリーID
 */
export const getCategoryIdFromItemId = <
    C extends { id: string },
    I extends { id: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    itemId: string,
) => {
    return currentDatasets.find(v => v.items.some(item => item.id === itemId))
        ?.category.id;
};

/**
 * アイテムIDからカテゴリーインデックスとアイテムインデックスを取得
 * @param currentDatasets アイテムを持つ現在のカテゴリーのリスト
 * @param itemId アイテムID
 * @returns カテゴリーインデックスとアイテムインデックス、見つからない場合はnull
 */
export const getItemIndexes = <
    C extends { id: string },
    I extends { id: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    itemId: string,
): { categoryIndex: number; itemIndex: number } | null => {
    // カテゴリーIDを取得
    const categoryId = getCategoryIdFromItemId(currentDatasets, itemId);
    if (!categoryId) return null;

    // カテゴリーインデックスを取得
    const categoryIndex = currentDatasets.findIndex(
        dataset => dataset.category.id === categoryId,
    );
    if (categoryIndex === -1) return null;

    // アイテムインデックスを取得
    const itemIndex = currentDatasets[categoryIndex].items.findIndex(
        item => item.id === itemId,
    );
    if (itemIndex === -1) return null;

    return { categoryIndex, itemIndex };
};

/**
 * 複数のアイテムをカテゴリー内で一括更新
 * @param currentCategories アイテムを持つ現在のカテゴリーのリスト
 * @param itemsToUpdate 更新対象のアイテムの配列
 * @returns 更新後のアイテムを持つカテゴリーの配列
 */
export const updateItemsInCategories = <
    C extends { id: string },
    I extends { id: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    itemsToUpdate: I[],
): ICategoryWithItems<C, I>[] => {
    const updatedItemsMap = new Map(itemsToUpdate.map(item => [item.id, item]));

    const updatedCategories = currentDatasets.map(v => ({
        ...v,
        items: v.items.map(item =>
            updatedItemsMap.has(item.id)
                ? { ...item, ...updatedItemsMap.get(item.id) }
                : item,
        ),
    }));
    return updatedCategories;
};

/**
 * ドラッグ&ドロップ移動のロジックを処理する総合ヘルパー関数
 * @param currentDatasets アイテムを持つ現在のカテゴリーのリスト
 * @param activeId ドラッグ中のアイテムID
 * @param overId ドロップ先の要素ID (アイテムIDまたはカテゴリーID)
 * @returns 更新後のアイテムを持つカテゴリーの配列、または処理が失敗した場合は元の配列
 */
export const processDndMove = <
    C extends { id: string },
    I extends { id: string; categoryId: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    activeId: string,
    overId: string,
): ICategoryWithItems<C, I>[] => {
    if (activeId === overId) return currentDatasets;

    const moveData = getDndMoveData(currentDatasets, activeId, overId);

    if (!moveData) {
        console.error('processDndMove: DND移動データが見つかりませんでした。');
        return currentDatasets;
    }

    const { activeDataset, overDataset, activeItem, isOverItem } = moveData;

    // インデックス計算ロジックをヘルパー関数に抽出
    const { activeIndex, overIndex } = getDndItemIndexes(
        activeDataset,
        overDataset,
        activeId,
        overId,
        isOverItem,
    );

    // overIndex の妥当性チェック
    if (isOverItem && overIndex === -1) {
        console.error(
            'processDndMove: ドロップ先のアイテムが見つかりませんでした。\n (overId はアイテムIDとして指定されたが、overCategory 内に存在しません)',
            {
                overId,
                overCategoryId: overDataset.category.id,
                overDataset,
                overIndex,
            },
        );
        return currentDatasets;
    }

    let updatedItems = [...currentDatasets];

    // 別カテゴリーでの入れ替え
    if (activeDataset.category.id !== overDataset.category.id) {
        updatedItems = moveItemBetweenCategories(
            updatedItems,
            activeId,
            activeDataset.category.id,
            activeItem,
            overDataset.category.id,
            overIndex,
        );
    }
    // 同カテゴリーでの入れ替え
    else {
        updatedItems = moveItemInSameCategory(
            updatedItems,
            activeDataset.category.id,
            activeIndex,
            overIndex,
        );
    }

    return updatedItems;
};

/**
 * アイテムをカテゴリーから削除
 * @param currentCategories アイテムを持つ現在のカテゴリーのリスト
 * @param categoryId 削除対象カテゴリーID
 * @param itemId 削除対象アイテムID
 * @returns 更新後のアイテムを持つカテゴリーの配列
 */
const removeItemFromCategory = <
    C extends { id: string },
    I extends { id: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    categoryId: string,
    itemId: string,
): ICategoryWithItems<C, I>[] => {
    return currentDatasets.map(v => {
        if (v.category.id === categoryId) {
            return {
                ...v,
                items: v.items.filter(item => item.id !== itemId),
            };
        }
        return v;
    });
};

/**
 * アイテムをカテゴリーに追加
 * @param currentCategories アイテムを持つ現在のカテゴリーのリスト
 * @param categoryId 追加対象カテゴリーID
 * @param item 追加アイテム
 * @param index 追加位置インデックス
 * @returns 更新後のアイテムを持つカテゴリーの配列
 */
const insertItemIntoCategory = <
    C extends { id: string },
    I extends { id: string; categoryId: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    categoryId: string,
    item: I,
    index: number,
): ICategoryWithItems<C, I>[] => {
    return currentDatasets.map(v => {
        if (v.category.id === categoryId) {
            const newItems = [...v.items];
            newItems.splice(index, 0, { ...item, categoryId: categoryId });
            return {
                ...v,
                items: newItems,
            };
        }
        return v;
    });
};

/**
 * ドラッグオーバーしているターゲットの情報を取得
 * @param currentData アイテムを持つ現在のカテゴリーのリスト
 * @param overId オーバーしている要素のID
 * @returns ドラッグオーバーターゲット情報
 */
const getDragOverTargetInfo = <
    C extends { id: string },
    I extends { id: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    overId: string,
): IDragOverTargetReturnData => {
    const overCategoryId = getCategoryIdFromItemId(currentDatasets, overId);
    const overCategoryInfo = currentDatasets?.find(
        v => v.category.id === overId,
    )?.category;

    const isOverItem = !!overCategoryId;
    const isOverCategory = !!overCategoryInfo;

    return {
        isOverItem,
        overCategoryId: isOverCategory
            ? overId
            : isOverItem
              ? overCategoryId
              : null,
    };
};

/**
 * カテゴリー間でアイテムを移動
 * @param currentData アイテムを持つ現在のカテゴリーのリスト
 * @param activeId 移動対象アイテムID
 * @param activeCategoryId 移動対象アイテムのカテゴリーID
 * @param activeItem 移動対象アイテム
 * @param overCategoryId 移動先カテゴリーID
 * @param overIndex 移動先インデックス
 * @returns 更新後のアイテムを持つカテゴリーの配列
 */
const moveItemBetweenCategories = <
    C extends { id: string },
    I extends { id: string; categoryId: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    activeId: string,
    activeCategoryId: string,
    activeItem: I,
    overCategoryId: string,
    overIndex: number,
): ICategoryWithItems<C, I>[] => {
    // 元のカテゴリーからアイテムを削除
    let updatedCategories = removeItemFromCategory(
        currentDatasets,
        activeCategoryId,
        activeId,
    );

    // 新しいカテゴリーにアイテムを挿入
    updatedCategories = insertItemIntoCategory(
        updatedCategories,
        overCategoryId,
        activeItem,
        overIndex,
    );

    return updatedCategories;
};

/**
 * ドラッグ&ドロップ移動に必要な情報を取得する
 * @param currentData アイテムを持つ現在のカテゴリーのリスト
 * @param activeId ドラッグ中のアイテムID
 * @param overId ドロップ先の要素ID (アイテムIDまたはカテゴリーID)
 * @returns 移動に必要な情報
 */
const getDndMoveData = <
    C extends { id: string },
    I extends { id: string; categoryId: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    activeId: string,
    overId: string,
): IDndMoveReturnData<C, I> | null => {
    const activeCategoryId = getCategoryIdFromItemId(currentDatasets, activeId);
    const { isOverItem, overCategoryId } = getDragOverTargetInfo(
        currentDatasets,
        overId,
    );

    if (!activeCategoryId || !overCategoryId) {
        console.error(
            'getDndMoveData: activeCategoryId または overCategoryId が見つかりませんでした。',
            { activeId, overId, activeCategoryId, overCategoryId },
        );
        return null;
    }

    const activeDataset = currentDatasets.find(
        v => v.category.id === activeCategoryId,
    );
    const overDataset = currentDatasets.find(
        v => v.category.id === overCategoryId,
    );

    if (!activeDataset || !overDataset) {
        console.error(
            'getDndMoveData: activeCategory または overCategory が見つかりませんでした。',
            { activeId, overId, activeCategoryId, overCategoryId },
        );
        return null;
    }

    const activeItem = activeDataset.items.find(v => v.id === activeId);
    if (!activeItem) {
        console.error(
            'getDndMoveData: activeItem が activeCategory に見つかりませんでした。',
            {
                activeId,
                activeCategoryId,
            },
        );
        return null;
    }

    return {
        activeDataset,
        overDataset,
        activeItem,
        isOverItem,
    };
};

/**
 * DND移動におけるアイテムのインデックス情報を取得
 * @param activeCategory ドラッグ中のアイテムが属するカテゴリーオブジェクト
 * @param overCategory ドロップ先のカテゴリーオブジェクト
 * @param activeId ドラッグ中のアイテムID
 * @param overId ドロップ先の要素ID (アイテムIDまたはカテゴリーID)
 * @param isOverItem `overId` がアイテムのIDであるか
 * @returns { activeIndex: number, overIndex: number } アイテムのインデックス情報
 */
const getDndItemIndexes = <
    C extends { id: string },
    I extends { id: string; categoryId: string },
>(
    activeDataset: ICategoryWithItems<C, I>,
    overDataset: ICategoryWithItems<C, I>,
    activeId: string,
    overId: string,
    isOverItem: boolean,
): { activeIndex: number; overIndex: number } => {
    const activeIndex = activeDataset.items.findIndex(v => v.id === activeId);

    const overIndex = isOverItem
        ? overDataset.items.findIndex(v => v.id === overId)
        : overDataset.items.length;

    return { activeIndex, overIndex };
};

/**
 * 同一カテゴリー内でアイテムを並び替える
 * @param currentData アイテムを持つ現在のカテゴリーのリスト
 * @param categoryId 並び替えを行うカテゴリーID
 * @param activeIndex ドラッグ中のアイテムのインデックス
 * @param overIndex ドロップ先のインデックス
 * @returns 更新後のアイテムを持つカテゴリーの配列
 */
const moveItemInSameCategory = <
    C extends { id: string },
    I extends { id: string },
>(
    currentDatasets: ICategoryWithItems<C, I>[],
    categoryId: string,
    activeIndex: number,
    overIndex: number,
): ICategoryWithItems<C, I>[] => {
    return currentDatasets.map(v => {
        if (v.category.id === categoryId) {
            return {
                ...v,
                items: arrayMove(v.items, activeIndex, overIndex),
            };
        }
        return v;
    });
};
