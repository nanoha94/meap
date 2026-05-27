'use client';

import React from 'react';

import { TIMEOUT_MS, TMP_ID_PREFIX } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import { getApiErrorMessageFromSettledResult } from '@/lib/apiResponse';
import axios from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import {
    IGetShoppingCategoryIndexResponse,
    IPostShoppingCategoryRequest,
    IPutShoppingCategoryRequest,
    IShoppingCategory,
} from '@/types';
import { useShoppingStore } from '../hooks';

export const useShoppingCategoryApi = () => {
    // store
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);
    const storeCategories = useShoppingStore(state => state.categories);
    const setCategories = useShoppingStore(state => state.setCategories);

    // hook
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isBulkUpdateRequestRef = React.useRef(false);

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
            // 作成するものを配列にセット
            const createCategories: IPostShoppingCategoryRequest[] = [];
            // 更新するものを配列にセット
            const updateCategories: IPutShoppingCategoryRequest[] = [];

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
                            categories[i] as IPutShoppingCategoryRequest,
                        );
                    }
                }
                // まだDBにレコードがない場合は、作成リクエスト
                else {
                    if (!categories[i]) {
                        continue;
                    }
                    createCategories.push(
                        categories[i] as IPostShoppingCategoryRequest,
                    );
                }
            }

            let createRequest: Promise<unknown> | null = null;
            let updateRequest: Promise<unknown> | null = null;

            // 作成リクエスト
            if (createCategories.length > 0) {
                createRequest = axios.post(`/shopping-categories/bulk`, {
                    data: createCategories,
                    timeout: TIMEOUT_MS,
                });
            }

            // 更新リクエスト
            if (updateCategories.length > 0) {
                updateRequest = axios.put(`/shopping-categories/bulk`, {
                    data: updateCategories,
                    timeout: TIMEOUT_MS,
                });
            }

            return { createRequest, updateRequest };
        },
        [storeCategories],
    );

    /**
     * 買い物カテゴリー一覧を取得してストアに反映する
     * @returns ストア反映に成功したとき true
     */
    const fetchShoppingCategories = React.useCallback(async (): Promise<boolean> => {
        try {
            const { data } = await axios.get<IGetShoppingCategoryIndexResponse>(
                '/shopping-categories',
                { timeout: TIMEOUT_MS },
            );
            if (data.success && data.data) {
                setCategories(data.data);
                return true;
            }
            addSnackbar(
                'error',
                '買い物カテゴリーの再取得に失敗しました。表示が古い場合はページを再読み込みしてください。',
            );
            return false;
        } catch {
            addSnackbar(
                'error',
                '買い物カテゴリーの再取得に失敗しました。表示が古い場合はページを再読み込みしてください。',
            );
            return false;
        }
    }, [addSnackbar, setCategories]);

    /**
     * 買い物カテゴリーを一括更新
     * @param categories 更新する買い物カテゴリー
     * @returns 成功時 true、それ以外は false
     */
    const bulkUpdateShoppingCategories = React.useCallback(
        async (categories: IShoppingCategory[]) => {
            // 重複リクエスト防止
            // 更新データがない場合は処理を終了
            if (isBulkUpdateRequestRef.current ||
                JSON.stringify(categories) === JSON.stringify(storeCategories)) {
                return false;
            }

            // 重複リクエスト防止用のフラグをセット
            isBulkUpdateRequestRef.current = true;
            // ローディング状態をセット
            incrementLoadingCount();

            // 並列実行するリクエストを準備
            const requests: Promise<unknown>[] = [];

            // 削除リクエスト
            const deleteRequest = generateDeleteRequest(categories);
            if (deleteRequest) {
                requests.push(deleteRequest);
            }

            // 更新・作成リクエスト
            const { createRequest, updateRequest } =
                generateCreateUpdateRequest(categories);
            if (createRequest) {
                requests.push(createRequest);
            }
            if (updateRequest) {
                requests.push(updateRequest);
            }

            if (requests.length === 0) {
                isBulkUpdateRequestRef.current = false;
                decrementLoadingCount();
                return false;
            }

            // すべてのリクエストを並列実行
            try {
                const results = await Promise.allSettled(requests);

                const rejected = results.filter(
                    (r): r is PromiseRejectedResult => r.status === 'rejected',
                );
                if (rejected.length > 0) {
                    rejected.forEach(r => handleApiError(r.reason));
                    return false;
                }

                const businessErrorMessages = results
                    .map(r =>
                        getApiErrorMessageFromSettledResult(
                            r,
                            '買い物カテゴリーの更新に失敗しました',
                        ),
                    )
                    .filter((m): m is string => m != null);
                if (businessErrorMessages.length > 0) {
                    Array.from(new Set(businessErrorMessages)).forEach(msg =>
                        addSnackbar('error', msg),
                    );
                    return false;
                }

                addSnackbar('success', '買い物カテゴリーを更新しました');
                await fetchShoppingCategories();
                return true;
            } catch (error) {
                handleApiError(error);
                return false;
            } finally {
                isBulkUpdateRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [
            storeCategories,
            incrementLoadingCount,
            decrementLoadingCount,
            fetchShoppingCategories,
            generateDeleteRequest,
            generateCreateUpdateRequest,
            handleApiError,
            addSnackbar,
        ],
    );

    return {
        fetchShoppingCategories,
        bulkUpdateShoppingCategories,
    };
};
