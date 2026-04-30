"use client";
import React from 'react';
import { useRouter } from 'next/navigation';

import { TIMEOUT_MS, TMP_ID_PREFIX } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import { getApiErrorMessageFromSettledResult } from '@/lib/apiResponse';
import axios from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import { IPostRecipeCategoryRequest, IPutRecipeCategoryRequest, IRecipeCategory } from '@/types';
import { useRecipeStore } from '../hooks';

export const useRecipeCategoryApi = () => {
    // store
    const storeCategories = useRecipeStore(state => state.categories);
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);

    // hook
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isBulkUpdateRequestRef = React.useRef(false);

    const generateDeleteRequest = React.useCallback(
        (categories: IRecipeCategory[]) => {
            // 削除するカテゴリーIDを取得
            const deleteCategoryIds = storeCategories
                .filter(v => !categories.some(c => c.id === v.id))
                .map(v => v.id);

            // 削除リクエスト
            if (deleteCategoryIds.length > 0) {
                return axios.delete(`/recipe-categories/bulk`, {
                    data: { ids: deleteCategoryIds },
                    timeout: TIMEOUT_MS,
                });
            }
            return null;
        },
        [storeCategories],
    );

    const generateCreateUpdateRequest = React.useCallback(
        (categories: IRecipeCategory[]) => {
            // 作成するカテゴリ―
            const createCategories: IPostRecipeCategoryRequest[] = [];
            // 更新するカテゴリー
            const updateCategories: IPutRecipeCategoryRequest[] = [];

            for (let i = 0; i < categories.length; i++) {
                // 既存のカテゴリーかどうかを判断
                const isStored = !categories[i].id?.startsWith(
                    TMP_ID_PREFIX.RECIPE_CATEGORY,
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
                        updateCategories.push(categories[i] as IPutRecipeCategoryRequest);
                    }
                }
                // まだDBにレコードがない場合は、作成リクエスト
                else {
                    if (
                        categories[i] &&
                        (categories[i].name ?? '').length > 0
                    ) {
                        createCategories.push({
                            name: categories[i].name!,
                            order: categories[i].order,
                        });
                    }
                }
            }

            let createRequest: Promise<unknown> | null = null;
            let updateRequest: Promise<unknown> | null = null;

            // 新規作成リクエスト（一括）
            if (createCategories.length > 0) {
                createRequest = axios.post(
                    `/recipe-categories/bulk`,
                    { data: createCategories },
                    { timeout: TIMEOUT_MS },
                );

            }

            // 更新リクエスト
            if (updateCategories.length > 0) {
                updateRequest = axios.put(`/recipe-categories/bulk`, {
                    data: updateCategories,
                    timeout: TIMEOUT_MS,
                });
            }

            return { createRequest, updateRequest };

        },
        [storeCategories],
    );

    const bulkUpdateRecipeCategories = React.useCallback(
        async (categories: IRecipeCategory[]) => {
            // 重複リクエスト防止
            // 更新データがない場合は処理を終了
            if (isBulkUpdateRequestRef.current ||
                JSON.stringify(categories) === JSON.stringify(storeCategories)) {
                return;
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

            // 作成・更新リクエスト
            const { createRequest, updateRequest } = generateCreateUpdateRequest(categories);
            if (createRequest) {
                requests.push(createRequest);
            }
            if (updateRequest) {
                requests.push(updateRequest);
            }

            // すべてのリクエストを並列実行
            try {
                const results = await Promise.allSettled(requests);

                const rejected = results.filter(
                    (r): r is PromiseRejectedResult => r.status === 'rejected',
                );
                if (rejected.length > 0) {
                    rejected.forEach(r => handleApiError(r.reason));
                    return;
                }

                const businessErrorMessages = results
                    .map(r =>
                        getApiErrorMessageFromSettledResult(
                            r,
                            'レシピカテゴリーの更新に失敗しました',
                        ),
                    )
                    .filter((m): m is string => m != null);
                if (businessErrorMessages.length > 0) {
                    Array.from(new Set(businessErrorMessages)).forEach(msg =>
                        addSnackbar('error', msg),
                    );
                    return;
                }

                router.refresh();
                addSnackbar('success', 'レシピカテゴリーを更新しました');
            } catch (error) {
                handleApiError(error);
            } finally {
                isBulkUpdateRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [
            storeCategories,
            incrementLoadingCount,
            decrementLoadingCount,
            addSnackbar,
            handleApiError,
            router,
        ],
    );

    return {
        storeData: { categories: storeCategories },
        bulkUpdateRecipeCategories,
    };
};
