import {
    IIngredientCategory,
    IPostIngredientCategoryRequestData,
    IPutIngredientCategoryRequestData,
} from '@/types/api';
import React from 'react';
import { useIngredientStore } from './ingredientStores';
import { useSnackbars } from '@/hooks/useSnackbars';
import { useApiErrorHandler } from '@/hooks/api/useApiErrorHandler';
import { LOADING_STATE_KEYS } from '../constants';
import axios from '@/lib/axios';
import { TIMEOUT_MS, TMP_ID_PREFIX } from '@/constants';
import { useRouter } from 'next/navigation';

export const useIngredientCatgoryApi = () => {
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const { categories: storeCategories, setIsLoadings } = useIngredientStore();

    /**
     * 食材カテゴリーを一括削除用リクエストを生成
     * @param categories 更新する食材カテゴリー
     * @returns 削除用リクエスト
     */
    const generateDeleteRequest = React.useCallback(
        (categories: IIngredientCategory[]) => {
            // 削除するカテゴリーIDを取得
            const deleteCategoryIds = storeCategories
                .filter(v => !categories.some(c => c.id === v.id))
                .map(v => v.id);

            // 削除リクエスト
            if (deleteCategoryIds.length > 0) {
                return axios.delete(`/ingredient-categories/bulk`, {
                    data: { ids: deleteCategoryIds },
                    timeout: TIMEOUT_MS,
                });
            }
            return null;
        },
        [storeCategories],
    );

    /**
     * 食材カテゴリーを一括作成・更新用リクエストを生成
     * @param categories 更新する食材カテゴリー
     * @returns 作成・更新用リクエスト
     */
    const generateCreateUpdateRequest = React.useCallback(
        (categories: IIngredientCategory[]) => {
            // 更新するものを配列にセット
            const updateCategories: IPutIngredientCategoryRequestData[] = [];
            // 作成するものを配列にセット
            const createCategories: IPostIngredientCategoryRequestData[] = [];

            for (let i = 0; i < categories.length; i++) {
                // 既存のカテゴリーかどうかを判断
                const isStored = !categories[i].id?.startsWith(
                    TMP_ID_PREFIX.INGREDIENT_CATEGORY,
                );

                // 既存カテゴリ―の場合、更新用配列にセット
                if (isStored) {
                    if (
                        JSON.stringify(categories[i]) !==
                        JSON.stringify(
                            storeCategories.find(
                                v => v.id === categories[i].id,
                            ),
                        )
                    ) {
                        updateCategories.push(
                            categories[i] as IPutIngredientCategoryRequestData,
                        );
                    }
                }
                // まだDBにレコードがない場合は、作成リクエスト
                else {
                    if (!categories[i]) {
                        continue;
                    }
                    createCategories.push(
                        categories[i] as IPostIngredientCategoryRequestData,
                    );
                }
            }

            let updateRequest: Promise<unknown> | null = null;
            let createRequest: Promise<unknown> | null = null;

            // 更新リクエスト
            if (updateCategories.length > 0) {
                updateRequest = axios.put(`/ingredient-categories/bulk`, {
                    data: updateCategories,
                    timeout: TIMEOUT_MS,
                });
            }
            // 作成リクエスト
            if (createCategories.length > 0) {
                createRequest = axios.post(`/ingredient-categories/bulk`, {
                    data: createCategories,
                    timeout: TIMEOUT_MS,
                });
            }
            return { updateRequest, createRequest };
        },
        [storeCategories],
    );

    const bulkUpdateIngredientCategories = React.useCallback(
        async (categories: IIngredientCategory[]) => {
            // 更新データがない場合は処理を終了
            if (
                JSON.stringify(categories) === JSON.stringify(storeCategories)
            ) {
                return;
            }

            // ローディング状態をセット
            setIsLoadings(LOADING_STATE_KEYS.INGREDIENT_CATEGORY, true);

            // 並列実行するリクエストを準備
            const requests: Promise<unknown>[] = [];

            // 削除リクエスト
            const deleteRequest = generateDeleteRequest(categories);
            if (deleteRequest) {
                requests.push(deleteRequest);
            }

            // 更新・作成リクエスト
            const { updateRequest, createRequest } =
                generateCreateUpdateRequest(categories);
            if (updateRequest) {
                requests.push(updateRequest);
            }
            if (createRequest) {
                requests.push(createRequest);
            }

            // すべてのリクエストを並列実行
            try {
                const results = await Promise.allSettled(requests);

                // エラーが発生したリクエストをチェック
                const errors = results.filter(
                    result => result.status === 'rejected',
                ) as PromiseRejectedResult[];

                if (errors.length > 0) {
                    // エラーを処理
                    errors.forEach(error => {
                        handleApiError(error.reason);
                    });
                    return;
                }

                // すべての処理がエラーなく完了した場合
                router.refresh();
                addSnackbar('success', '食材カテゴリーを更新しました');
            } catch (error) {
                handleApiError(error);
            } finally {
                setIsLoadings(LOADING_STATE_KEYS.INGREDIENT_CATEGORY, false);
            }
        },
        [storeCategories],
    );

    return {
        storeData: { categories: storeCategories },
        bulkUpdateIngredientCategories,
    };
};
