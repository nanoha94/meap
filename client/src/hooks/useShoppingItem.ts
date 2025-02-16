import axios from '@/lib/axios';
import { IGetShoppingItem, IPostShoppingItem } from '@/types/api';
import React from 'react';
import useSWR from 'swr';

const fetchShoppingItems = (path: string): Promise<IGetShoppingItem[]> =>
    axios.get(path).then(res => res.data);

export const useShoppingItem = () => {
    const { data: shoppingItems, error } = useSWR(
        '/api/group/shopping/items',
        fetchShoppingItems,
    );

    const updateShoppingItems = async (items: IPostShoppingItem[]) => {
        try {
            const res = await axios.post(`/api/group/shopping/items`, {
                items,
            });
            if (res.data) {
                // TODO: スナックバーで表示
                console.log(res.data.message);
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        }
    };

    const deleteShoppingItem = async (id: string) => {
        try {
            const res = await axios.delete(`/api/group/shopping/items`, {
                data: { id },
            });
            if (res.data) {
                // TODO: スナックバーで表示
                console.log(res.data.message);
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        }
    };

    // const updateShoppingItems = async (items: IPostShoppingItem[]) => {
    //     try {
    //         await Promise.all(items.map(item => updateShoppingItem(item)));
    //     } catch (error) {
    //         console.error('Failed to update shopping items:', error);
    //     }
    // };

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
