"use client";
import React from 'react';
import { useRouter } from 'next/navigation';

import { TIMEOUT_MS, TMP_ID_PREFIX } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { useImageApi } from '@/models/image';
import { useGlobalStore } from '@/stores';
import {
    IGetRecipeIndexRequest,
    IPostPutRecipeRequest,
    IPostRecipeResponse,
    IRecipeStep,
} from '@/types';
import { RecipeFilterFormData, RecipeStepEditFormData } from '../types';
import { useRecipeStore } from './useRecipeStores';
import { sortOptions } from '../constants';
import { getQueryString } from '../utils';

/**
 * 手順をフォーマット
 * @param items 手順リスト
 * @returns フォーマットされた手順
 */
export const formatStepItems = (
    items: IRecipeStep[],
): IPostPutRecipeRequest['steps'] => {
    return items
        .filter(v => v.instruction && v.instruction.length > 0)
        .map((v, idx) => {
            const isNew = v.id?.startsWith(TMP_ID_PREFIX.RECIPE_STEP);
            return {
                ...(v.id && !isNew ? { id: v.id } : {}),
                instruction: v.instruction,
                imageId: v.image?.id,
                order: idx,
            };
        });
};

export const useRecipeApi = () => {
    const { incrementLoadingCount, decrementLoadingCount } = useGlobalStore();
    const setRecipes = useRecipeStore(state => state.setRecipes);
    const { listSortOptions, listFilterOptions } = useRecipeStore();
    const { bulkUploadImage } = useImageApi();
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const setListSortOptions = useRecipeStore(state => state.setListSortOptions);
    const setListFilterOptions = useRecipeStore(state => state.setListFilterOptions);

    // 重複リクエスト防止用のフラグ
    const isFetchRequestRef = React.useRef(false);
    const isStoreRequestRef = React.useRef(false);
    const isUpdateRequestRef = React.useRef(false);
    const isDeleteRequestRef = React.useRef(false);

    /**
     * 手順画像のアップロード
     * @param steps 手順リスト
     * @returns アップロードされた手順リスト
     * @description
     * 手順画像をアップロードし、アップロードされた画像IDを反映した新しい手順リストを返す
     */
    const uploadStepImages = React.useCallback(
        async (steps: RecipeStepEditFormData[]) => {
            // 新規アップロードが必要なファイルとそのステップインデックスを取得
            const filesToUpload: { file: File; stepIndex: number }[] = [];
            steps.forEach((step, index) => {
                if (step.image?.file) {
                    filesToUpload.push({
                        file: step.image.file,
                        stepIndex: index,
                    });
                }
            });

            // アップロードされた画像IDをマッピング
            const uploadedImageMap = new Map<number, string>();
            if (filesToUpload.length > 0) {
                // 画像アップロード
                const images = await bulkUploadImage(
                    filesToUpload.map(v => v.file),
                );
                if (images.success) {
                    filesToUpload.forEach((item, uploadIndex) => {
                        const imageId = images.data[uploadIndex]?.id;
                        if (imageId) {
                            uploadedImageMap.set(item.stepIndex, imageId);
                        }
                    });
                }
            }

            // アップロードされた画像IDを反映した新しい配列を作成
            const stepsWithImageIds = steps.map((step, index) => ({
                ...step,
                image: step.image
                    ? {
                        ...step.image,
                        id: uploadedImageMap.get(index) ?? step.image.id,
                    }
                    : null,
            }));

            return stepsWithImageIds;
        },
        [bulkUploadImage],
    );

    const fetchRecipes = React.useCallback(
        async (sortOptionId?: string, filterOptions?: RecipeFilterFormData) => {
            // 重複リクエスト防止
            if (isFetchRequestRef.current) {
                return;
            }

            // パラメータをセット（ストアの値を使用）
            const params: IGetRecipeIndexRequest = {
                sort: listSortOptions.sort,
                order: listSortOptions.order,
                recipe_name: listFilterOptions?.recipeName,
                ingredient_name: listFilterOptions?.ingredientName,
                category_ids: listFilterOptions?.categoryId ? [listFilterOptions?.categoryId] : [], // TODO: ひとまずはカテゴリ１つとしておく。後で配列に変更する
                last_planned_date_from: listFilterOptions?.lastPlannedDateFrom,
                last_planned_date_to: listFilterOptions?.lastPlannedDateTo,
            };

            // 並び替えパラメータをセット
            if (sortOptionId) {
                setListSortOptions(sortOptionId);
                params.sort = sortOptions.find(v => v.id === sortOptionId)?.sort;
                params.order = sortOptions.find(v => v.id === sortOptionId)?.order;
            }

            // フィルターパラメータをセット
            if (filterOptions) {
                setListFilterOptions(filterOptions);
                params.recipe_name = filterOptions?.recipeName;
                params.ingredient_name = filterOptions?.ingredientName;
                params.category_ids = filterOptions?.categoryId ? [filterOptions?.categoryId] : []; // TODO: ひとまずはカテゴリ１つとしておく。後で配列に変更する
                params.last_planned_date_from = filterOptions?.lastPlannedDateFrom;
                params.last_planned_date_to = filterOptions?.lastPlannedDateTo;
            }

            try {
                isFetchRequestRef.current = true;
                incrementLoadingCount();

                const { data: responseData } = await axios.get('/recipes', {
                    params,
                    timeout: TIMEOUT_MS,
                });
                if (responseData.success) {
                    setRecipes(responseData.data, responseData.total);
                }
            } catch (error) {
                handleApiError(error);
            } finally {
                isFetchRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [incrementLoadingCount, decrementLoadingCount, setRecipes, handleApiError, listSortOptions, listFilterOptions],
    );

    const storeRecipe = React.useCallback(
        async (
            data: IPostPutRecipeRequest,
            thumbnail: File | null,
            steps: RecipeStepEditFormData[],
        ) => {
            // 重複リクエスト防止
            if (isStoreRequestRef.current) {
                return;
            }

            const sendData: IPostPutRecipeRequest = data;

            try {
                isStoreRequestRef.current = true;
                incrementLoadingCount();

                // サムネイル画像のアップロード
                if (thumbnail) {
                    const images = await bulkUploadImage([thumbnail]);
                    if (images.success) {
                        sendData.thumbnailId = images.data[0]?.id;
                    }
                }

                // 手順画像のアップロード
                if (steps.length > 0) {
                    const stepsWithImageIds = await uploadStepImages(steps);
                    sendData.steps = formatStepItems(stepsWithImageIds);
                }

                // APIリクエスト
                const { data: responseData } = await axios.post<IPostRecipeResponse>(
                    `/recipes`,
                    sendData,
                    {
                        timeout: TIMEOUT_MS,
                    },
                );
                if (responseData.success) {
                    router.push(`/recipe?${getQueryString(listSortOptions, listFilterOptions)}`);
                    addSnackbar(
                        'success',
                        responseData.message ??
                        'リクエストが正常に完了しました',
                    );
                    await fetchRecipes();
                }
            } catch (error) {
                handleApiError(error);
            } finally {
                isStoreRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [incrementLoadingCount, decrementLoadingCount, bulkUploadImage, uploadStepImages, router, addSnackbar, handleApiError],
    );

    const updateRecipe = React.useCallback(
        async (
            data: IPostPutRecipeRequest,
            thumbnail: File | null,
            steps: RecipeStepEditFormData[],
        ) => {
            // 重複リクエスト防止
            if (isUpdateRequestRef.current) {
                return;
            }

            const sendData: IPostPutRecipeRequest = data;

            try {
                isUpdateRequestRef.current = true;
                incrementLoadingCount();

                // サムネイル画像のアップロード
                if (thumbnail) {
                    const images = await bulkUploadImage([thumbnail]);
                    if (images.success) {
                        sendData.thumbnailId = images.data[0]?.id;
                    }
                }

                // 手順画像のアップロード
                if (steps.length > 0) {
                    const stepsWithImageIds = await uploadStepImages(steps);
                    sendData.steps = formatStepItems(stepsWithImageIds);
                }

                // APIリクエスト
                const { data: responseData } = await axios.put(`/recipes/${data.id}`, sendData, {
                    timeout: TIMEOUT_MS,
                });
                if (responseData.success) {
                    router.push(`/recipe/${data.id}`);
                    addSnackbar(
                        'success',
                        responseData.message ??
                        'リクエストが正常に完了しました',
                    );

                    await fetchRecipes();
                }
            } catch (error) {
                handleApiError(error);
            } finally {
                isUpdateRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [incrementLoadingCount, decrementLoadingCount, bulkUploadImage, uploadStepImages, router, addSnackbar, handleApiError],
    );

    const deleteRecipe = React.useCallback(async (id: string) => {
        // 重複リクエスト防止
        if (isDeleteRequestRef.current) {
            return;
        }

        try {
            isDeleteRequestRef.current = true;
            incrementLoadingCount();
            const { data: responseData } = await axios.delete(`/recipes/${id}`, {
                timeout: TIMEOUT_MS,
            });
            if (responseData.success) {
                addSnackbar('success', responseData.message ?? 'リクエストが正常に完了しました');
                router.push('/recipe/');
            }
        } catch (error) {
            handleApiError(error);
        } finally {
            isDeleteRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [incrementLoadingCount, decrementLoadingCount, router, addSnackbar, handleApiError]);

    return {
        fetchRecipes,
        storeRecipe,
        updateRecipe,
        deleteRecipe,
    };
};
