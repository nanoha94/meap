import { useCallback } from 'react';
import { arrayMove } from '@dnd-kit/sortable';
import { IShoppingItem } from '@/types/api';
import { useShoppingStore } from './stores';

export const useShoppingHandlers = () => {
    const { items, setItems } = useShoppingStore();

    const categoryIdFromItemId = useCallback(
        (itemId: string) => {
            return items.find(v => v.items.some(item => item.id === itemId))
                ?.category.id;
        },
        [items],
    );

    // アイテムの更新処理
    const handleUpdateItem = useCallback(
        (item: IShoppingItem) => {
            const { id, name, isPinned, isChecked, order } = item;
            const categoryId = categoryIdFromItemId(id);

            if (categoryId) {
                const updatedItems = items.map(v => ({
                    ...v,
                    items: v.items.map(item =>
                        item.id === id
                            ? { ...item, name, isPinned, isChecked, order }
                            : item,
                    ),
                }));
                // ストアデータを更新
                setItems(updatedItems);
            }
        },
        [items],
    );

    // アイテムの削除ロジック
    const handleDeleteItem = useCallback(
        (itemId: string) => {
            const updatedItems = items.map(v => ({
                ...v,
                items: v.items.filter(item => item.id !== itemId),
            }));
            // ストアデータを更新
            setItems(updatedItems);
        },
        [items],
    );

    // アイテムの移動ロジック
    const handleMoveItem = useCallback(
        (activeId: string, overId: string) => {
            if (activeId === overId) return;

            const overCategoryItemId = categoryIdFromItemId(overId);
            const overCategoryInfo = items?.find(
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
            const activeCategory = items.find(
                v => v.category.id === activeCategoryId,
            );
            const overCategory = items.find(
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
                    const removedActiveItems = items.map(v => ({
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
                updatedItems = items.map(v => {
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
                setItems(updatedItems);
            }
        },
        [items],
    );

    return {
        handleUpdateItem,
        handleDeleteItem,
        handleMoveItem,
    };
};
