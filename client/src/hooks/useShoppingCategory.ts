import { useSnackbars } from '@/contexts';
import axios from '@/lib/axios';
import {
    IGetShoppingCategory,
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
    const { data, error, isValidating } = useSWR(
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
        if (isValidating) {
            setIsLoading(true);
        } else {
            setIsLoading(false);
        }
    }, [isValidating]);

    React.useEffect(() => {
        setShoppingCategories(data?.categories);
        console.log(data?.categories);
    }, [data]);

    const createOrUpdateShoppingCategories = async (
        categories: (IPostShoppingCategory | IPutShoppingCategory)[],
    ) => {
        // 更新データがない場合は処理を終了
        if (
            categories.length === 0 ||
            JSON.stringify(categories) === JSON.stringify(shoppingCategories)
        ) {
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

        const updateCategories: IPutShoppingCategory[] = [];

        for (let i = 0; i < categories.length; i++) {
            // 既存のカテゴリーかどうかを判断
            const isStored =
                'id' in categories[i] &&
                (categories[i] as IPutShoppingCategory).id;

            // 既存カテゴリ―の場合、更新用配列にセット
            if (isStored) {
                updateCategories.push(categories[i] as IPutShoppingCategory);
            }
            // まだDBにレコードがない場合は、作成リクエスト
            else {
                try {
                    setIsLoading(true);
                    const res: IGetShoppingCategory = await axios.post(
                        `/shopping/categories`,
                        categories[i],
                    );
                    updateCategories.push(res);
                } catch (error) {
                    console.error(error.response?.data.message);
                    addSnackbar('error', error.response?.data.message);
                } finally {
                    setIsLoading(false);
                }
            }
        }

        // 更新リクエスト
        try {
            setIsLoading(true);
            const res = await axios.put(`/shopping/categories/bulk`, {
                categories: updateCategories.map((v, idx) => ({
                    ...v,
                    order: idx,
                })),
            });

            if (res.status === 200) {
                setShoppingCategories(res.data);
                addSnackbar('success', '買い物カテゴリ―を更新しました');
            }
        } catch (error) {
            console.error(error.response?.data.message);
            addSnackbar('error', error.response?.data.message);
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * カテゴリーを更新する
     * @param categories 更新するカテゴリーの配列
     */
    const updateShoppingCategories = async (
        categories: IPostShoppingCategory[],
    ) => {
        const filteredCategories = categories?.filter(v => v.name.length > 0);

        for (let idx = 0; idx < filteredCategories.length; idx++) {
            try {
                const res = await axios.post(`/shopping/categories/bulk`, {
                    ...filteredCategories[idx],
                    order: filteredCategories[idx].order ?? idx,
                });
                if (res.data) {
                    addSnackbar('success', res.data.message);
                }
            } catch (error) {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
        }
    };

    /**
     * カテゴリーを削除する
     * @param id 削除するカテゴリーのID
     */
    const deleteShoppingCategory = async (id: string) => {
        try {
            const res = await axios.delete(`/shopping/categories`, {
                data: { id },
            });
            if (res.data) {
                addSnackbar('success', res.data.message);
            }
        } catch (error) {
            console.error(error.response?.data.message);
            addSnackbar('error', error.response?.data.message);
        }
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

    return {
        shoppingCategories: shoppingCategories,
        createOrUpdateShoppingCategories,
        updateShoppingCategories,
        deleteShoppingCategory,
        isLoading,
    };
};
