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
import { ShoppingListHandle } from '../../types';

const ShoppingList = React.forwardRef<ShoppingListHandle, object>((_, ref) => {
    // store
    const setStoreItems = useShoppingStore(state => state.setItems);
    const storeItems = useShoppingStore(state => state.items);
    const serverItems = useShoppingStore(state => state.serverItems);
    const categories = useShoppingStore(state => state.categories);

    // hook
    const { updateShoppingItems } = useShoppingItemApi();
    const [tmpItems, setTmpItems] = React.useState<IShoppingItem[]>([]);

    // 最後に送信したアイテムを記録して重複実行を防ぐ
    const lastSentItemsRef = React.useRef<string>('');

    // デバウンス処理
    const [debouncedItems, flushDebouncedItems] = useDebounce(
        storeItems,
        DEBOUNCE_DELAY,
    );

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
        [tmpItems],
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
        [tmpItems, setStoreItems],
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
     * ローカル items がサーバーと異なり、かつ直近送信と同一でなければ bulk 更新する（共通処理）
     */
    const pushPendingItems = React.useCallback(
        async (
            items: IShoppingItem[],
            serverItemsSnapshot: IShoppingItem[],
        ) => {
            const currentItemsStr = JSON.stringify(items);
            const serverItemsStr = JSON.stringify(serverItemsSnapshot);
            if (
                items.length === 0 ||
                currentItemsStr === serverItemsStr ||
                currentItemsStr === lastSentItemsRef.current
            ) {
                return;
            }
            const updateItems = items.map((item, idx) => ({
                ...item,
                order: idx,
            }));
            const serverItemById = new Map(
                serverItemsSnapshot.map(item => [item.id, item]),
            );
            const changedItems = updateItems.filter(item => {
                const serverItem = serverItemById.get(item.id);
                return (
                    !serverItem ||
                    JSON.stringify(item) !== JSON.stringify(serverItem)
                );
            });
            if (changedItems.length === 0) {
                return;
            }
            lastSentItemsRef.current = currentItemsStr;
            await updateShoppingItems(changedItems, true);
        },
        [updateShoppingItems],
    );

    /** beforeunload / アンマウント用 effect は依存を空に固定するため、pushPendingItems だけ ref で最新を参照（flush は useDebounce 内で参照が安定） */
    const pushPendingItemsRef = React.useRef(pushPendingItems);
    pushPendingItemsRef.current = pushPendingItems;

    /**
     * デバウンスを flush し、未保存分を API へ送って完了まで待つ
     */
    const syncPendingItems = React.useCallback(async () => {
        if (activeId) {
            return;
        }
        flushDebouncedItems();
        const { items, serverItems: serverItemsSnapshot } =
            useShoppingStore.getState();
        await pushPendingItems(items, serverItemsSnapshot);
    }, [activeId, flushDebouncedItems, pushPendingItems]);

    /**
     * 未保存の変更を送り終えるまで待つ（ローディングアニメーションが終わるまで）
     * @returns void
     */
    React.useImperativeHandle(
        ref,
        () => ({
            syncPendingItems,
        }),
        [syncPendingItems],
    );

    /**
     * デバウンス後の items がサーバーとずれていれば更新
     */
    React.useEffect(() => {
        // debouncedItems が undefined の場合（初期化前）は何もしない
        if (!debouncedItems || activeId) {
            return;
        }
        void pushPendingItems(debouncedItems, serverItems);
    }, [
        debouncedItems,
        activeId,
        serverItems,
        pushPendingItems,
    ]);

    /**
     * アンマウント・ページアンロード時の保存処理（差分のみ API 送信。getState で常に最新を参照）
     */
    React.useEffect(() => {
        const persistFromStore = () => {
            /** beforeunload / アンマウント用 effect は依存を空に固定するため、pushPendingItems だけ ref で最新を参照（flush は useDebounce 内で参照が安定） */
            flushDebouncedItems();

            // ここで useShoppingStore.getState() を呼び出すと、マウント時にはまだ storeItems が空のため、常に最新の storeItems を参照できる
            // アンマウント時には storeItems が最新の内容になっているため、常に最新の storeItems を参照できる
            const { items, serverItems: serverItemsSnapshot } =
                useShoppingStore.getState();
            void pushPendingItemsRef.current(items, serverItemsSnapshot);
        };

        const handleBeforeUnload = () => {
            persistFromStore();
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            persistFromStore();
        };
    }, [flushDebouncedItems]); // flush の参照は useDebounce 側で安定。pushPendingItems は ref で最新を参照

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
        <div className="flex flex-col md:flex-row md:w-max gap-7 md:pr-10">
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
                            syncPendingItems={syncPendingItems}
                        />
                    );
                })}
                <DragOverlay>
                    {activeItem && <ShoppingItemCard item={activeItem} syncPendingItems={syncPendingItems} />}
                </DragOverlay>
            </DndContext>
        </div>
    );
});

ShoppingList.displayName = 'ShoppingList';

export default ShoppingList;
