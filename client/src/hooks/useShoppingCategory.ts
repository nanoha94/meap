import { useSnackbars } from '@/contexts';
import axios from '@/lib/axios';
import { IGetShoppingCategory, IPostShoppingCategory } from '@/types/api';
import React from 'react';
import useSWR from 'swr';

const fetchShoppingCategories = (
    path: string,
): Promise<{ categories: IGetShoppingCategory[]; total: number }> =>
    axios.get(path).then(res => res.data);

export const useShoppingCategory = () => {
    const { addSnackbar } = useSnackbars();
    const { data, error } = useSWR(
        '/shopping/categories',
        fetchShoppingCategories,
    );
    const shoppingCategories = data?.categories;

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
                const res = await axios.post(`/shopping/categories`, {
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
        updateShoppingCategories,
        deleteShoppingCategory,
    };
};
