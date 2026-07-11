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
import { useDialog, useFocusItem } from '@/hooks';
import { DEFAULT_RECIPE_CATEGORY, useRecipeCategoryApi } from '@/models/recipe';
import { IRecipeCategory } from '@/types';
import { focusItemById } from '@/utils';


interface FormData {
    categories: IRecipeCategory[];
}

const RecipeCategoryEditForm: React.FC = () => {
    const { closeDialog, updateCurrentDialogConfig } = useDialog();
    const { storeData, bulkUpdateRecipeCategories } = useRecipeCategoryApi();
    const prefix = TMP_ID_PREFIX.RECIPE_CATEGORY;

    const { control, handleSubmit, reset } = useForm<FormData>({
        defaultValues: {
            categories: [DEFAULT_RECIPE_CATEGORY],
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
    const { setFocusTargetId } = useFocusItem(watchedCategories);

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
    const addEmptyCategory = React.useCallback(() => {
        const emptyItem = watchedCategories.filter(item => item.name === '');

        if (emptyItem.length > 0) {
            // 空のアイテムがある場合、最初の空アイテムにフォーカスを当てる
            if (emptyItem[0].id) {
                focusItemById(emptyItem[0].id);
            }
            return;
        }

        const newItem = {
            ...DEFAULT_RECIPE_CATEGORY,
            id: `${prefix}${Date.now()}`,
        };

        // 末尾に追加
        append(newItem);
        setFocusTargetId(newItem.id);
    }, [watchedCategories, append, prefix, setFocusTargetId]);

    /**
     * カテゴリーを削除
     */
    const removeCategory = React.useCallback(
        (index: number) => {
            if (fields.length <= 1) {
                addEmptyCategory();
            }
            remove(index);
        },
        [fields, addEmptyCategory, remove],
    );

    /**
     * フォームの送信
     */
    const onSubmit = async (data: FormData) => {
        try {
            // 空のアイテムを除いてデータ更新
            const filteredItems = data.categories.filter(
                v =>
                    (v.id?.startsWith(prefix) &&
                        v.name &&
                        v.name?.length > 0) ||
                    !v.id?.startsWith(prefix),
            );
            const success = await bulkUpdateRecipeCategories(
                filteredItems.map((v, idx) => ({
                    ...v,
                    order: idx,
                })),
            );
            if (success) {
                closeDialog(false);
            }
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
    }, [reset, storeData.categories]);

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
                                            data-item-id={watchedCategories[index]?.id}
                                            type="text"
                                            placeholder="カテゴリー名を入力"
                                            autoFocus={
                                                index === 0 &&
                                                watchedCategories?.length === 1 &&
                                                watchedCategories[0].name === ''
                                            }
                                            className="w-full py-2 px-4 flex-1 outline-black bg-white rounded-lg border border-gray-main"
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

export default RecipeCategoryEditForm;
