"use client";

import React from 'react';
import { useForm, useWatch } from 'react-hook-form';

import { EDIT_MODE, EditMode, TMP_ID_PREFIX } from '@/constants';
import { IIngredientItem, IPostPutRecipeRequest, IRecipe } from '@/types';
import { DEFAULT_RECIPE_EDIT_FORM_DATA } from '../constants';
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

/**
 * デフォルト値を取得 （fetchedRecipeが渡ってきた場合はそれをベースにデフォルト値を取得）
 * @param fetchedRecipe 
 * @param initialOwnerUserId 
 * @returns デフォルト値
 */
const getDefaultValues = (fetchedRecipe?: IRecipe, initialOwnerUserId?: string): RecipeEditFormData => ({
    ...DEFAULT_RECIPE_EDIT_FORM_DATA,
    ...fetchedRecipe,
    ownerUserId: fetchedRecipe?.ownerUserId ?? initialOwnerUserId ?? '',
    thumbnail: fetchedRecipe?.thumbnail
        ? {
            ...fetchedRecipe.thumbnail,
            file: null,
        }
        : null,
    steps: (fetchedRecipe?.steps ?? []).map(step => ({
        ...step,
        image: step.image
            ? {
                ...step.image,
                file: null,
            }
            : null,
    })),
});

/**
 * 手順を比較用のデータに変換
 * @param steps 手順
 * @returns 比較用の手順
 */
const normalizeStepsForCompare = (steps: RecipeEditFormData['steps'] | IRecipe['steps']) =>
    steps
        .filter(step => step.instruction !== '' || (step.image?.id ?? step.image?.src ?? '') !== '')
        .map((step, index) => ({
            instruction: step.instruction,
            imageId: step.image?.id ?? null,
            order: index,
        }));


/**
 * レシピを比較用のデータに変換
 * @param recipe レシピ
 * @returns 比較用のデータ
 */
const normalizeRecipeForCompare = (recipe: Omit<RecipeEditFormData, 'id' | 'cookingTime'>) => {
    return {
        ownerUserId: recipe.ownerUserId ?? '',
        name: recipe.name,
        url: recipe.url ?? '',
        memo: recipe.memo ?? '',
        servingCount: recipe.servingCount ?? null,
        thumbnailId: recipe.thumbnail?.id ?? null,
        categoryIds: recipe.categories.map(category => category.id).sort(),
        ingredients: formatIngredientItems(recipe.ingredients),
        steps: normalizeStepsForCompare(recipe.steps),
    };
};

export const useRecipeEditForm = (initialOwnerUserId: string, fetchedRecipe?: IRecipe) => {
    const [errors, setErrors] = React.useState<Record<string, string> | null>(
        null,
    );
    const methods = useForm<RecipeEditFormData>({
        defaultValues: getDefaultValues(fetchedRecipe, initialOwnerUserId),
    });
    const { control, handleSubmit, reset } = methods;
    const { storeRecipe, updateRecipe } = useRecipeApi();
    const watchedName = useWatch({ control, name: 'name' });
    const watchedUrl = useWatch({ control, name: 'url' });
    const watchedMemo = useWatch({ control, name: 'memo' });
    const watchedServingCount = useWatch({ control, name: 'servingCount' });
    const watchedThumbnail = useWatch({ control, name: 'thumbnail' });
    const watchedCategories = useWatch({ control, name: 'categories' });
    const watchedIngredients = useWatch({ control, name: 'ingredients' });
    const watchedSteps = useWatch({ control, name: 'steps' });
    const watchedOwnerUserId = useWatch({ control, name: 'ownerUserId' });
    const editMode: EditMode = fetchedRecipe
        ? EDIT_MODE.UPDATE
        : EDIT_MODE.CREATE;

    React.useEffect(() => {
        if (fetchedRecipe) {
            reset(getDefaultValues(fetchedRecipe, initialOwnerUserId));
        }
    }, [fetchedRecipe, reset]);

    /**
     * 送信ボタンの無効化判定
     * 料理名が空の場合は送信ボタンを無効化
     * 更新モードの場合、フォームが変更されていない場合は送信ボタンを無効化
     */
    const isDisabledSendButton: boolean = React.useMemo(() => {
        if (watchedName?.length <= 0) {
            return true;
        }
        if (errors && Object.values(errors).some(v => v !== '')) {
            return true;
        }

        // 更新モードの場合、フォームが変更されていない場合は送信ボタンを無効化
        if (editMode === EDIT_MODE.UPDATE) {
            if (!fetchedRecipe) {
                return true;
            }

            const currentRecipe = {
                ownerUserId: watchedOwnerUserId ?? '',
                name: watchedName ?? '',
                url: watchedUrl ?? '',
                memo: watchedMemo ?? '',
                servingCount: watchedServingCount ?? null,
                thumbnail: watchedThumbnail ?? null,
                categories: watchedCategories ?? [],
                ingredients: watchedIngredients ?? [],
                steps: watchedSteps ?? [],
            };

            if (
                JSON.stringify(normalizeRecipeForCompare(getDefaultValues(fetchedRecipe, initialOwnerUserId))) ===
                JSON.stringify(normalizeRecipeForCompare(currentRecipe))
            ) {
                return true;
            }
        }
        return false;
    }, [
        watchedName,
        watchedUrl,
        watchedMemo,
        watchedServingCount,
        watchedThumbnail,
        watchedCategories,
        watchedIngredients,
        watchedSteps,
        watchedOwnerUserId,
        errors,
        editMode,
        fetchedRecipe,
        initialOwnerUserId,
    ]);

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = async (data: RecipeEditFormData) => {
        const sendData: IPostPutRecipeRequest = {
            name: data.name,
            url: data.url,
            memo: data.memo,
            servingCount: data.servingCount ?? null,
            thumbnailId: data.thumbnail?.id,
            categoryIds: data.categories.map(v => v.id),
            ownerUserId: data.ownerUserId,
            ingredients: formatIngredientItems(data.ingredients),
            // stepsはstoreRecipe()/updateRecipe()でフォーマットする
        };

        if (editMode === EDIT_MODE.CREATE) {
            await storeRecipe(sendData, data.thumbnail?.file ?? null, data.steps);
        } else {
            await updateRecipe(
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
