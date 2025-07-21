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
import { CirclePlus } from 'lucide-react';
import React from 'react';
import { IShoppingCategory } from '@/types/api';
import { useFieldArray, useForm } from 'react-hook-form';
import EditItem from './EditItem';
import Sortable from '@/components/dnd/Sortable';
import { useShoppingCategories } from '../../hooks';
import { DRAG_ACTIVATION_DISTANCE, TMP_ID_PREFIX } from '../../constants';

interface FormData {
    categories: IShoppingCategory[];
}

interface Props {
    onClose: () => void;
}

const EditForm: React.FC<Props> = ({ onClose }) => {
    const { storeData, bulkUpdateShoppingCategories } = useShoppingCategories();

    const { control, handleSubmit, watch, reset } = useForm<FormData>({
        defaultValues: {
            categories: [],
        },
    });

    const { fields, append, remove, move } = useFieldArray({
        control,
        name: 'categories',
    });

    /**
     * カテゴリーの監視
     */
    const watchedCategories = watch('categories');

    /**
     * センサー
     */
    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                distance: DRAG_ACTIVATION_DISTANCE,
            },
        }),
        useSensor(KeyboardSensor),
    );

    /**
     * ドラッグ終了
     */
    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
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

    /**
     * 空のカテゴリーを追加
     */
    const addEmptyCategory = () => {
        const emptyItem = watchedCategories.filter(item => item.name === '');

        if (emptyItem.length > 0) {
            // 空のアイテムがある場合、最初の空アイテムにフォーカスを当てる
            const emptyIndex = watchedCategories.findIndex(
                item => item.id === emptyItem[0].id,
            );
            const inputElement = document.querySelector(
                `[data-item-id="${TMP_ID_PREFIX.SHOPPING_CATEGORY}${emptyIndex}"]`,
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

    /**
     * フォームの送信
     */
    const onSubmit = (data: FormData) => {
        try {
            // 空のアイテムを除いてデータ更新
            const filteredItems = data.categories.filter(
                v =>
                    (v.id?.startsWith(TMP_ID_PREFIX.SHOPPING_CATEGORY) &&
                        v.name.length > 0) ||
                    !v.id?.startsWith(TMP_ID_PREFIX.SHOPPING_CATEGORY),
            );
            bulkUpdateShoppingCategories(
                filteredItems.map((v, idx) => ({
                    ...v,
                    order: idx,
                })),
            );
            onClose();
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
                    onClick={addEmptyCategory}
                    className="!border-none !bg-transparent hover:!bg-gray-light">
                    <CirclePlus size={20} />
                    追加
                </TextButton>
            </div>
            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                <Button
                    type="button"
                    colorVariant="gray"
                    variant="outlined"
                    onClick={onClose}>
                    戻る
                </Button>
                <Button type="submit">設定</Button>
            </div>
        </form>
    );
};

export default EditForm;
