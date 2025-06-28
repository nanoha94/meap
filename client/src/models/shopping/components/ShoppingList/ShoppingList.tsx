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

interface Props {
    items: IGetShoppingItemsResponse['data'];
}

const ShoppingList: React.FC<Props> = ({ items }) => {
    const { handleMoveItem } = useShoppingHandlers();
    const { setItems: setStoreItems, items: storeItems } = useShoppingStore();
    const { updateShoppingItems } = useShoppingItems();
    const [activeId, setActiveId] = React.useState<string | null>(null);

    const flatItems = React.useMemo(() => {
        if (!storeItems || !Array.isArray(storeItems)) {
            return [];
        }
        return storeItems.flatMap(v => v.items);
    }, [storeItems]);

    const debouncedItems = useDebounce(flatItems, 5000);

    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                distance: 5, // 5pxドラッグした時にソート機能を有効にする
            },
        }),
    );

    const handleDragStart = (event: DragStartEvent) => {
        const { active } = event;

        setActiveId(active.id as string);
    };

    const handleDragOver = (event: DragOverEvent) => {
        const { active, over } = event;
        if (!over) return;
        handleMoveItem(active.id as string, over.id as string);
    };

    const handleDragEnd = () => {
        setActiveId(null);
    };

    React.useEffect(() => {
        setStoreItems(items);
    }, [items]);

    // ５秒間変更がなかったらAPIに送る
    React.useEffect(() => {
        if (debouncedItems.length > 0) {
            // APIに送るデータの形式に変換
            const updateItems = debouncedItems.map((item, idx) => ({
                ...item,
                order: idx,
            }));

            if (JSON.stringify(debouncedItems) !== JSON.stringify(storeItems)) {
                updateShoppingItems(updateItems);
            }
        }
    }, [debouncedItems]);

    // アンマウント時とページアンロード時の保存処理
    const saveItemsRef = React.useRef(() => {});
    saveItemsRef.current = () => {
        if (Object.keys(flatItems).length > 0) {
            const updateItems = flatItems.map((item, idx) => ({
                ...item,
                order: idx,
            }));
            updateShoppingItems(updateItems);
        }
    };

    // ページアンロード時とアンマウント時の保存設定（初回マウント時のみ設定）
    React.useEffect(() => {
        const handleBeforeUnload = () => saveItemsRef.current();

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            saveItemsRef.current();
        };
    }, []);

    return (
        <div className="flex flex-col gap-y-7">
            {!!storeItems && storeItems.length > 0 ? (
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
                        {activeId ? (
                            <ShoppingItemCard
                                item={flatItems?.find(
                                    item => item.id === activeId,
                                )}
                            />
                        ) : (
                            <></>
                        )}
                    </DragOverlay>
                </DndContext>
            ) : (
                <></>
            )}
        </div>
    );
};

export default ShoppingList;
