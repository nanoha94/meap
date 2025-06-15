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
    MouseSensor,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
    IGetShoppingItem,
    IGetShoppingItemsResponse,
    IPostShoppingItem,
} from '@/types/api';
import { CategoryItemList, ShoppingItem } from './_components';
import { ChevronRight, LoaderCircle } from 'lucide-react';
import { useRouter } from 'next/navigation';
import { useShoppingItem } from '@/hooks';
import { useDebounce } from '@/hooks/useDebounce';
import { AlertDialog, TextButton } from '../_components';
import { colors } from '@/constants/colors';

enum ItemType {
    ITEM = 'item',
    CATEGORY = 'category',
    NONE = null,
}

const Page = () => {
    const {
        isLoading,
        shoppingItems,
        updateShoppingItems,
        deleteShoppingItem,
        deleteAllShoppingItems,
    } = useShoppingItem();
    const router = useRouter();

    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                distance: 10, // 10pxドラッグした時にソート機能を有効にする
            },
        }),
    );

    // ページに初めてアクセスしたときのみローディングを表示したいので、そのためのフラグ
    const [isInit, setIsInit] = React.useState(true);

    const [listItems, setListItems] = React.useState<
        IGetShoppingItemsResponse['data']
    >([]);
    const [items, setItems] = React.useState<IGetShoppingItem[]>([]);
    const [activeId, setActiveId] = React.useState<string | null>(null);
    const debouncedItems = useDebounce(items, 5000);

    const [isOpenListEmptyDialog, setIsOpenListEmptyDialog] =
        React.useState(false);

    // shoppingItemsの変更を監視
    React.useEffect(() => {
        if (shoppingItems) {
            setListItems(shoppingItems);
        }
    }, [shoppingItems]);

    React.useEffect(() => {
        console.log('listItems', listItems);
        setItems(listItems.flatMap(v => v.items));
    }, [listItems]);

    /**
     * アイテムのカテゴリーIDを取得
     * @param itemId アイテムID
     * @returns カテゴリーID
     */
    const categoryIdFromItemId = (itemId: string): string | undefined => {
        return items.find(v => v.id === itemId)?.categoryId;
    };

    const updateItem = (item: IPostShoppingItem) => {
        const { id, name, isPinned, isChecked, order } = item;
        const categoryId = categoryIdFromItemId(id);

        if (categoryId) {
            setListItems(
                listItems.map(v => ({
                    ...v,
                    items: v.items.map(item =>
                        item.id === id
                            ? { ...item, name, isPinned, isChecked, order }
                            : item,
                    ),
                })),
            );
        }
    };

    // TODO: あとで復活
    // const unCheckAllItems = () => {
    //     setItems(prev => {
    //         const newItems = { ...prev };
    //         Object.keys(prev).forEach(categoryId => {
    //             newItems[categoryId] = prev[categoryId].map(item => ({
    //                 ...item,
    //                 isChecked: false,
    //             }));
    //         });
    //         return newItems;
    //     });
    // };

    // ５秒間変更がなかったらAPIに送る
    React.useEffect(() => {
        if (debouncedItems.length > 0) {
            // APIに送るデータの形式に変換
            const updateItems = debouncedItems.map((item, idx) => ({
                ...item,
                order: idx,
            }));

            if (
                JSON.stringify(debouncedItems) !== JSON.stringify(shoppingItems)
            ) {
                updateShoppingItems(updateItems);
            }
        }
    }, [debouncedItems]);

    // アンマウント時とページアンロード時の保存処理
    const saveItemsRef = React.useRef(() => {});
    saveItemsRef.current = () => {
        if (Object.keys(items).length > 0) {
            const updateItems = items.map((item, idx) => ({
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

    React.useEffect(() => {
        if (isInit && shoppingItems && shoppingItems.length > 0) {
            setIsInit(false);
        }
    }, [shoppingItems]);

    const updateSortableItems = (activeId: string, overId: string) => {
        if (activeId === overId) return;
        const overCategoryItemId = categoryIdFromItemId(overId);
        const overCategoryInfo = listItems?.find(
            v => v.category.id === overId,
        )?.category;

        // overIdがitemかcategoryかを判断
        const overType = overCategoryItemId
            ? ItemType.ITEM
            : overCategoryInfo
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

        // カテゴリごとのインデックスを取得
        const activeCategory = listItems.find(
            v => v.category.id === activeCategoryId,
        );
        const overCategory = listItems.find(
            v => v.category.id === overCategoryId,
        );

        if (!activeCategory || !overCategory) return;

        const activeIndex = activeCategory.items.findIndex(
            v => v.id === activeId,
        );
        const overIndex =
            overType === ItemType.ITEM
                ? overCategory.items.findIndex(v => v.id === overId)
                : 0;

        if (activeIndex === -1 || overIndex === -1) return;

        // 別カテゴリ―での入れ替え
        if (activeCategoryId !== overCategoryId) {
            const activeItem = activeCategory.items[activeIndex];
            if (activeItem) {
                const removedActiveItems = listItems.map(v => ({
                    ...v,
                    items: v.items.filter(item => item.id !== activeId),
                }));

                const updatedListItems = removedActiveItems.map(v => {
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

                setListItems(updatedListItems);
            }
        }
        // 同カテゴリーでの入れ替え
        else {
            const updatedListItems = listItems.map(v => {
                if (v.category.id === activeCategoryId) {
                    return {
                        ...v,
                        items: arrayMove(v.items, activeIndex, overIndex),
                    };
                }
                return v;
            });
            setListItems(updatedListItems);
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

    const handleDragEnd = () => {
        setActiveId(null);
    };

    if (isInit && isLoading) {
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
                    <div className="flex flex-col gap-y-7">
                        {!!listItems && listItems.length > 0 ? (
                            <DndContext
                                sensors={sensors}
                                collisionDetection={closestCenter}
                                onDragStart={handleDragStart}
                                onDragEnd={handleDragEnd}
                                onDragOver={handleDragOver}>
                                {listItems.map(v => (
                                    <SortableContext
                                        key={v.category.id}
                                        items={v.items}
                                        strategy={verticalListSortingStrategy}>
                                        <CategoryItemList
                                            category={v.category}
                                            items={v.items}
                                            deleteItem={deleteShoppingItem}
                                            updateItem={updateItem}
                                        />
                                    </SortableContext>
                                ))}
                                <DragOverlay>
                                    {activeId ? (
                                        <ShoppingItem
                                            item={items?.find(
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
                                                    categoryId: items?.find(
                                                        item =>
                                                            item.id ===
                                                            activeId,
                                                    )?.categoryId,
                                                    order: items?.find(
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
