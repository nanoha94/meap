'use client';

import React from 'react';
import { CirclePlus } from 'lucide-react';
import { Controller, useFieldArray, useForm, useWatch } from 'react-hook-form';

import {
    Button,
    DndSortableList,
    GrippableHorizontalItem,
    TextButton,
} from '@/components';
import { BUTTON_TYPE, BUTTON_VARIANT, COLOR_VARIANT, DND_SORTABLE_LIST_TYPE, TMP_ID_PREFIX } from '@/constants';
import { useDialog } from '@/hooks';
import { defaultIngredientCategory, useIngredientCategoryApi } from '@/models/ingredient';
import { IIngredientCategory } from '@/types';

interface FormData {
    categories: IIngredientCategory[];
}

const IngredientCategoryEditForm: React.FC = () => {
    const { closeDialog, updateCurrentDialogConfig } = useDialog();
    const { storeData, bulkUpdateIngredientCategories } =
        useIngredientCategoryApi();
    const prefix = TMP_ID_PREFIX.INGREDIENT_CATEGORY;

    const { control, handleSubmit, reset } = useForm<FormData>({
        defaultValues: {
            categories: [],
        },
    });

    const { fields, append, remove, move } = useFieldArray<FormData>({
        control,
        name: 'categories',
    });

    /**
     * カテゴリーの監視
     */
    const watchedCategories = useWatch({ control, name: 'categories' });

    /**
     * 送信ボタンの無効化判定
     * カテゴリーが変更されていない場合は送信ボタンを無効化
     */
    const isDisabledSendButton = React.useMemo(() => {
        return JSON.stringify(watchedCategories.filter(item => item.name !== '')) === JSON.stringify(storeData.categories);
    }, [watchedCategories, storeData.categories]);

    /**
     * 閉じる前確認の要否をフォーム状態に合わせて更新
    */
    React.useEffect(() => {
        updateCurrentDialogConfig({ isCheckBeforeClose: !isDisabledSendButton });
    }, [isDisabledSendButton, updateCurrentDialogConfig]);

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
        try {
            // 空のアイテムを除いてデータ更新
            const filteredItems = data.categories.filter(
                v =>
                    (v.id?.startsWith(prefix) && v.name.length > 0) ||
                    !v.id?.startsWith(prefix),
            );
            bulkUpdateIngredientCategories(
                filteredItems.map((v, idx) => ({
                    ...v,
                    order: idx,
                })),
            );
            closeDialog();
        } catch {
            // エラーの場合はダイアログを閉じない
            // エラーハンドリングはbulkUpdateIngredientCategoriesで行う
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
                                isDisabledDeleteButton={item.isDefault}
                                onDelete={() => remove(index)}>
                                <Controller
                                    control={control}
                                    name={`categories.${index}.name`}
                                    render={({ field }) => (
                                        <input
                                            {...field}
                                            data-item-id={`${TMP_ID_PREFIX.INGREDIENT_CATEGORY}${index}`}
                                            type="text"
                                            placeholder="カテゴリー名を入力"
                                            className="py-2 px-4 flex-1 outline-none bg-white rounded-lg border border-gray-main"
                                        />
                                    )}
                                />
                            </GrippableHorizontalItem>
                        )}
                    />
                </div>
                <TextButton
                    type={BUTTON_TYPE.BUTTON}
                    onClick={addEmptyCategory}
                    className="!border-none !bg-transparent hover:!bg-gray-light">
                    <CirclePlus size={20} />
                    追加
                </TextButton>
            </div>
            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                <Button
                    type={BUTTON_TYPE.BUTTON}
                    colorVariant={COLOR_VARIANT.GRAY}
                    variant={BUTTON_VARIANT.OUTLINED}
                    onClick={() => closeDialog()}>
                    戻る
                </Button>
                <Button type={BUTTON_TYPE.SUBMIT} disabled={isDisabledSendButton}>設定</Button>
            </div>
        </form>
    );
};

export default IngredientCategoryEditForm;
