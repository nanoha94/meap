'use client';
import React from 'react';
import { Button } from '@/components';
import {
    closestCenter,
    DndContext,
    DragOverEvent,
    DragOverlay,
    DragStartEvent,
    useSensor,
    useSensors,
    DragEndEvent,
    MouseSensor,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
    IGetShoppingCategory,
    IGetShoppingItem,
    IPostShoppingItem,
} from '@/types/api';
import { CategoryItemList, ShoppingItem } from './_components';
import { ChevronRight, LoaderCircle } from 'lucide-react';
import { useRouter } from 'next/navigation';
import { useShoppingCategory, useShoppingItem } from '@/hooks';
import { sort, generateUuid } from '@/utils';
import { useDebounce } from '@/hooks/useDebounce';
import { AlertDialog, TextButton } from '../_components';
import { colors } from '@/constants/colors';

enum ItemType {
    ITEM = 'item',
    CATEGORY = 'category',
    NONE = null,
}

type ItemsByCategory = {
    [key: string]: IGetShoppingItem[];
};

const Page = () => {
    const {
        isLoading,
        shoppingItems,
        updateShoppingItems,
        deleteShoppingItem,
        deleteAllShoppingItems,
    } = useShoppingItem();
    const { shoppingCategories } = useShoppingCategory();
    const router = useRouter();

    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                distance: 10, // 10pxドラッグした時にソート機能を有効にする
            },
        }),
    );

    const [activeId, setActiveId] = React.useState<string | null>(null);
    const [items, setItems] = React.useState<ItemsByCategory>({});
    const debouncedItems = useDebounce(items, 5000);
    const [categories, setCategories] = React.useState<IGetShoppingCategory[]>(
        [],
    );

    const [isOpenListEmptyDialog, setIsOpenListEmptyDialog] =
        React.useState(false);

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

    const unCheckAllItems = () => {
        setItems(prev => {
            const newItems = { ...prev };
            Object.keys(prev).forEach(categoryId => {
                newItems[categoryId] = prev[categoryId].map(item => ({
                    ...item,
                    isChecked: false,
                }));
            });
            return newItems;
        });
    };

    // ５秒間変更がなかったらAPIに送る
    React.useEffect(() => {
        if (debouncedItems.length > 0) {
            // APIに送るデータの形式に変換
            const updateItems = categories
                .map(category =>
                    debouncedItems[category.id].map((item, idx) => ({
                        ...item,
                        order: idx,
                    })),
                )
                .flat()
                .filter(v => v !== null && v.name.length > 0);

            if (
                JSON.stringify(debouncedItems) !== JSON.stringify(shoppingItems)
            ) {
                updateShoppingItems(updateItems);
            }
        }
    }, [debouncedItems, categories]);

    // アンマウント時とページアンロード時の保存処理
    const saveItemsRef = React.useRef(() => {});
    saveItemsRef.current = () => {
        if (Object.keys(items).length > 0) {
            const updateItems = categories
                .map(category =>
                    items[category.id].map((item, idx) => ({
                        ...item,
                        order: idx,
                    })),
                )
                .flat()
                .filter(v => v !== null && v.name.length > 0);
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

    React.useEffect(() => {
        if (shoppingItems && shoppingCategories) {
            setCategories(sort(shoppingCategories));

            // カテゴリーごとにアイテムを整理
            // 表示する際に便利な形式に変換
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
        setActiveId(null);
    };

    if (isLoading) {
        return (
            <div className="py-5">
                <LoaderCircle
                    size={40}
                    color={colors.primary.main}
                    className="animate-spin mx-auto"
                />
            </div>
        );
    } else {
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
                                            deleteItem={deleteShoppingItem}
                                            updateItem={updateItem}
                                        />
                                    </SortableContext>
                                ))}
                                <DragOverlay>
                                    {activeId ? (
                                        <ShoppingItem
                                            item={items[
                                                categoryIdFromItemId(activeId)
                                            ].find(
                                                item => item.id === activeId,
                                            )}
                                            onDelete={() =>
                                                deleteShoppingItem(activeId)
                                            }
                                            onUpdate={(
                                                name,
                                                isPinned,
                                                isChecked,
                                            ) =>
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
                                                            item.id ===
                                                            activeId,
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
                        <Button variant="outlined" onClick={unCheckAllItems}>
                            チェックをすべて外す
                        </Button>
                        <Button onClick={() => setIsOpenListEmptyDialog(true)}>
                            買い物リストを空にする
                        </Button>
                    </div>
                )}
                {isOpenListEmptyDialog && (
                    <AlertDialog
                        title="買い物リストを空にする"
                        onClose={() => setIsOpenListEmptyDialog(false)}>
                        <div className="flex flex-col gap-y-7">
                            <p className="text-center">
                                買い物リストに登録されているすべてのアイテムを削除しますか？
                            </p>
                            <p className="text-sm text-center">
                                ※固定化アイテムは削除されません
                            </p>
                            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                                <Button
                                    colorVariant="gray"
                                    variant="outlined"
                                    onClick={() =>
                                        setIsOpenListEmptyDialog(false)
                                    }>
                                    キャンセル
                                </Button>
                                <Button
                                    onClick={() => {
                                        deleteAllShoppingItems();
                                        setIsOpenListEmptyDialog(false);
                                    }}
                                    colorVariant="alert">
                                    削除
                                </Button>
                            </div>
                        </div>
                    </AlertDialog>
                )}
            </>
        );
    }
};

export default Page;
