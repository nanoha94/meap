import axios from '@/lib/axios';
import { IGetShoppingItem, IPostShoppingItem } from '@/types/api';
import React from 'react';
import useSWR from 'swr';
import { useDebounce } from './useDebounce';

const fetchShoppingItems = (path: string): Promise<IGetShoppingItem[]> =>
    axios.get(path).then(res => res.data);

export const useShoppingItem = () => {
    const { data: shoppingItems, error } = useSWR(
        '/api/group/shopping/items',
        fetchShoppingItems,
    );

    const [localItems, setLocalItems] = React.useState<IPostShoppingItem[]>([]);
    const debouncedItems = useDebounce(localItems, 5000);

    // コンポーネントのアンマウント時に強制的に保存
    React.useEffect(() => {
        return () => {
            if (localItems.length > 0) {
                updateShoppingItems(localItems);
            }
        };
    }, [localItems]);

    // ローカルの状態が変更されたら5秒後に自動で更新
    React.useEffect(() => {
        if (
            debouncedItems.length > 0 &&
            JSON.stringify(debouncedItems) !== JSON.stringify(shoppingItems)
        ) {
            updateShoppingItems(debouncedItems);
        }
    }, [debouncedItems, shoppingItems]);

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

    React.useEffect(() => {
        if (error) {
            // TODO: スナックバーでエラー表示
            console.error(error?.response?.data?.message);
        }
    }, [error]);

    return {
        shoppingItems,
        updateShoppingItems: items => setLocalItems(items),
        deleteShoppingItem,
    };
};
