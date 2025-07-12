import React from 'react';
import { arrayMove } from '@dnd-kit/sortable';
import { IShoppingItem } from '@/types/api';
import { useShoppingStore } from './stores';

export const useShoppingHandlers = () => {
    const { items: storeItems, setItems: setStoreItems } = useShoppingStore();

    // アイテムIDからカテゴリーIDを取得
    const categoryIdFromItemId = React.useCallback(
        (itemId: string) => {
            return storeItems.find(v =>
                v.items.some(item => item.id === itemId),
            )?.category.id;
        },
        [storeItems],
    );

    // 個別アイテムの更新処理
    const handleUpdateItem = React.useCallback(
        (item: IShoppingItem) => {
            const { id, name, isPinned, isChecked, order } = item;
            const categoryId = categoryIdFromItemId(id);

            if (categoryId) {
                const updatedItems = storeItems.map(v => ({
                    ...v,
                    items: v.items.map(storeItem =>
                        storeItem.id === id
                            ? { ...storeItem, name, isPinned, isChecked, order }
                            : storeItem,
                    ),
                }));
                setStoreItems(updatedItems);
            }
        },
        [storeItems, categoryIdFromItemId, setStoreItems],
    );

    // 複数アイテムの一括更新処理
    const handleUpdateItems = React.useCallback(
        (items: IShoppingItem[]) => {
            const updatedItemsMap = new Map(items.map(item => [item.id, item]));

            const updatedItems = storeItems.map(v => ({
                ...v,
                items: v.items.map(item =>
                    updatedItemsMap.has(item.id)
                        ? { ...item, ...updatedItemsMap.get(item.id) }
                        : item,
                ),
            }));
            setStoreItems(updatedItems);
        },
        [storeItems, setStoreItems],
    );

    // アイテムの移動ロジック
    const handleMoveItem = React.useCallback(
        (activeId: string, overId: string) => {
            if (activeId === overId) return;

            const overCategoryItemId = categoryIdFromItemId(overId);
            const overCategoryInfo = storeItems?.find(
                v => v.category.id === overId,
            )?.category;

            // overIdがitemかcategoryかを判断
            const isOverItem = !!overCategoryItemId;
            const isOverCategory = !!overCategoryInfo;

            // カテゴリーIDを取得
            const activeCategoryId = categoryIdFromItemId(activeId);
            const overCategoryId = isOverCategory
                ? overId
                : isOverItem
                  ? overCategoryItemId
                  : null;

            if (!activeCategoryId && !overCategoryId) return;

            // カテゴリごとのインデックスを取得
            const activeCategory = storeItems.find(
                v => v.category.id === activeCategoryId,
            );
            const overCategory = storeItems.find(
                v => v.category.id === overCategoryId,
            );

            if (!activeCategory || !overCategory) return;

            const activeIndex = activeCategory.items.findIndex(
                v => v.id === activeId,
            );
            const overIndex = isOverItem
                ? overCategory.items.findIndex(v => v.id === overId)
                : 0;

            if (activeIndex === -1 || overIndex === -1) return;

            let updatedItems;

            // 別カテゴリ―での入れ替え
            if (activeCategoryId !== overCategoryId) {
                const activeItem = activeCategory.items[activeIndex];
                if (activeItem) {
                    const removedActiveItems = storeItems.map(v => ({
                        ...v,
                        items: v.items.filter(item => item.id !== activeId),
                    }));

                    updatedItems = removedActiveItems.map(v => {
                        if (v.category.id === overCategoryId) {
                            const newItems = [...v.items];
                            newItems.splice(overIndex, 0, {
                                ...activeItem,
                                categoryId: overCategoryId,
                            });
                            return {
                                ...v,
                                items: newItems,
                            };
                        }
                        return v;
                    });
                }
            }
            // 同カテゴリーでの入れ替え
            else {
                updatedItems = storeItems.map(v => {
                    if (v.category.id === activeCategoryId) {
                        return {
                            ...v,
                            items: arrayMove(v.items, activeIndex, overIndex),
                        };
                    }
                    return v;
                });
            }

            if (updatedItems) {
                // ストアデータを更新
                setStoreItems(updatedItems);
            }
        },
        [storeItems],
    );

    return {
        handleUpdateItem,
        handleUpdateItems,
        handleMoveItem,
    };
};
