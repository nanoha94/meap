import { useCallback } from 'react';
import { arrayMove } from '@dnd-kit/sortable';
import { IShoppingItem } from '@/types/api';
import { useShoppingStore } from '@/stores';

export const useShoppingListLogic = () => {
    const {
        items,
        setItems,
        setActiveId,
        activeId,
        isShowLoading,
        setIsShowLoading,
    } = useShoppingStore();

    const categoryIdFromItemId = useCallback(
        (itemId: string) => {
            return items.find(v => v.items.some(item => item.id === itemId))
                ?.category.id;
        },
        [items],
    );

    // アイテムの更新ロジック
    const updateItem = useCallback(
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
                setItems(updatedItems);
            }
        },
        [items, categoryIdFromItemId, setItems],
    );

    // アイテムの削除ロジック
    const deleteItem = useCallback(
        (itemId: string) => {
            const updatedItems = items.map(v => ({
                ...v,
                items: v.items.filter(item => item.id !== itemId),
            }));
            setItems(updatedItems);
        },
        [items, setItems],
    );

    // アイテムの移動ロジック
    const moveItem = useCallback(
        (activeId: string, overId: string) => {
            if (activeId === overId) return;

            const overCategoryItemId = categoryIdFromItemId(overId);
            const overCategoryInfo = items?.find(
                v => v.category.id === overId,
            )?.category;

            // overIdがitemかcategoryかを判断（シンプルな真偽値）
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
                setItems(updatedItems);
            }
        },
        [items, categoryIdFromItemId, setItems],
    );

    return {
        items,
        updateItem,
        deleteItem,
        moveItem,
        setActiveId,
        activeId,
        setIsShowLoading,
        isShowLoading,
        setItems,
    };
};
