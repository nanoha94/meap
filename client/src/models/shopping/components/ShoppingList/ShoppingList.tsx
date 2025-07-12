'use client';
import React from 'react';
import {
    closestCenter,
    DndContext,
    DragOverEvent,
    DragOverlay,
    DragStartEvent,
    useSensors,
    useSensor,
    MouseSensor,
} from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import CategoryItemList from './CategoryItemList';
import ShoppingItemCard from './ShoppingItemCard';
import { IGetShoppingItemsResponse } from '@/types/api';
import { useDebounce } from '@/hooks/useDebounce';
import {
    useShoppingStore,
    useShoppingHandlers,
    useShoppingItems,
} from '../../hooks';
import { DRAG_ACTIVATION_DISTANCE, DEBOUNCE_DELAY } from '../../constants';

interface Props {
    items: IGetShoppingItemsResponse['data'];
}

const ShoppingList: React.FC<Props> = ({ items }) => {
    const { handleMoveItem } = useShoppingHandlers();
    const {
        setItems: setStoreItems,
        items: storeItems,
        serverItems,
        isLoadingItems,
    } = useShoppingStore();
    const { updateShoppingItems } = useShoppingItems();
    const [activeId, setActiveId] = React.useState<string | null>(null);

    // 最後に送信したアイテムを記録して重複実行を防ぐ
    const lastSentItemsRef = React.useRef<string>('');

    // アイテムのフラット化
    const flatItems = React.useMemo(() => {
        return storeItems?.flatMap(v => v.items) || [];
    }, [storeItems]);

    // デバウンス処理
    const debouncedItems = useDebounce(flatItems, DEBOUNCE_DELAY);

    // ドラッグ&ドロップ設定
    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                distance: DRAG_ACTIVATION_DISTANCE,
            },
        }),
    );

    /**
     * ドラッグ開始
     */
    const handleDragStart = React.useCallback((event: DragStartEvent) => {
        setActiveId(event.active.id as string);
    }, []);

    /**
     * ドラッグオーバー
     */
    const handleDragOver = React.useCallback(
        (event: DragOverEvent) => {
            const { active, over } = event;
            if (!over) return;
            handleMoveItem(active.id as string, over.id as string);
        },
        [handleMoveItem],
    );

    /**
     * ドラッグ終了
     */
    const handleDragEnd = React.useCallback(() => {
        setActiveId(null);
    }, []);

    /**
     * アクティブなアイテム
     */
    const activeItem = React.useMemo(
        () => flatItems.find(item => item.id === activeId),
        [activeId, flatItems],
    );

    /**
     * ストアにアイテムを設定
     */
    React.useEffect(() => {
        setStoreItems(items);
    }, [items]);

    /**
     * デバウンス処理
     */
    React.useEffect(() => {
        // debouncedItems が undefined の場合（初期化前）は何もしない
        if (debouncedItems) {
            const currentItemsStr = JSON.stringify(debouncedItems);
            // flatServerItemsではなく、直接serverItemsからフラット化して比較
            const serverItemsFlat = serverItems?.flatMap(v => v.items) || [];
            const serverItemsStr = JSON.stringify(serverItemsFlat);

            // アイテムの比較と更新処理を直接実行
            if (
                debouncedItems.length > 0 &&
                !isLoadingItems &&
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
    }, [debouncedItems]);

    /**
     * アンマウント・ページアンロード時の保存処理
     */
    React.useEffect(() => {
        const handleBeforeUnload = () => {
            const currentFlatItems = storeItems?.flatMap(v => v.items) || [];
            const currentFlatServerItems =
                serverItems?.flatMap(v => v.items) || [];

            if (
                currentFlatItems.length > 0 &&
                !isLoadingItems &&
                JSON.stringify(currentFlatItems) !==
                    JSON.stringify(currentFlatServerItems)
            ) {
                const updateItems = currentFlatItems.map((item, idx) => ({
                    ...item,
                    order: idx,
                }));
                updateShoppingItems(updateItems);
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            // アンマウント時の保存処理
            const currentFlatItems = storeItems?.flatMap(v => v.items) || [];
            const currentFlatServerItems =
                serverItems?.flatMap(v => v.items) || [];

            if (
                currentFlatItems.length > 0 &&
                !isLoadingItems &&
                JSON.stringify(currentFlatItems) !==
                    JSON.stringify(currentFlatServerItems)
            ) {
                const updateItems = currentFlatItems.map((item, idx) => ({
                    ...item,
                    order: idx,
                }));
                updateShoppingItems(updateItems);
            }
        };
    }, []); // 依存配列を空にして、マウント時に一度だけ実行

    return (
        <div className="flex flex-col gap-y-7">
            {storeItems?.length > 0 && (
                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCenter}
                    onDragStart={handleDragStart}
                    onDragEnd={handleDragEnd}
                    onDragOver={handleDragOver}>
                    {storeItems.map(v => (
                        <SortableContext
                            key={v.category.id}
                            items={v.items}
                            strategy={verticalListSortingStrategy}>
                            <CategoryItemList
                                category={v.category}
                                items={v.items}
                            />
                        </SortableContext>
                    ))}
                    <DragOverlay>
                        {activeItem && <ShoppingItemCard item={activeItem} />}
                    </DragOverlay>
                </DndContext>
            )}
        </div>
    );
};

export default ShoppingList;
