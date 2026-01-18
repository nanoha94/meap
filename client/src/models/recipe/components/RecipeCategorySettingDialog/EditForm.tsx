'use client';
import React from 'react';
import {
    Button,
    TextButton,
    GrippableHorizontalItem,
} from '@/components/common';
import { DndSortableList } from '@/components/dnd';
import { CirclePlus } from 'lucide-react';
import { Controller, useFieldArray, useForm } from 'react-hook-form';
import { defaultRecipeCategory } from '../../constants';
import { useRecipeCategoryApi } from '../../hooks';
import { IRecipeCategory } from '@/types/api';
import { DND_SORTABLE_LIST_TYPE, TMP_ID_PREFIX } from '@/constants';

interface FormData {
    categories: IRecipeCategory[];
}

interface Props {
    onClose: () => void;
}

const EditForm: React.FC<Props> = ({ onClose }) => {
    const { storeData, bulkUpdateRecipeCategories } = useRecipeCategoryApi();
    const prefix = TMP_ID_PREFIX.RECIPE_CATEGORY;

    const { control, handleSubmit, watch, reset } = useForm<FormData>({
        defaultValues: {
            categories: [defaultRecipeCategory],
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
            ...defaultRecipeCategory,
            id: `${prefix}${Date.now()}`,
        };

        // 末尾に追加
        append(newItem);
    };

    const removeCategory = React.useCallback(
        (index: number) => {
            if (fields.length <= 1) {
                addEmptyCategory();
            }
            remove(index);
        },
        [fields],
    );

    /**
     * フォームの送信
     */
    const onSubmit = (data: FormData) => {
        try {
            // 空のアイテムを除いてデータ更新
            const filteredItems = data.categories.filter(
                v =>
                    (v.id?.startsWith(prefix) &&
                        v.name &&
                        v.name?.length > 0) ||
                    !v.id?.startsWith(prefix),
            );
            bulkUpdateRecipeCategories(
                filteredItems.map((v, idx) => ({
                    ...v,
                    order: idx,
                })),
            );
            onClose();
        } catch {
            // エラーの場合はダイアログを閉じない
            // エラーハンドリングはbulkUpdateRecipeCategoriesで行う
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
                    <DndSortableList
                        type={DND_SORTABLE_LIST_TYPE.LIST}
                        items={fields}
                        prefix={prefix}
                        onDragEnd={(oldIndex, newIndex) => {
                            move(oldIndex, newIndex);
                        }}
                        renderItem={(item, index) => (
                            <GrippableHorizontalItem
                                hasDeleteButton={true}
                                isDisabledDeleteButton={
                                    index === 0 &&
                                    watchedCategories?.length === 1 &&
                                    watchedCategories[0].name === ''
                                }
                                onDelete={() => removeCategory(index)}>
                                <Controller
                                    control={control}
                                    name={`categories.${index}.name`}
                                    render={({ field }) => (
                                        <input
                                            {...field}
                                            data-item-id={`${prefix}${index}`}
                                            type="text"
                                            placeholder="カテゴリー名を入力"
                                            autoFocus
                                            className="py-2 px-4 flex-1 outline-none bg-white rounded-lg border border-gray-main"
                                        />
                                    )}
                                />
                            </GrippableHorizontalItem>
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
