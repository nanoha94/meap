'use client';
import React from 'react';
import { DndContext, DragOverlay, rectIntersection } from '@dnd-kit/core';
import { arrayMove } from '@dnd-kit/sortable';
import CategoryItemList from './CategoryItemList';
import ShoppingItemCard from './ShoppingItemCard';

import { useDebounce, useItemAndCategoryDnd } from '@/hooks';
import { IShoppingItem } from '@/types';
import { getItemsInCategory } from '@/utils';
import { DEBOUNCE_DELAY } from '../../constants';
import { useShoppingItemApi, useShoppingStore } from '../../hooks';

const ShoppingList = () => {
    const {
        setItems: setStoreItems,
        items: storeItems,
        serverItems,
        categories,
    } = useShoppingStore();
    const { updateShoppingItems } = useShoppingItemApi();
    const [tmpItems, setTmpItems] = React.useState<IShoppingItem[]>([]);

    // 最後に送信したアイテムを記録して重複実行を防ぐ
    const lastSentItemsRef = React.useRef<string>('');

    // デバウンス処理
    const debouncedItems = useDebounce(storeItems, DEBOUNCE_DELAY);

    /**
     * ドラッグオーバー
     */
    const customHandleDragOver = React.useCallback(
        (
            activeId: string,
            activeItem: IShoppingItem,
            overCategoryId: string,
        ) => {
            // 別カテゴリーへの移動の場合
            if (activeItem.categoryId !== overCategoryId) {
                // tmpItemsを更新
                setTmpItems(
                    tmpItems.map(v =>
                        v.id === activeId
                            ? {
                                ...activeItem,
                                categoryId: overCategoryId,
                            }
                            : v,
                    ),
                );
            }
        },
        [tmpItems, categories],
    );

    /**
     * ドラッグ終了
     */
    const customHandleDragEnd = React.useCallback(
        (activeIndex: number | undefined, overIndex: number | undefined) => {
            // 並び替えたtmpItemsを更新
            const array =
                activeIndex !== undefined && overIndex !== undefined
                    ? arrayMove(tmpItems, activeIndex, overIndex)
                    : tmpItems;
            setStoreItems(array);
        },
        [tmpItems],
    );

    const {
        activeId,
        sensors,
        activeItem,
        handleDragStart,
        handleDragOver,
        handleDragEnd,
    } = useItemAndCategoryDnd({
        currentItems: tmpItems,
        categories,
        onDragOver: customHandleDragOver,
        onDragEnd: customHandleDragEnd,
    });

    /**
     * デバウンス処理
     */
    React.useEffect(() => {
        // debouncedItems が undefined の場合（初期化前）は何もしない
        if (debouncedItems && !activeId) {
            const currentItemsStr = JSON.stringify(debouncedItems);
            // serverItemsからフラット化して比較
            const serverItemsStr = JSON.stringify(serverItems);

            // アイテムの比較と更新処理を直接実行
            if (
                debouncedItems.length > 0 &&
                currentItemsStr !== serverItemsStr &&
                currentItemsStr !== lastSentItemsRef.current // 重複送信防止
            ) {
                lastSentItemsRef.current = currentItemsStr; // 送信するアイテムを記録
                const updateItems = debouncedItems.map((item, idx) => ({
                    ...item,
                    order: idx,
                }));
                updateShoppingItems(updateItems);
            }
        }
    }, [debouncedItems, activeId]);

    /**
     * アンマウント・ページアンロード時の保存処理
     */
    React.useEffect(() => {
        const handleBeforeUnload = () => {
            const updateItems = storeItems.map((item, idx) => ({
                ...item,
                order: idx,
            }));
            updateShoppingItems(updateItems);

        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            // アンマウント時の保存処理
            const updateItems = storeItems.map((item, idx) => ({
                ...item,
                order: idx,
            }));
            updateShoppingItems(updateItems);
        };
    }, []); // 依存配列を空にして、マウント時に一度だけ実行

    /**
     * ドラッグ中でない場合、tmpItemsをstoreItemsの内容で更新
     * @returns void
     */
    React.useEffect(() => {
        if (!activeId) {
            setTmpItems(storeItems);
        }
    }, [storeItems, activeId]);

    return (
        <div className="flex flex-col gap-y-7">
            <DndContext
                sensors={sensors}
                collisionDetection={rectIntersection}
                onDragStart={handleDragStart}
                onDragEnd={handleDragEnd}
                onDragOver={handleDragOver}>
                {categories.map(category => {
                    const items = getItemsInCategory(tmpItems, category.id);
                    const itemsKey = items.map(item => item.id).join(',');
                    return (
                        <CategoryItemList
                            key={`${category.id}-${itemsKey}`}
                            category={category}
                            items={items}
                        />
                    );
                })}
                <DragOverlay>
                    {activeItem && <ShoppingItemCard item={activeItem} />}
                </DragOverlay>
            </DndContext>
        </div>
    );
};

export default ShoppingList;
