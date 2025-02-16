'use client';
import React from 'react';
import { Button } from '@/components';
import {
    closestCenter,
    DndContext,
    DragOverEvent,
    DragOverlay,
    DragStartEvent,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragEndEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
    IGetShoppingCategory,
    IGetShoppingItem,
    IPostShoppingItem,
} from '@/types/api';
import { CategoryItemList, ShoppingItem } from './_components';
import { TextButton } from '../_components';
import { ChevronRight } from 'lucide-react';
import { useRouter } from 'next/navigation';
import { useDebounce, useShoppingCategory, useShoppingItem } from '@/hooks';
import { sort, generateUuid } from '@/utils';

enum ItemType {
    ITEM = 'item',
    CATEGORY = 'category',
    NONE = null,
}

type ItemsByCategory = {
    [key: string]: IGetShoppingItem[];
};

const Page = () => {
    const { shoppingItems, updateShoppingItems, deleteShoppingItem } =
        useShoppingItem();
    const { shoppingCategories } = useShoppingCategory();
    const router = useRouter();

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const [activeId, setActiveId] = React.useState<string | null>(null);
    const [items, setItems] = React.useState<ItemsByCategory>({});
    const debouncedItems = useDebounce(items, 5000);
    const [categories, setCategories] = React.useState<IGetShoppingCategory[]>(
        [],
    );

    const categoryIdFromItemId = (itemId: string): string | undefined => {
        return Object.keys(items).find(
            categoryId =>
                items[categoryId].find(item => item.id === itemId)?.categoryId,
        );
    };

    const addEmptyItem = (categoryId: string) => {
        const newItem = {
            id: generateUuid(),
            name: '',
            isChecked: false,
            isPinned: false,
            categoryId: categoryId,
            order: items[categoryId].filter(v => v.name.length > 0).length,
        };

        setItems(prev => ({
            ...prev,
            [categoryId]: [...prev[categoryId], newItem],
        }));
    };

    const deleteItem = (id: string) => {
        const categoryId = categoryIdFromItemId(id);
        if (categoryId) {
            setItems(prev => ({
                ...prev,
                [categoryId]: prev[categoryId].filter(item => item.id !== id),
            }));
            deleteShoppingItem(id);
        }
    };

    const updateItem = (item: IPostShoppingItem) => {
        const { id, name, isPinned, isChecked, order } = item;
        const categoryId = categoryIdFromItemId(id);
        if (categoryId) {
            setItems(prev => ({
                ...prev,
                [categoryId]: prev[categoryId].map(v =>
                    v.id === id
                        ? { ...v, name, isPinned, isChecked, order }
                        : v,
                ),
            }));
        }
    };

    // 5秒異常の操作がなければAPIでデータを更新
    React.useEffect(() => {
        if (activeId) return;

        const update = async () => {
            const updates: IPostShoppingItem[] = categories
                .map(category => {
                    return items[category.id].map((item, idx) => {
                        return {
                            ...item,
                            order: idx,
                        };
                        return null;
                    });
                })
                .flat()
                .filter(v => v !== null);

            if (updates.length > 0) {
                await updateShoppingItems(updates);
            }
        };

        update();
    }, [debouncedItems]);

    const updateSortableItems = (activeId: string, overId: string) => {
        const overCategoryItemId = categoryIdFromItemId(overId);
        const overCategory = categories.find(
            category => category.id === overId,
        );

        // overIdがitemかcategoryかを判断
        const overType = overCategoryItemId
            ? ItemType.ITEM
            : overCategory
              ? ItemType.CATEGORY
              : ItemType.NONE;

        // カテゴリーIDを取得
        const activeCategoryId = categoryIdFromItemId(activeId);
        const overCategoryId =
            overType === ItemType.CATEGORY
                ? overId
                : overType === ItemType.ITEM
                  ? overCategoryItemId
                  : null;

        if (!activeCategoryId && !overCategoryId) return;

        const activeItem = items[activeCategoryId].find(
            item => item.id === activeId,
        );

        const newIndex =
            overType === ItemType.ITEM
                ? items[overCategoryId].findIndex(item => item.id === overId)
                : 0;

        if (activeCategoryId !== overCategoryId) {
            if (activeItem) {
                setItems(prev => ({
                    ...prev,
                    [activeCategoryId]: prev[activeCategoryId].filter(
                        item => item.id !== activeId,
                    ),
                    [overCategoryId]: [
                        ...prev[overCategoryId].slice(0, newIndex),
                        { ...activeItem, categoryId: overCategoryId },
                        ...prev[overCategoryId].slice(newIndex),
                    ],
                }));
            }
        } else {
            setItems(prev => ({
                ...prev,
                [activeCategoryId]: arrayMove(
                    prev[activeCategoryId],
                    prev[activeCategoryId].findIndex(
                        item => item.id === activeId,
                    ),
                    prev[activeCategoryId].findIndex(
                        item => item.id === overId,
                    ),
                ),
            }));
        }
    };

    const handleDragStart = (event: DragStartEvent) => {
        const { active } = event;

        setActiveId(active.id as string);
    };

    const handleDragOver = (event: DragOverEvent) => {
        const { active, over } = event;
        if (!over) return;
        updateSortableItems(active.id as string, over.id as string);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over) return;
        updateSortableItems(active.id as string, over.id as string);
        // await updateOrder(active.id as string);
        setActiveId(null);
    };

    React.useEffect(() => {
        if (shoppingItems && shoppingCategories) {
            setCategories(sort(shoppingCategories));

            // カテゴリーごとにアイテムを整理
            const formattedItemsByCategory: ItemsByCategory = {};
            shoppingCategories.forEach(category => {
                formattedItemsByCategory[category.id] = sort(
                    shoppingItems.filter(
                        item => item.categoryId === category.id,
                    ),
                );
            });
            setItems(formattedItemsByCategory);
        }
    }, [shoppingCategories, shoppingItems]);

    return (
        <>
            <div className="pb-12 flex flex-col gap-y-7">
                <p className="text-sm">
                    アイテムのタップで、編集・削除・固定化ができます。
                    <br />
                    アイテムのドラッグ＆ドロップで、並び替えができます。
                </p>
                <div className="flex flex-col gap-y-7">
                    {!!categories && categories.length > 0 ? (
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragStart={handleDragStart}
                            onDragEnd={handleDragEnd}
                            onDragOver={handleDragOver}>
                            {categories.map(category => (
                                <SortableContext
                                    key={category.id}
                                    items={items[category.id]}
                                    strategy={verticalListSortingStrategy}>
                                    <CategoryItemList
                                        category={category}
                                        items={items[category.id]}
                                        addEmptyItem={addEmptyItem}
                                        deleteItem={deleteItem}
                                        updateItem={updateItem}
                                    />
                                </SortableContext>
                            ))}
                            <DragOverlay>
                                {activeId ? (
                                    <ShoppingItem
                                        item={items[
                                            categoryIdFromItemId(activeId)
                                        ].find(item => item.id === activeId)}
                                        onDelete={() => deleteItem(activeId)}
                                        onUpdate={(name, isPinned, isChecked) =>
                                            updateItem({
                                                id: activeId,
                                                name,
                                                isPinned,
                                                isChecked,
                                                categoryId: categories.find(
                                                    category =>
                                                        category.id ===
                                                        activeId,
                                                )?.id,
                                                order: items[
                                                    categoryIdFromItemId(
                                                        activeId,
                                                    )
                                                ].find(
                                                    item =>
                                                        item.id === activeId,
                                                )?.order,
                                            })
                                        }
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
                <TextButton
                    onClick={() => {
                        router.push('/shopping-lists/categories');
                    }}>
                    カテゴリーの追加・編集
                    <ChevronRight size={20} />
                </TextButton>
            </div>
            {!!items && (
                <div className="flex flex-col gap-y-3">
                    <Button variant="outlined">チェックをすべて外す</Button>
                    <Button>買い物リストを空にする</Button>
                </div>
            )}
        </>
    );
};

export default Page;
