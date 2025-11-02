import { useSnackbars } from '@/contexts';
import { useRouter } from 'next/navigation';
import { useRecipeStore } from './recipeStores';
import {
    IPostRecipeCategoryRequest,
    IRecipeCategory,
} from '@/types/api/recipe';
import axios from '@/lib/axios';
import { TIMEOUT_MS } from '@/constants';
import React from 'react';
import { TMP_ID_PREFIX } from '@/constants/tmpIdPrefix';

export const useRecipeCategories = () => {
    const {
        categories: storeCategories,
        isLoadings,
        setIsLoadings,
    } = useRecipeStore();
    const router = useRouter();
    const { addSnackbar } = useSnackbars();

    const bulkUpdateRecipeCategories = React.useCallback(
        async (categories: IRecipeCategory[]) => {
            if (isLoadings.recipeCategory) {
                return;
            }

            // 更新データがない場合は処理を終了
            if (
                JSON.stringify(categories) === JSON.stringify(storeCategories)
            ) {
                return;
            }

            let hasError = false;
            setIsLoadings('recipeCategory', true);

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
                    if (error.code === 'ECONNABORTED') {
                        addSnackbar(
                            'error',
                            'リクエストがタイムアウトしました',
                        );
                    } else {
                        hasError = true;
                        console.error(error.response?.data.message);
                        addSnackbar('error', error.response?.data.message);
                    }
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
                        if (error.code === 'ECONNABORTED') {
                            addSnackbar(
                                'error',
                                'リクエストがタイムアウトしました',
                            );
                        } else {
                            hasError = true;
                            console.error(error.response?.data.message);
                            addSnackbar('error', error.response?.data.message);
                        }
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
                    if (res.status === 200) {
                        router.refresh();
                    }
                } catch (error) {
                    if (error.code === 'ECONNABORTED') {
                        addSnackbar(
                            'error',
                            'リクエストがタイムアウトしました',
                        );
                    } else {
                        hasError = true;
                        console.error(error.response?.data.message);
                        addSnackbar('error', error.response?.data.message);
                    }
                }
            }

            // すべての処理がエラーなく完了した場合
            if (!hasError) {
                addSnackbar('success', '買い物カテゴリーを更新しました');
                router.refresh();
            }

            setIsLoadings('recipeCategory', false);
        },
        [storeCategories, isLoadings.recipeCategory],
    );

    return {
        storeData: { categories: storeCategories },
        bulkUpdateRecipeCategories,
    };
};
