'use client';
import React from 'react';
import { Button } from '@/components';
import { DndContext, DragEndEvent } from '@dnd-kit/core';
import { arrayMove, SortableContext } from '@dnd-kit/sortable';
import {
    IGetShoppingCategory,
    IGetShoppingItem,
    IPostShoppingItem,
} from '@/types/api';
import { CategoryItemList } from './_components';
import { TextButton } from '../_components';
import { ChevronRight } from 'lucide-react';
import { useRouter } from 'next/navigation';
import { useShoppingCategory, useShoppingItem } from '@/hooks';
import { sort, generateUuid } from '@/utils';

const Page = () => {
    const { shoppingItems, updateShoppingItem, deleteShoppingItem } =
        useShoppingItem();
    const { shoppingCategories } = useShoppingCategory();
    const router = useRouter();

    const [items, setItems] = React.useState<IGetShoppingItem[]>([]);
    const [categories, setCategories] = React.useState<IGetShoppingCategory[]>(
        [],
    );

    const addEmptyItem = (categoryId: string) => {
        setItems(prev => [
            ...prev,
            {
                id: generateUuid(),
                name: '',
                isChecked: false,
                isPinned: false,
                categoryId: categoryId,
                order: items.filter(v => v.name.length > 0).length,
            },
        ]);
    };

    const deleteItem = (id: string) => {
        setItems(prev => prev.filter(item => item.id !== id));
        deleteShoppingItem(id);
    };

    const updateItem = (item: IPostShoppingItem) => {
        const { id, name, isPinned, isChecked, order } = item;

        setItems(prev =>
            prev.map(v =>
                v.id === id ? { ...v, name, isPinned, isChecked, order } : v,
            ),
        );
        updateShoppingItem(item);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (active.id !== over.id) {
            setItems(items => {
                const oldIndex = items.findIndex(item => item.id === active.id);
                const newIndex = items.findIndex(item => item.id === over.id);

                return arrayMove(items, oldIndex, newIndex);
            });
        }
    };

    React.useEffect(() => {
        const updateOrder = async () => {
            for (let idx = 0; idx < items.length; idx++) {
                const item = items[idx];
                if (item.order !== idx) {
                    await updateShoppingItem({
                        ...item,
                        categoryId: item.categoryId,
                        order: idx,
                    });
                }
            }
        };
        updateOrder();
    }, [items]);

    React.useEffect(() => {
        if (shoppingItems) {
            setItems(sort(shoppingItems));
        }
    }, [shoppingItems]);

    React.useEffect(() => {
        if (shoppingCategories) {
            setCategories(sort(shoppingCategories));
        }
    }, [shoppingCategories]);

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
                        <DndContext onDragEnd={handleDragEnd}>
                            <SortableContext items={items}>
                                {categories.map(category => (
                                    <CategoryItemList
                                        key={category.id}
                                        category={category}
                                        items={items.filter(
                                            item =>
                                                !!item.id &&
                                                item.categoryId === category.id,
                                        )}
                                        addEmptyItem={addEmptyItem}
                                        deleteItem={deleteItem}
                                        updateItem={updateItem}
                                    />
                                ))}
                            </SortableContext>
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
            {items.some(v => !v.id) && (
                <div className="flex flex-col gap-y-3">
                    <Button variant="outlined">チェックをすべて外す</Button>
                    <Button>買い物リストを空にする</Button>
                </div>
            )}
        </>
    );
};

export default Page;
