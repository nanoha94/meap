import { useSnackbars } from '@/contexts';
import { useShoppingStore } from '../hooks/stores';
import React from 'react';
import { IPostShoppingCategory, IShoppingCategory } from '@/types/api';
import axios from '@/lib/axios';
import { TMP_ID_PREFIX } from '@/constants/ids';
import useSWR from 'swr';

const fetchShoppingCategories = (
    path: string,
): Promise<{ categories: IShoppingCategory[]; total: number }> =>
    axios
        .get(path, {
            timeout: 5000, // 5秒タイムアウト
        })
        .then(res => res.data);

export const useShoppingCategories = () => {
    const { addSnackbar } = useSnackbars();
    const { categories: storeCategories, setCategories } = useShoppingStore();
    const [isLoading, setIsLoading] = React.useState(false);
    const { data, error, isValidating, mutate } = useSWR(
        '/shopping/categories',
        fetchShoppingCategories,
    );

    /**
     * ローディング中かどうかを管理する
     */
    React.useEffect(() => {
        // isValidatingがtrueで、かつデータがない場合のみローディング状態にする
        if (isValidating) {
            setIsLoading(true);
        } else {
            setIsLoading(false);
        }
    }, [isValidating]);

    // フェッチ後にストアにセット
    React.useEffect(() => {
        if (data?.categories) {
            setCategories(data.categories);
        }
    }, [data, setCategories]);

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
                    await axios.delete(`/shopping/categories/bulk`, {
                        data: { ids: deleteCategoryIds },
                        timeout: 5000, // 5秒タイムアウト
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
                            `/shopping/categories`,
                            categories[i] as IPostShoppingCategory,
                            {
                                timeout: 5000, // 5秒タイムアウト
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
                    const res = await axios.put(`/shopping/categories/bulk`, {
                        data: updateCategories,
                        timeout: 5000, // 5秒タイムアウト
                    });
                    if (res.status === 200) {
                        await mutate();
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
            }

            setIsLoading(false);
        },
        [storeCategories, isLoading],
    );

    /**
     * エラーが発生した場合に、エラーメッセージを表示する
     */
    React.useEffect(() => {
        if (error) {
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error?.response?.data?.message);
                addSnackbar('error', error?.response?.data?.message);
            }
        }
    }, [error]);

    return {
        isLoading,
        storeData: { categories: storeCategories },
        bulkUpdateShoppingCategories,
    };
};
