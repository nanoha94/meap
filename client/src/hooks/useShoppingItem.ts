import axios from '@/lib/axios';
import { IGetShoppingItem, IPostShoppingItem } from '@/types/api';
import React from 'react';
import useSWR from 'swr';

const fetchShoppingItems = (path: string): Promise<IGetShoppingItem[]> =>
    axios.get(path).then(res => res.data);

export const useShoppingItem = () => {
    const [isLoading, setIsLoading] = React.useState(false);
    const {
        data: shoppingItems,
        error,
        mutate,
        isValidating,
    } = useSWR('/api/group/shopping/items', fetchShoppingItems);

    React.useEffect(() => {
        if (isValidating) {
            setIsLoading(true);
        } else {
            setIsLoading(false);
        }
    }, [isValidating]);

    const updateShoppingItems = async (items: IPostShoppingItem[]) => {
        if (
            isLoading ||
            items.length === 0 ||
            JSON.stringify(items) === JSON.stringify(shoppingItems)
        ) {
            return;
        }

        try {
            setIsLoading(true);
            const res = await axios.post(`/api/group/shopping/items`, {
                items,
            });
            if (res.data) {
                // TODO: スナックバーで表示
                console.log(res.data.message);
                await mutate();
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        } finally {
            setIsLoading(false);
        }
    };

    const deleteShoppingItem = async (id: string) => {
        if (isLoading) return;

        try {
            setIsLoading(true);
            const res = await axios.delete(`/api/group/shopping/items`, {
                data: { id },
            });
            if (res.data) {
                // TODO: スナックバーで表示
                console.log(res.data.message);
                await mutate();
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        } finally {
            setIsLoading(false);
        }
    };

    const deleteAllShoppingItems = async () => {
        if (isLoading) return;

        try {
            setIsLoading(true);
            const res = await axios.delete(`/api/group/shopping/items/all`);

            if (res.data) {
                // TODO: スナックバーで表示
                console.log(res.data.message);
                await mutate();
            }
        } catch (error) {
            // TODO: スナックバーでエラー表示
            console.error(error.response?.data.message);
        } finally {
            setIsLoading(false);
        }
    };

    React.useEffect(() => {
        if (error) {
            // TODO: スナックバーでエラー表示
            console.error(error?.response?.data?.message);
        }
    }, [error]);

    return {
        isLoading,
        shoppingItems,
        updateShoppingItems,
        deleteShoppingItem,
        deleteAllShoppingItems,
    };
};
