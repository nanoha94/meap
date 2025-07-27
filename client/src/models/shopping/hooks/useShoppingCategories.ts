import { useSnackbars } from '@/contexts';
import { useShoppingStore } from './shoppingStores';
import React from 'react';
import { IPostShoppingCategoryRequest, IShoppingCategory } from '@/types/api';
import axios from '@/lib/axios';
import { TIMEOUT_MS } from '@/constants';
import { useRouter } from 'next/navigation';
import { TMP_ID_PREFIX } from '../constants';

export const useShoppingCategories = () => {
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const {
        categories: storeCategories,
        isLoadingCategories: isLoading,
        setIsLoadingCategories: setIsLoading,
    } = useShoppingStore();

    const bulkUpdateShoppingCategories = React.useCallback(
        async (categories: IShoppingCategory[]) => {
            // 更新データがない場合は処理を終了
            if (
                JSON.stringify(categories) === JSON.stringify(storeCategories)
            ) {
                return;
            }

            let hasError = false;
            setIsLoading(true);

            // 削除するカテゴリーを取得
            const deleteCategoryIds = storeCategories
                .filter(v => !categories.some(c => c.id === v.id))
                .map(v => v.id);

            // 削除リクエスト
            if (deleteCategoryIds.length > 0) {
                try {
                    await axios.delete(`/shopping-categories/bulk`, {
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

            const updateCategories: IShoppingCategory[] = [];

            // 更新用配列を生成
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
                            categories[i] as IShoppingCategory,
                        );
                    }
                }
                // まだDBにレコードがない場合は、作成リクエスト
                else {
                    if (!categories[i]) {
                        continue;
                    }
                    try {
                        await axios.post(
                            `/shopping-categories`,
                            categories[i] as IPostShoppingCategoryRequest,
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
                    const res = await axios.put(`/shopping-categories/bulk`, {
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

            setIsLoading(false);
        },
        [storeCategories, isLoading],
    );

    return {
        storeData: { isLoading, categories: storeCategories },
        bulkUpdateShoppingCategories,
    };
};
