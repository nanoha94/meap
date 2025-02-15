'use client';
import { Button } from '@/components';
import { TextButton, TextInputAndDelete } from '../../_components';
import { CirclePlus } from 'lucide-react';
import { useRouter } from 'next/navigation';
import React from 'react';
import { useShoppingCategory } from '@/hooks';
import { IPostShoppingCategory } from '@/types/api';
import { generateUuid } from '@/utils/uuid';
import { sort } from '@/utils';
import { DndContext, DragEndEvent } from '@dnd-kit/core';
import { arrayMove, SortableContext, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

const page = () => {
    const {
        shoppingCategories,
        updateShoppingCategories,
        deleteShoppingCategory,
    } = useShoppingCategory();
    const router = useRouter();
    const [categories, setCategories] = React.useState<IPostShoppingCategory[]>(
        [],
    );
    const [isInitialized, setIsInitialized] = React.useState(true);

    const defaultCategoryCount = React.useMemo(
        () => shoppingCategories?.filter(v => v.isDefault).length,
        [shoppingCategories],
    );

    const filteredCategories = React.useMemo(
        () => shoppingCategories?.filter(v => !v.isDefault),
        [shoppingCategories],
    );

    const addEmptyCategory = () => {
        setCategories(prev => [
            ...prev,
            {
                id: generateUuid(),
                name: '',
            },
        ]);
    };

    const updateCategory = (id: string, name: string) => {
        setCategories(prev =>
            prev.map(category =>
                category.id === id ? { ...category, name } : { ...category },
            ),
        );
    };

    const deleteItem = (id: string) => {
        setCategories(prev => prev.filter(categoty => categoty.id !== id));
        deleteShoppingCategory(id);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (active.id !== over.id) {
            setCategories(categories => {
                const oldIndex = categories.findIndex(
                    item => item.id === active.id,
                );
                const newIndex = categories.findIndex(
                    item => item.id === over.id,
                );

                return arrayMove(categories, oldIndex, newIndex);
            });
        }
    };

    React.useEffect(() => {
        if (filteredCategories?.length > 0) {
            setCategories(sort(filteredCategories));
            return;
        }
        if (categories.length === 0 && !!defaultCategoryCount) {
            addEmptyCategory();
            return;
        }
    }, [filteredCategories, defaultCategoryCount]);

    React.useEffect(() => {
        // 初期時に複数の空のカテゴリーが生成されるので１つだけ残す
        // 追加ボタンでのカテゴリー追加時にはここが呼ばれないようにisInitializedをfalseにする
        if (isInitialized) {
            const emptyCategories = categories.filter(v => v.name === '');
            if (emptyCategories.length > 1) {
                setCategories(prev => [
                    ...prev.filter(v => v.name !== ''),
                    emptyCategories[0],
                ]);
            }
        }

        // カテゴリーの順番を更新
        const updatedCategories = categories.map((v, idx) =>
            v.order !== idx ? { ...v, order: idx } : v,
        );
        if (JSON.stringify(updatedCategories) !== JSON.stringify(categories)) {
            setCategories(updatedCategories);
        }
    }, [categories]);

    return (
        <>
            <div className="pb-12 flex flex-col gap-y-7">
                <p className="text-sm">
                    買い物アイテムをカテゴリーごとに管理できます。
                </p>
                <div className="flex flex-col gap-y-4">
                    <DndContext onDragEnd={handleDragEnd}>
                        <SortableContext items={categories}>
                            {categories.map(v => (
                                <InputItem
                                    key={v.id}
                                    id={v.id}
                                    defaultValue={v.name}
                                    onUpdate={updateCategory}
                                    onDelete={deleteItem}
                                />
                            ))}
                        </SortableContext>
                    </DndContext>
                    <TextButton
                        onClick={() => {
                            setIsInitialized(false);
                            addEmptyCategory();
                        }}
                        className="!border-none !bg-transparent">
                        <CirclePlus size={20} />
                        追加
                    </TextButton>
                </div>
            </div>
            <div className="flex gap-x-3">
                <Button
                    variant="outlined"
                    colorVariant="gray"
                    onClick={() => {
                        router.push('/shopping-lists');
                    }}>
                    戻る
                </Button>
                <Button
                    onClick={() => {
                        updateShoppingCategories(categories);
                        router.push('/shopping-lists');
                    }}>
                    設定
                </Button>
            </div>
        </>
    );
};

export default page;

interface InputItemProps {
    id: string;
    defaultValue: string;
    onUpdate: (id: string, name: string) => void;
    onDelete: (id: string) => void;
}
const InputItem: React.FC<InputItemProps> = ({
    id,
    defaultValue,
    onUpdate,
    onDelete,
}) => {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            key={id}
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}>
            <TextInputAndDelete
                defaultValue={defaultValue}
                onUpdate={name => {
                    onUpdate(id, name);
                }}
                onDelete={() => {
                    onDelete(id);
                }}
            />
        </div>
    );
};
