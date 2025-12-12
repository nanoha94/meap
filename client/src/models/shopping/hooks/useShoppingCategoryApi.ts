import React from 'react';
import { useSnackbars } from '@/contexts';
import { useShoppingStore } from './shoppingStores';
import {
    IPostShoppingCategoryRequestData,
    IPutShoppingCategoryRequestData,
    IShoppingCategory,
} from '@/types/api';
import axios from '@/lib/axios';
import { TIMEOUT_MS, TMP_ID_PREFIX } from '@/constants';
import { useRouter } from 'next/navigation';
import { useApiErrorHandler } from '@/hooks/api/useApiErrorHandler';

export const useShoppingCategoryApi = () => {
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const {
        categories: storeCategories,
        isLoadingCategories: isLoading,
        setIsLoadingCategories: setIsLoading,
    } = useShoppingStore();

    /**
     * 買い物カテゴリーを一括削除用リクエストを生成
     * @param categories 更新する買い物カテゴリー
     * @returns 削除用リクエスト
     */
    const generateDeleteRequest = React.useCallback(
        (categories: IShoppingCategory[]) => {
            // 削除するカテゴリーIDを取得
            const deleteCategoryIds = storeCategories
                .filter(v => !categories.some(c => c.id === v.id))
                .map(v => v.id);

            // 削除リクエスト
            if (deleteCategoryIds.length > 0) {
                return axios.delete(`/shopping-categories/bulk`, {
                    data: { ids: deleteCategoryIds },
                    timeout: TIMEOUT_MS,
                });
            }
            return null;
        },
        [storeCategories],
    );

    /**
     * 買い物カテゴリーを一括作成・更新用リクエストを生成
     * @param categories 更新する買い物カテゴリー
     * @returns 作成・更新用リクエスト
     */
    const generateCreateUpdateRequest = React.useCallback(
        (categories: IShoppingCategory[]) => {
            // 更新するものを配列にセット
            const updateCategories: IPutShoppingCategoryRequestData[] = [];
            // 作成するものを配列にセット
            const createCategories: IPostShoppingCategoryRequestData[] = [];

            for (let i = 0; i < categories.length; i++) {
                // 既存のカテゴリーかどうかを判断
                const isStored = !categories[i].id?.startsWith(
                    TMP_ID_PREFIX.SHOPPING_CATEGORY,
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
                            categories[i] as IPutShoppingCategoryRequestData,
                        );
                    }
                }
                // まだDBにレコードがない場合は、作成リクエスト
                else {
                    if (!categories[i]) {
                        continue;
                    }
                    createCategories.push(
                        categories[i] as IPostShoppingCategoryRequestData,
                    );
                }
            }

            let updateRequest: Promise<unknown> | null = null;
            let createRequest: Promise<unknown> | null = null;

            // 更新リクエスト
            if (updateCategories.length > 0) {
                updateRequest = axios.put(`/shopping-categories/bulk`, {
                    data: updateCategories,
                    timeout: TIMEOUT_MS,
                });
            }
            // 作成リクエスト
            if (createCategories.length > 0) {
                createRequest = axios.post(`/shopping-categories/bulk`, {
                    data: createCategories,
                    timeout: TIMEOUT_MS,
                });
            }
            return { updateRequest, createRequest };
        },
        [storeCategories],
    );

    /**
     * 買い物カテゴリーを一括更新
     * @param categories 更新する買い物カテゴリー
     * @returns 更新結果
     */
    const bulkUpdateShoppingCategories = React.useCallback(
        async (categories: IShoppingCategory[]) => {
            // 更新データがない場合は処理を終了
            if (
                JSON.stringify(categories) === JSON.stringify(storeCategories)
            ) {
                return;
            }

            // ローディング状態をセット
            setIsLoading(true);

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
                addSnackbar('success', '買い物カテゴリーを更新しました');
            } catch (error) {
                handleApiError(error);
            } finally {
                setIsLoading(false);
            }
        },
        [storeCategories],
    );

    return {
        storeData: { isLoading, categories: storeCategories },
        bulkUpdateShoppingCategories,
    };
};
