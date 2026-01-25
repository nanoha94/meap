import { useSnackbars } from '@/hooks/useSnackbars';
import { useRouter } from 'next/navigation';
import { useRecipeStore } from './recipeStores';
import { IPostRecipeCategoryRequest, IRecipeCategory } from '@/types/api';
import axios from '@/lib/axios';
import { API_STATUS_CODE, TIMEOUT_MS, TMP_ID_PREFIX } from '@/constants';
import React from 'react';
import { useApiErrorHandler } from '@/hooks/api';
import { useGlobalStore } from '@/stores';

export const useRecipeCategoryApi = () => {
    const { categories: storeCategories } = useRecipeStore();
    const { incrementLoadingCount, decrementLoadingCount } = useGlobalStore();
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isBulkUpdateRequestRef = React.useRef(false);

    const bulkUpdateRecipeCategories = React.useCallback(
        async (categories: IRecipeCategory[]) => {
            // 重複リクエスト防止
            if (isBulkUpdateRequestRef.current) {
                return;
            }

            // 更新データがない場合は処理を終了
            if (
                JSON.stringify(categories) === JSON.stringify(storeCategories)
            ) {
                return;
            }

            let hasError = false;
            isBulkUpdateRequestRef.current = true;
            incrementLoadingCount();

            // 削除するカテゴリーを取得
            const deleteCategoryIds = storeCategories
                .filter(v => {
                    const found = categories.find(c => c.id === v.id);
                    return !found || !found.name;
                })
                .map(v => v.id);

            // 削除リクエスト
            if (deleteCategoryIds.length > 0) {
                try {
                    await axios.delete(`/recipe-categories/bulk`, {
                        data: { ids: deleteCategoryIds },
                        timeout: TIMEOUT_MS,
                    });
                } catch (error) {
                    hasError = true;
                    handleApiError(error);
                }
            }

            const updateCategories: IRecipeCategory[] = [];

            // 更新用配列を生成
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
                        updateCategories.push(categories[i] as IRecipeCategory);
                    }
                }
                // まだDBにレコードがない場合は、作成リクエスト
                else {
                    if (
                        !categories[i] ||
                        (categories[i].name ?? '').length <= 0
                    ) {
                        continue;
                    }
                    try {
                        await axios.post(
                            `/recipe-categories`,
                            categories[i] as IPostRecipeCategoryRequest,
                            {
                                timeout: TIMEOUT_MS,
                            },
                        );
                    } catch (error) {
                        hasError = true;
                        handleApiError(error);
                    }
                }
            }

            // 更新リクエスト
            if (updateCategories.length > 0) {
                try {
                    const res = await axios.put(`/recipe-categories/bulk`, {
                        data: updateCategories,
                        timeout: TIMEOUT_MS,
                    });
                    if (res.status === API_STATUS_CODE.OK) {
                        router.refresh();
                    }
                } catch (error) {
                    hasError = true;
                    handleApiError(error);
                }
            }

            // すべての処理がエラーなく完了した場合
            if (!hasError) {
                addSnackbar('success', 'レシピカテゴリーを更新しました');
                router.refresh();
            }

            isBulkUpdateRequestRef.current = false;
            decrementLoadingCount();
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
