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
import { useRecipeListStateStore } from './useRecipeListStateStore';
import { RECIPES_PER_PAGE, sortOptions } from '../constants';
import { getBrowserQueryString } from '../utils';

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
    // store
    const incrementLoadingCount = useGlobalStore(
        (state) => state.incrementLoadingCount,
    );
    const decrementLoadingCount = useGlobalStore(
        (state) => state.decrementLoadingCount,
    );
    const listSortOptions = useRecipeListStateStore(state => state.listSortOptions);
    const listFilterOptions = useRecipeListStateStore(state => state.listFilterOptions);
    const listCurrentPage = useRecipeListStateStore(state => state.listCurrentPage);

    // hook
    const { bulkUploadImage } = useImageApi();
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

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

    /**
     * レシピ一覧を取得する（ストアデータは更新しない）
     * @param sortOptionId 並び替えオプションID
     * @param filterOptions フィルターオプション
     * @param page ページ番号
     * @returns レシピ一覧
     */
    const fetchRecipes = React.useCallback(
        async (sortOptionId?: string, filterOptions?: RecipeFilterFormData, page?: number) => {
            // 重複リクエスト防止
            if (isFetchRequestRef.current) {
                return;
            }

            // パラメータをセット
            const params: IGetRecipeIndexRequest = {
                sort: sortOptions.find(v => v.id === sortOptionId)?.sort ?? sortOptions[0].sort,
                order: sortOptions.find(v => v.id === sortOptionId)?.order ?? sortOptions[0].order,
                recipe_name: filterOptions?.recipeName,
                ingredient_name: filterOptions?.ingredientName,
                category_ids: filterOptions?.categoryIds ? filterOptions?.categoryIds : [],
                last_planned_date_from: filterOptions?.lastPlannedDateFrom,
                last_planned_date_to: filterOptions?.lastPlannedDateTo,
                limit: RECIPES_PER_PAGE,
                offset: ((page ?? 1) - 1) * RECIPES_PER_PAGE,
            };

            try {
                isFetchRequestRef.current = true;
                incrementLoadingCount();

                const { data: responseData } = await axios.get('/recipes', {
                    params,
                    timeout: TIMEOUT_MS,
                });
                if (responseData.success) {
                    return {
                        recipes: responseData.data,
                        pageSize: Math.ceil((responseData?.total ?? 0) / RECIPES_PER_PAGE),
                        currentPage: page ?? 1,
                    };
                }
            } catch (error) {
                handleApiError(error);
                return {
                    recipes: [],
                    pageSize: 0,
                    currentPage: 1,
                };
            } finally {
                isFetchRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [incrementLoadingCount, decrementLoadingCount, handleApiError],
    );

    /**
     * レシピを作成する
     * @param data 作成するレシピデータ
     * @param thumbnail サムネイル画像
     * @param steps 手順リスト
     * @returns void
     */
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
                    router.push(`/recipe?${getBrowserQueryString(listSortOptions, listFilterOptions, listCurrentPage)}`);
                    router.refresh();
                    addSnackbar(
                        'success',
                        responseData.message ??
                        'リクエストが正常に完了しました',
                    );
                }
            } catch (error) {
                handleApiError(error);
            } finally {
                isStoreRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [
            listSortOptions,
            listFilterOptions,
            listCurrentPage,
            router,
            incrementLoadingCount,
            decrementLoadingCount,
            bulkUploadImage,
            uploadStepImages,
            addSnackbar,
            handleApiError,
        ],
    );

    /**
     * レシピを更新する
     * @param data 更新するレシピデータ
     * @param thumbnail サムネイル画像
     * @param steps 手順リスト
     * @returns void
     */
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
                    router.refresh();
                    addSnackbar(
                        'success',
                        responseData.message ??
                        'リクエストが正常に完了しました',
                    );

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

    /**
     * レシピを削除する
     * @param id 削除するレシピのID
     * @returns void
     */
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
                router.push('/recipe/');
                router.refresh();
                addSnackbar('success', responseData.message ?? 'リクエストが正常に完了しました');
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
