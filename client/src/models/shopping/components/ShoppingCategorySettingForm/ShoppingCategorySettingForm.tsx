'use client';
import { Button, TextButton } from '@/components/common';
import { DndSortableList } from '@/components/dnd';
import { CirclePlus } from 'lucide-react';
import React from 'react';
import { IShoppingCategory } from '@/types/api';
import { Controller, useFieldArray, useForm } from 'react-hook-form';
import { useShoppingCategoryApi } from '../../hooks';
import { TMP_ID_PREFIX } from '@/constants';
import GrippableEditItem from '@/components/common/GrippableEditItem';

interface FormData {
    categories: IShoppingCategory[];
}

interface Props {
    onClose: () => void;
}

const ShoppingCategorySettingForm: React.FC<Props> = ({ onClose }) => {
    const { storeData, bulkUpdateShoppingCategories } =
        useShoppingCategoryApi();
    const prefix = TMP_ID_PREFIX.SHOPPING_CATEGORY;

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
            id: `${prefix}${Date.now()}`,
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
                    (v.id?.startsWith(prefix) && v.name.length > 0) ||
                    !v.id?.startsWith(prefix),
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
                    <DndSortableList
                        items={fields}
                        prefix={prefix}
                        onDragEnd={(oldIndex, newIndex) =>
                            move(oldIndex, newIndex)
                        }
                        renderItem={(item, index) => (
                            <GrippableEditItem
                                hasDeleteButton={true}
                                isDisabledDeleteButton={item.isDefault}
                                onDelete={() => remove(index)}>
                                <Controller
                                    control={control}
                                    name={`categories.${index}.name`}
                                    render={({ field }) => (
                                        <input
                                            {...field}
                                            data-item-id={`${TMP_ID_PREFIX.SHOPPING_CATEGORY}${index}`}
                                            type="text"
                                            placeholder="カテゴリー名を入力"
                                            className="py-2 px-4 flex-1 outline-none bg-white rounded-lg border border-gray-main"
                                        />
                                    )}
                                />
                            </GrippableEditItem>
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

export default ShoppingCategorySettingForm;
