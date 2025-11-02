'use client';
import React from 'react';
import { useFieldArray, useForm } from 'react-hook-form';
import { Button, TextButton } from '@/components/common';
import { CirclePlus } from 'lucide-react';
import { DndSortableList } from '@/components/dnd';
import EditItem from './EditItem';
import { TMP_ID_PREFIX } from '@/constants/tmpIdPrefix';
import { defaultIngredientCategory } from '@/models/ingredient/constants';
import { IIngredientCategory } from '@/types/api/ingredient';

interface Props {
    onClose: () => void;
}

interface FormData {
    categories: IIngredientCategory[];
}

const EditForm: React.FC<Props> = ({ onClose }) => {
    const prefix = TMP_ID_PREFIX.INGREDIENT_CATEGORY;

    const { control, handleSubmit, watch } = useForm<FormData>({
        defaultValues: {
            categories: [defaultIngredientCategory],
        },
    });

    const { fields, append, remove, move } = useFieldArray<FormData>({
        control,
        name: 'categories',
    });

    /**
     * カテゴリーの監視
     */
    const watchedCategories = watch('categories');

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
                `[data-item-id="${prefix}${emptyIndex}"]`,
            ) as HTMLInputElement;
            if (inputElement) {
                inputElement.focus();
            }
            return;
        }

        const newItem = {
            ...defaultIngredientCategory,
            id: `${prefix}${Date.now()}`,
        };

        // 末尾に追加
        append(newItem);
    };

    /**
     * フォームの送信
     */
    const onSubmit = (data: FormData) => {
        console.log(data);
        onClose();
    };

    return (
        <form
            onSubmit={handleSubmit(onSubmit)}
            className="w-full flex flex-col gap-y-10">
            <div className="w-full flex flex-col gap-y-5">
                <div className="flex flex-col gap-y-2">
                    <DndSortableList
                        items={fields}
                        prefix={prefix}
                        onDragEnd={(oldIndex, newIndex) => {
                            move(oldIndex, newIndex);
                        }}
                        renderItem={(item, index) => (
                            // TODO: GrippableEditItemを使用するように修正
                            <EditItem
                                index={index}
                                control={control}
                                onDelete={() => remove(index)}
                            />
                        )}
                    />
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
