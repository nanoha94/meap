import axios from '@/lib/axios';
import {
    IGetShoppingItemsResponse,
    IPostShoppingItem,
    IShoppingItem,
} from '@/types/api';
import React from 'react';
import useSWR from 'swr';
import { useSnackbars } from '@/contexts';

const fetchShoppingItems = (path: string): Promise<IGetShoppingItemsResponse> =>
    axios.get(path).then(res => res.data);

export const useShoppingItem = () => {
    const { addSnackbar } = useSnackbars();
    const [isLoading, setIsLoading] = React.useState(false);
    const { data, error, mutate, isValidating } = useSWR(
        '/shopping/items',
        fetchShoppingItems,
        { revalidateOnMount: false },
    );
    const shoppingItems: IGetShoppingItemsResponse['data'] = data?.data;

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

    const createShoppingItem = async (item: IPostShoppingItem) => {
        if (isLoading) {
            return;
        }

        try {
            setIsLoading(true);
            const res = await axios.post(`/shopping/items`, {
                ...item,
            });
            if (res.data) {
                addSnackbar(
                    'success',
                    `買い物リストに${item.name}を追加しました`,
                );
                await mutate();
            }
        } catch (error) {
            console.error(error.response?.data.message);
            addSnackbar('error', error.response?.data.message);
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * 更新処理
     * @param items 更新するアイテム
     * @returns 更新結果
     */
    const updateShoppingItems = async (items: IShoppingItem[]) => {
        if (
            isLoading ||
            items.length === 0 ||
            JSON.stringify(items) ===
                JSON.stringify(
                    shoppingItems?.flatMap(category => category.items) || [],
                )
        ) {
            return;
        }

        try {
            setIsLoading(true);
            const res = await axios.put(`/shopping/items/bulk`, {
                data: items.filter(v => v.name && v.name.length > 0),
            });
            if (res.data) {
                addSnackbar('success', '買い物リストを更新しました');
                await mutate();
            }
        } catch (error) {
            console.error(error.response?.data.message);
            addSnackbar('error', error.response?.data.message);
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * 削除処理
     * @param id 削除するアイテムのID
     * @returns 削除結果
     */
    const deleteShoppingItem = async (id: string) => {
        console.log('deleteShoppingItem', id);
        if (isLoading) {
            return;
        }

        try {
            setIsLoading(true);
            // const res = await axios.delete(`/shopping/items`, {
            //     data: { id },
            // });
            // if (res.data) {
            //     addSnackbar('success', res.data.message);
            //     await mutate();
            // }
        } catch (error) {
            console.error(error.response?.data.message);
            addSnackbar('error', error.response?.data.message);
        } finally {
            setIsLoading(false);
        }
    };

    const deleteAllShoppingItems = async () => {
        console.log('deleteAllShoppingItems');
        if (isLoading) return;
        // TODO: API仕様に合わせる
        try {
            setIsLoading(true);
            // const res = await axios.delete(`/shopping/items/bulk`);

            // if (res.data) {
            //     addSnackbar('success', res.data.message);
            //     await mutate();
            // }
        } catch (error) {
            console.error(error.response?.data.message);
            addSnackbar('error', error.response?.data.message);
        } finally {
            setIsLoading(false);
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
        isLoading,
        shoppingItems,
        createShoppingItem,
        updateShoppingItems,
        deleteShoppingItem,
        deleteAllShoppingItems,
        mutate,
    };
};
