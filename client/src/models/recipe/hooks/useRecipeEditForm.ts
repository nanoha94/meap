"use client";
import React from 'react';
import { useForm, useWatch } from 'react-hook-form';

import { EDIT_MODE, EditMode, TMP_ID_PREFIX } from '@/constants';
import { IPostPutRecipeRequest, IRecipe, IIngredientItem } from '@/types';
import { DEFAULT_POST_DATA } from '../constants';
import { RecipeEditFormData } from '../types';
import { useRecipeApi } from './useRecipeApi';

/**
 * 食材をフォーマット
 * @param items 食材リスト
 * @returns フォーマットされた食材
 */
export const formatIngredientItems = (
    items: IIngredientItem[],
): IPostPutRecipeRequest['ingredients'] => {
    return items
        .filter(v => v.name && v.name.length > 0)
        .map((v, idx) => {
            const isNew = v.id?.startsWith(TMP_ID_PREFIX.INGREDIENT_ITEM);
            return {
                ...(v.id && !isNew ? { id: v.id } : {}),
                name: v.name,
                quantity: v.quantity,
                unitId: v.unit?.id ?? '',
                categoryId: v.categoryId,
                order: idx,
            };
        });
};

export const useRecipeEditForm = (fetchRecipe?: IRecipe, ownerUserId?: string) => {
    const [errors, setErrors] = React.useState<Record<string, string> | null>(
        null,
    );
    const methods = useForm<RecipeEditFormData>({
        defaultValues: { 
            ...DEFAULT_POST_DATA, 
            ...fetchRecipe,
            ownerUserId: fetchRecipe?.ownerUserId ?? '',
        },
    });
    const { control, handleSubmit } = methods;
    const { storeRecipe, updateRecipe } = useRecipeApi();
    const watchedName = useWatch({ control, name: 'name' });
    const watchedSteps = useWatch({ control, name: 'steps' });
    const editMode: EditMode = fetchRecipe
        ? EDIT_MODE.UPDATE
        : EDIT_MODE.CREATE;

    /**
     * 送信ボタンの無効化判定
     * 料理名が空の場合は送信ボタンを無効化
     */
    const isDisabledSendButton: boolean = React.useMemo(() => {
        if (watchedName?.length <= 0) {
            return true;
        }
        if (errors && Object.values(errors).some(v => v !== '')) {
            return true;
        }
        return false;
    }, [watchedName, errors]);

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: RecipeEditFormData) => {
        const sendData: IPostPutRecipeRequest = {
            name: data.name,
            url: data.url,
            memo: data.memo,
            servingCount: data.servingCount ?? null,
            thumbnailId: data.thumbnail?.id,
            categoryIds: data.categories.map(v => v.id),
            ownerUserId: ownerUserId ?? data.ownerUserId ?? fetchRecipe?.ownerUserId,
            ingredients: formatIngredientItems(data.ingredients),
            // stepsはstoreRecipe()/updateRecipe()でフォーマットする
        };

        if (editMode === EDIT_MODE.CREATE) {
            storeRecipe(sendData, data.thumbnail?.file ?? null, data.steps);
        } else {
            updateRecipe(
                { ...sendData, id: data.id },
                data.thumbnail?.file ?? null,
                data.steps,
            );
        }
    };

    React.useEffect(() => {
        if (watchedSteps?.length > 0) {
            watchedSteps.forEach((item, index) => {
                if (item.instruction === '' && item.image?.src.length !== 0) {
                    setErrors({
                        [`steps.${index}`]: '説明文を入力してください',
                    });
                } else {
                    setErrors({
                        [`steps.${index}`]: '',
                    });
                }
            });
        }
    }, [watchedSteps]);

    return {
        control,
        methods,
        editMode,
        isDisabledSendButton,
        onSubmit: handleSubmit(onSubmit),
        errors,
    };
};
