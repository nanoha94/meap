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
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CategoryItemList, ShoppingItem } from './_components';
import {
    CalendarDays,
    ChevronRight,
    LoaderCircle,
    SquarePen,
} from 'lucide-react';
import { useDebounce } from '@/hooks/useDebounce';
import { AlertDialog, Header, TextButton } from '../_components';
import { colors } from '@/constants/colors';
import {
    SettingCategoryDialog,
    SettingItemDialog,
} from './_components/Dialogs';
import { useShoppingListLogic } from './_hooks/useShoppingListLogic';
import { useShoppingCategory, useShoppingItem } from '@/hooks/api';

const Page = () => {
    const {
        isLoading,
        shoppingItems,
        updateShoppingItems,
        deleteShoppingItem,
        deleteAllShoppingItems,
        mutate: itemMutate,
    } = useShoppingItem();
    const { shoppingCategories, handleCategoryChange } = useShoppingCategory();

    const {
        items,
        updateItem,
        moveItem,
        setActiveId,
        activeId,
        setIsShowLoading,
        isShowLoading,
        setItems,
    } = useShoppingListLogic();

    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                distance: 10, // 10pxドラッグした時にソート機能を有効にする
            },
        }),
    );

    // ローカルでflatItemsを計算
    const flatItems = React.useMemo(() => {
        return items.flatMap(v => v.items);
    }, [items]);

    const debouncedItems = useDebounce(flatItems, 5000);

    // アイテム追加・編集ダイアログを表示するか
    const [isOpenSettingItemDialog, setIsOpenSettingItemDialog] =
        React.useState(false);
    // カテゴリー設定ダイアログを表示するか
    const [isOpenSettingCategoryDialog, setIsOpenSettingCategoryDialog] =
        React.useState(false);
    // 買い物リストを空にするダイアログを表示するか
    const [isOpenListEmptyDialog, setIsOpenListEmptyDialog] =
        React.useState(false);

    // shoppingItemsの変更を監視
    React.useEffect(() => {
        if (shoppingItems) {
            setItems(shoppingItems);
        }
    }, [shoppingItems]);

    // カテゴリー変更時の処理
    React.useEffect(() => {
        if (shoppingItems) {
            handleCategoryChange(shoppingItems, itemMutate, () => {
                setIsShowLoading(true);
            });
        }
    }, [shoppingCategories, shoppingItems, handleCategoryChange, itemMutate]);

    React.useEffect(() => {
        if (!isLoading && shoppingItems?.length > 0) setIsShowLoading(false);
    }, [isLoading]);

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

    const handleDragStart = (event: DragStartEvent) => {
        const { active } = event;

        setActiveId(active.id as string);
    };

    const handleDragOver = (event: DragOverEvent) => {
        const { active, over } = event;
        if (!over) return;
        moveItem(active.id as string, over.id as string);
    };

    const handleDragEnd = () => {
        setActiveId(null);
    };

    if (isShowLoading && isLoading) {
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
                <Header title="買い物リスト">
                    <div className="flex gap-x-4">
                        {/* TODO: 実装 */}
                        <TextButton colorVariant="accent" onClick={() => {}}>
                            <CalendarDays size={20} />
                            献立から追加
                        </TextButton>
                        <TextButton
                            colorVariant="gray"
                            onClick={() => {
                                setIsOpenSettingItemDialog(true);
                            }}>
                            <SquarePen size={20} />
                            テキストから追加
                        </TextButton>
                    </div>
                </Header>
                <div className="p-5">
                    <div className="pb-12 flex flex-col gap-y-7">
                        <div className="flex flex-col gap-y-7">
                            {!!items && items.length > 0 ? (
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragStart={handleDragStart}
                                    onDragEnd={handleDragEnd}
                                    onDragOver={handleDragOver}>
                                    {items.map(v => (
                                        <SortableContext
                                            key={v.category.id}
                                            items={v.items}
                                            strategy={
                                                verticalListSortingStrategy
                                            }>
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
                                                item={flatItems?.find(
                                                    item =>
                                                        item.id === activeId,
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
                                                        categoryId:
                                                            flatItems?.find(
                                                                item =>
                                                                    item.id ===
                                                                    activeId,
                                                            )?.categoryId,
                                                        tags: flatItems?.find(
                                                            item =>
                                                                item.id ===
                                                                activeId,
                                                        )?.tags,
                                                        order: flatItems?.find(
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
                                setIsOpenSettingCategoryDialog(true);
                            }}>
                            カテゴリーの追加・編集
                            <ChevronRight size={20} />
                        </TextButton>
                    </div>
                    {/* アイテム追加・編集ダイアログ */}
                    {isOpenSettingItemDialog && (
                        <SettingItemDialog
                            onClose={() => {
                                setIsOpenSettingItemDialog(false);
                            }}
                        />
                    )}
                    {/* カテゴリー設定ダイアログ */}
                    {isOpenSettingCategoryDialog && (
                        <SettingCategoryDialog
                            onClose={() => {
                                setIsOpenSettingCategoryDialog(false);
                            }}
                        />
                    )}
                    {/* 買い物リストを空にするダイアログ */}
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
                </div>
            </>
        );
    }
};

export default Page;
