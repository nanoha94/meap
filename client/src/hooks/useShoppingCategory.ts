import { TMP_ID_PREFIX } from '@/constants/ids';
import { useSnackbars } from '@/contexts';
import axios from '@/lib/axios';
import {
    IGetShoppingCategory,
    IGetShoppingItemsResponse,
    IPostShoppingCategory,
    IPutShoppingCategory,
} from '@/types/api';
import { waitForLoading } from '@/utils/waitForLoading';
import React from 'react';
import useSWR from 'swr';

const fetchShoppingCategories = (
    path: string,
): Promise<{ categories: IGetShoppingCategory[]; total: number }> =>
    axios.get(path).then(res => res.data);

export const useShoppingCategory = () => {
    const { addSnackbar } = useSnackbars();
    const [isLoading, setIsLoading] = React.useState(false);
    const { data, error, isValidating, mutate } = useSWR(
        '/shopping/categories',
        fetchShoppingCategories,
    );
    const [shoppingCategories, setShoppingCategories] = React.useState<
        IGetShoppingCategory[]
    >([]);

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

    React.useEffect(() => {
        setShoppingCategories(data?.categories);
    }, [data]);

    const bulkUpdateShoppingCategories = async (
        categories: IPutShoppingCategory[],
    ) => {
        // 更新データがない場合は処理を終了
        if (JSON.stringify(categories) === JSON.stringify(shoppingCategories)) {
            return;
        }

        // ローディング中の場合は、ローディングが終わるまで待つ（タイムアウト＝5秒）
        if (isLoading) {
            try {
                await waitForLoading({ isLoading });
            } catch (error) {
                if (error instanceof Error && error.message === 'Timeout') {
                    addSnackbar(
                        'error',
                        '処理がタイムアウトしました。もう一度お試しください。',
                    );
                } else {
                    console.error(error);
                    addSnackbar('error', '予期しないエラーが発生しました。');
                }
            }
        }

        let hasError = false;
        setIsLoading(true);

        // 削除するカテゴリーを取得
        const deleteCategoryIds = shoppingCategories
            .filter(v => !categories.some(c => c.id === v.id))
            .map(v => v.id);

        // 削除リクエスト
        if (deleteCategoryIds.length > 0) {
            try {
                await axios.delete(`/shopping/categories/bulk`, {
                    data: { ids: deleteCategoryIds },
                });
            } catch (error) {
                hasError = true;
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
        }

        const updateCategories: IPutShoppingCategory[] = [];

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
                        shoppingCategories.find(v => v.id === categories[i].id),
                    )
                ) {
                    updateCategories.push(
                        categories[i] as IPutShoppingCategory,
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
                    );
                } catch (error) {
                    hasError = true;
                    console.error(error.response?.data.message);
                    addSnackbar('error', error.response?.data.message);
                }
            }
        }

        // 更新リクエスト
        if (updateCategories.length > 0) {
            try {
                const res = await axios.put(`/shopping/categories/bulk`, {
                    data: updateCategories,
                });
                if (res.status === 200) {
                    await mutate();
                }
            } catch (error) {
                hasError = true;
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
        }

        // すべての処理がエラーなく完了した場合
        if (!hasError) {
            addSnackbar('success', '買い物カテゴリーを更新しました');
        }

        setIsLoading(false);
    };

    /**
     * エラーが発生した場合に、エラーメッセージを表示する
     */
    React.useEffect(() => {
        if (error) {
            console.error(error?.response?.data?.message);
            addSnackbar('error', error?.response?.data?.message);
        }
    }, [error]);

    /**
     * カテゴリーが変更された場合の処理
     * @param shoppingItems 買い物アイテムの配列
     * @param itemMutate アイテムのmutate関数
     * @param onMutate カテゴリー変更時のコールバック
     */
    const handleCategoryChange = React.useCallback(
        (
            shoppingItems: IGetShoppingItemsResponse['data'],
            itemMutate: () => void,
            onMutate?: () => void,
        ) => {
            if (
                JSON.stringify(shoppingCategories) !==
                JSON.stringify(shoppingItems.map(v => v.category))
            ) {
                itemMutate(); // アイテムのmutate
                onMutate?.(); // 追加のコールバック
            }
        },
        [shoppingCategories],
    );

    return {
        shoppingCategories: shoppingCategories,
        bulkUpdateShoppingCategories,
        isLoading,
        handleCategoryChange,
    };
};
