"use client";

import React from 'react';
import { useRouter } from 'next/navigation';
import { useForm, useWatch } from 'react-hook-form';

import { EDIT_MODE, EditMode, LINK_TO, TMP_ID_PREFIX } from '@/constants';
import { IImageWithFile, IIngredientItem, IPostPutRecipeRequest, IRecipe } from '@/types';
import { normalizeQuantityFromDisplay } from '@/utils';
import { DEFAULT_RECIPE_EDIT_FORM_DATA } from '../constants';
import { RecipeEditFormData } from '../types';
import { useRecipeAiApi } from './useRecipeAiApi';
import { useRecipeAiImport } from './useRecipeAiImport';
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
                quantityDisplay: normalizeQuantityFromDisplay(
                    v.quantityDisplay,
                    v.unit?.requiresQuantity ?? true,
                    v.quantity,
                ).quantityDisplay,
                unitId: v.unit?.id ?? '',
                categoryId: v.categoryId,
                order: idx,
            };
        });
};

/**
 * 材料の name + unitId が同一の行を検出し、重複行に対応するエラーキーを返す（サーバー側と同じキー形式）
 */
const buildDuplicateIngredientErrors = (
    items: IIngredientItem[] | undefined,
): Record<string, string> => {
    if (!items?.length) {
        return {};
    }
    const seen = new Set<string>();
    const out: Record<string, string> = {};
    items.forEach((ingredient, index) => {
        const name = ingredient.name ?? '';
        if (name === '') return;
        const key = `${name}|${ingredient.unit?.id ?? ''}`;
        if (seen.has(key)) {
            out[`ingredients.${index}.name`] = '同じ材料名と単位の組み合わせが重複しています。';
        } else {
            seen.add(key);
        }
    });
    return out;
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
            hasImageFile: !!(step.image as IImageWithFile | null)?.file,
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
        hasThumbnailFile: !!recipe.thumbnail?.file,
        categoryIds: recipe.categories.map(category => category.id).sort(),
        ingredients: formatIngredientItems(recipe.ingredients),
        steps: normalizeStepsForCompare(recipe.steps),
    };
};

export const useRecipeEditForm = (initialOwnerUserId: string, fetchedRecipe?: IRecipe) => {
    const methods = useForm<RecipeEditFormData>({
        defaultValues: getDefaultValues(fetchedRecipe, initialOwnerUserId),
    });
    const { control, handleSubmit, reset } = methods;
    const [isNameFocused, setIsNameFocused] = React.useState(false);
    const router = useRouter();
    const { storeRecipe, updateRecipe } = useRecipeApi();
    const { parseRecipeFromImage, parseRecipeFromUrl } = useRecipeAiApi();
    const { convertToFormData, applyParsedRecipeToForm } = useRecipeAiImport();
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

    const prevRecipeIdRef = React.useRef<string | undefined>(fetchedRecipe?.id);

    React.useEffect(() => {
        if (fetchedRecipe && fetchedRecipe.id !== prevRecipeIdRef.current) {
            reset(getDefaultValues(fetchedRecipe, initialOwnerUserId));
            prevRecipeIdRef.current = fetchedRecipe.id;
            setIsNameFocused(false);
        }
    }, [fetchedRecipe, reset, initialOwnerUserId]);

    // DataHandler で loginUser が後から入るため、新規作成時は ownerUserId を同期する
    React.useEffect(() => {
        if (
            editMode === EDIT_MODE.CREATE &&
            initialOwnerUserId &&
            !methods.getValues('ownerUserId')
        ) {
            methods.setValue('ownerUserId', initialOwnerUserId);
        }
    }, [editMode, initialOwnerUserId, methods]);

    /**
     * エラーを取得
     * @returns エラー
     */
    const errors = React.useMemo((): Record<string, string> | null => {
        const next: Record<string, string> = {};

        if (isNameFocused && !watchedName?.trim()) {
            next.name = '料理名を入力してください';
        }

        if (watchedSteps?.length) {
            watchedSteps.forEach((item, index) => {
                next[`steps.${index}`] =
                    item.instruction === '' && item.image?.src.length !== 0
                        ? '手順を入力してください'
                        : '';
            });
        }

        Object.assign(next, buildDuplicateIngredientErrors(watchedIngredients));

        if (watchedIngredients?.length) {
            watchedIngredients.forEach((ingredient, index) => {
                if (
                    ingredient.unit?.requiresQuantity &&
                    ingredient.name?.trim() &&
                    !ingredient.quantityDisplay?.trim()
                ) {
                    next[`ingredients.${index}.quantityDisplay`] = '数量を入力してください';
                }
            });
        }

        return Object.keys(next).length > 0 ? next : null;
    }, [isNameFocused, watchedName, watchedSteps, watchedIngredients]);

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

        let id: string | null = null;
        if (editMode === EDIT_MODE.CREATE) {
            id = await storeRecipe(sendData, data.thumbnail?.file ?? null, data.steps);
        } else {
            id = await updateRecipe(
                { ...sendData, id: data.id },
                data.thumbnail?.file ?? null,
                data.steps,
            );
        }

        if (id) {
            router.push(`${LINK_TO.RECIPE.TOP}/${id}`);
        }
    };

    /**
     * AI 読み込みで上書きされる項目に入力済みの内容があるか判定する
     * （memo / url / thumbnail / categories は上書きしない）
     */
    const hasFormContent = React.useCallback((): boolean => {
        const values = methods.getValues();
        return !!(
            values.name?.trim() ||
            values.servingCount ||
            values.ingredients?.some(ingredient => ingredient.name?.trim()) ||
            values.steps?.some(
                step => step.instruction?.trim() || step.image?.src,
            )
        );
    }, [methods]);

    /**
     * 選択した画像を AI 解析し、結果をフォームへ反映する
     */
    const importFromImage = React.useCallback(
        async (file: File): Promise<boolean> => {
            const parsed = await parseRecipeFromImage(file);
            if (!parsed) {
                return false;
            }

            const formData = convertToFormData(parsed);
            applyParsedRecipeToForm(formData, methods);
            return true;
        },
        [parseRecipeFromImage, convertToFormData, applyParsedRecipeToForm, methods],
    );

    /**
     * 入力 URL を AI 解析し、結果をフォームへ反映する
     */
    const importFromUrl = React.useCallback(
        async (url: string): Promise<boolean> => {
            const parsed = await parseRecipeFromUrl(url);
            if (!parsed) {
                return false;
            }

            const formData = convertToFormData(parsed);
            applyParsedRecipeToForm(formData, methods);
            methods.setValue('url', url);
            return true;
        },
        [parseRecipeFromUrl, convertToFormData, applyParsedRecipeToForm, methods],
    );

    return {
        control,
        methods,
        editMode,
        isDisabledSendButton,
        onSubmit: handleSubmit(onSubmit),
        errors,
        setIsNameFocused,
        hasFormContent,
        importFromImage,
        importFromUrl,
    };
};
