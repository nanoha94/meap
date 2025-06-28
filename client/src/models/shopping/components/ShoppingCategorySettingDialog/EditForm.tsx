'use client';
import { Button, TextButton } from '@/components/common';
import {
    DndContext,
    DragEndEvent,
    KeyboardSensor,
    MouseSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { SortableContext } from '@dnd-kit/sortable';
import { TMP_ID_PREFIX } from '@/constants/ids';
import { CirclePlus } from 'lucide-react';
import React from 'react';
import { IShoppingCategory } from '@/types/api';
import { useFieldArray, useForm } from 'react-hook-form';
import EditItem from './EditItem';
import SpinAnimation from '@/components/common/SpinAnimation';
import Sortable from '@/components/dnd/Sortable';
import { useShoppingCategories } from '../../hooks';

interface FormData {
    categories: IShoppingCategory[];
}

interface Props {
    onBack: () => void;
}

const EditForm: React.FC<Props> = ({ onBack }) => {
    const { isLoading, storeData, bulkUpdateShoppingCategories } =
        useShoppingCategories();

    const { control, handleSubmit, watch, reset } = useForm<FormData>({
        defaultValues: {
            categories: [],
        },
    });

    const { fields, append, remove, move } = useFieldArray({
        control,
        name: 'categories',
    });

    const watchedCategories = watch('categories');

    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                distance: 5, // 5px ドラッグした時にソート機能を有効にする
            },
        }),
        useSensor(KeyboardSensor),
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (active.id !== over?.id) {
            const oldIndex = parseInt(
                active.id
                    .toString()
                    .replace(TMP_ID_PREFIX.SHOPPING_CATEGORY, ''),
            );
            const newIndex = parseInt(
                over.id.toString().replace(TMP_ID_PREFIX.SHOPPING_CATEGORY, ''),
            );
            move(oldIndex, newIndex);
        }
    };

    const addEmptyCategory = () => {
        const emptyItem = watchedCategories.filter(item => item.name === '');

        if (emptyItem.length > 0) {
            // 空のアイテムがある場合、最初の空アイテムにフォーカスを当てる
            const emptyIndex = watchedCategories.findIndex(
                item => item.id === emptyItem[0].id,
            );
            const inputElement = document.querySelector(
                `[data-item-id="${TMP_ID_PREFIX.SHOPPING_CATEGORY}${emptyIndex}"] input`,
            ) as HTMLInputElement;
            if (inputElement) {
                inputElement.focus();
            }
            return;
        }

        const newItem = {
            id: `${TMP_ID_PREFIX.SHOPPING_CATEGORY}${Date.now()}`,
            name: '',
            isDefault: false,
            order: watchedCategories.length,
        };

        // 末尾に追加
        append(newItem);
    };

    const onSubmit = async (data: FormData) => {
        try {
            // 空のアイテムを除いてデータ更新
            const filteredItems = data.categories.filter(
                v =>
                    (v.id?.startsWith(TMP_ID_PREFIX.SHOPPING_CATEGORY) &&
                        v.name.length > 0) ||
                    !v.id?.startsWith(TMP_ID_PREFIX.SHOPPING_CATEGORY),
            );
            await bulkUpdateShoppingCategories(
                filteredItems.map((v, idx) => ({
                    ...v,
                    order: idx,
                })),
            );
            onBack();
        } catch {
            // エラーの場合はダイアログを閉じない
            // エラーハンドリングはbulkUpdateShoppingCategoriesで行う
        }
    };

    // 初期化処理
    React.useEffect(() => {
        if (storeData?.categories?.length > 0) {
            reset({ categories: storeData.categories });
        }
    }, []);

    if (isLoading) {
        return <SpinAnimation />;
    } else {
        return (
            <form
                onSubmit={handleSubmit(onSubmit)}
                className="w-full flex flex-col gap-y-10">
                <div className="w-full flex flex-col gap-y-5">
                    <div className="flex flex-col gap-y-2">
                        <DndContext onDragEnd={handleDragEnd} sensors={sensors}>
                            {!!fields && fields.length > 0 && (
                                <SortableContext
                                    items={fields.map(
                                        (_, index) =>
                                            `${TMP_ID_PREFIX.SHOPPING_CATEGORY}${index}`,
                                    )}>
                                    {fields.map((field, index) => (
                                        <Sortable
                                            key={field.id}
                                            id={`${TMP_ID_PREFIX.SHOPPING_CATEGORY}${index}`}>
                                            <EditItem
                                                index={index}
                                                control={control}
                                                onDelete={() => remove(index)}
                                                isDefault={field.isDefault}
                                            />
                                        </Sortable>
                                    ))}
                                </SortableContext>
                            )}
                        </DndContext>
                    </div>
                    <TextButton
                        type="button"
                        onClick={() => {
                            addEmptyCategory();
                        }}
                        className="!border-none !bg-transparent">
                        <CirclePlus size={20} />
                        追加
                    </TextButton>
                </div>
                <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                    <Button
                        type="button"
                        colorVariant="gray"
                        variant="outlined"
                        onClick={onBack}>
                        戻る
                    </Button>
                    <Button type="submit">設定</Button>
                </div>
            </form>
        );
    }
};

export default EditForm;
