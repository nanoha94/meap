'use client';
import React from 'react';

import { TIMEOUT_MS, TMP_ID_PREFIX } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { useImageApi } from '@/models/image';
import { useGlobalStore } from '@/stores';
import {
    IDeleteRecipeResponse,
    IGetRecipeIndexRequest,
    IGetRecipeIndexResponse,
    IPostPutRecipeRequest,
    IPostRecipeResponse,
    IPutRecipeResponse,
    IRecipeStep,
} from '@/types';
import { RECIPES_PER_PAGE, sortOptions } from '../constants';
import { RecipeFilterFormData, RecipeStepEditFormData } from '../types';

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
    // hook
    const { bulkUploadImage } = useImageApi();
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

                const { data: responseData } = await axios.get<IGetRecipeIndexResponse>(
                    '/recipes',
                    {
                        params,
                        timeout: TIMEOUT_MS,
                    },
                );
                if (responseData.success) {
                    return {
                        recipes: responseData.data,
                        pageSize: Math.ceil((responseData?.total ?? 0) / RECIPES_PER_PAGE),
                        currentPage: page ?? 1,
                    };
                }
                addSnackbar(
                    'error',
                    responseData.message || 'レシピ一覧の取得に失敗しました',
                );
                return {
                    recipes: [],
                    pageSize: 0,
                    currentPage: page ?? 1,
                };
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
        [incrementLoadingCount, decrementLoadingCount, handleApiError, addSnackbar],
    );

    /**
     * レシピを作成する
     * @param data 作成するレシピデータ
     * @param thumbnail サムネイル画像
     * @param steps 手順リスト
     * @returns 成功時はレシピ ID / 失敗時は null
     */
    const storeRecipe = React.useCallback(
        async (
            data: IPostPutRecipeRequest,
            thumbnail: File | null,
            steps: RecipeStepEditFormData[],
        ): Promise<string | null> => {
            // 重複リクエスト防止
            if (isStoreRequestRef.current) {
                return null;
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
                    addSnackbar(
                        'success',
                        responseData.message ||
                        'リクエストが正常に完了しました',
                    );
                    return responseData.data.id ?? null;
                }
                addSnackbar(
                    'error',
                    responseData.message || 'レシピの保存に失敗しました',
                );
                return null;
            } catch (error) {
                handleApiError(error);
                return null;
            } finally {
                isStoreRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [
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
     * @returns 成功時はレシピ ID / 失敗時は null
     */
    const updateRecipe = React.useCallback(
        async (
            data: IPostPutRecipeRequest,
            thumbnail: File | null,
            steps: RecipeStepEditFormData[],
        ): Promise<string | null> => {
            // 重複リクエスト防止
            if (isUpdateRequestRef.current) {
                return null;
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
                const { data: responseData } = await axios.put<IPutRecipeResponse>(
                    `/recipes/${data.id}`,
                    sendData,
                    {
                        timeout: TIMEOUT_MS,
                    },
                );
                if (responseData.success) {
                    addSnackbar(
                        'success',
                        responseData.message ||
                        'リクエストが正常に完了しました',
                    );
                    return data.id ?? null;
                }
                addSnackbar(
                    'error',
                    responseData.message || 'レシピの更新に失敗しました',
                );
                return null;
            } catch (error) {
                handleApiError(error);
                return null;
            } finally {
                isUpdateRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [incrementLoadingCount, decrementLoadingCount, bulkUploadImage, uploadStepImages, addSnackbar, handleApiError],
    );

    /**
     * レシピを削除する
     * @param id 削除するレシピのID
     * @returns 成功時は true / 失敗時は false
     */
    const deleteRecipe = React.useCallback(async (id: string): Promise<boolean> => {
        // 重複リクエスト防止
        if (isDeleteRequestRef.current) {
            return false;
        }

        try {
            isDeleteRequestRef.current = true;
            incrementLoadingCount();
            const { data: responseData } = await axios.delete<IDeleteRecipeResponse>(
                `/recipes/${id}`,
                {
                    timeout: TIMEOUT_MS,
                },
            );
            if (responseData.success) {
                addSnackbar('success', responseData.message || 'リクエストが正常に完了しました');
                return true;
            }
            addSnackbar(
                'error',
                responseData.message || 'レシピの削除に失敗しました',
            );
            return false;
        } catch (error) {
            handleApiError(error);
            return false;
        } finally {
            isDeleteRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [incrementLoadingCount, decrementLoadingCount, addSnackbar, handleApiError]);

    return {
        fetchRecipes,
        storeRecipe,
        updateRecipe,
        deleteRecipe,
    };
};
