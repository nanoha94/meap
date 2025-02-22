import axios from '@/lib/axios';
import { IGetShoppingItem, IPostShoppingItem } from '@/types/api';
import React from 'react';
import useSWR from 'swr';
// import { useDebounce } from './useDebounce';

const fetchShoppingItems = (path: string): Promise<IGetShoppingItem[]> =>
    axios.get(path).then(res => res.data);

export const useShoppingItem = () => {
    const {
        data: shoppingItems,
        error,
        mutate,
    } = useSWR('/api/group/shopping/items', fetchShoppingItems);

    const updateShoppingItems = async (items: IPostShoppingItem[]) => {
        if (
            items.length > 0 &&
            JSON.stringify(items) !== JSON.stringify(shoppingItems)
        ) {
            try {
                const res = await axios.post(`/api/group/shopping/items`, {
                    items,
                });
                if (res.data) {
                    // TODO: スナックバーで表示
                    console.log(res.data.message);
                    // 更新後にSWRのキャッシュを更新
                    await mutate();
                }
            } catch (error) {
                // TODO: スナックバーでエラー表示
                console.error(error.response?.data.message);
            }
        }
    };

    const deleteShoppingItem = async (id: string) => {
        try {
            const res = await axios.delete(`/api/group/shopping/items`, {
                data: { id },
            });
            if (res.data) {
                await mutate();
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        }
    };

    React.useEffect(() => {
        if (error) {
            // TODO: スナックバーでエラー表示
            console.error(error?.response?.data?.message);
        }
    }, [error]);

    return {
        shoppingItems,
        updateShoppingItems,
        deleteShoppingItem,
    };
};
