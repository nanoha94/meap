import React from 'react';
import { useShoppingStore } from '../hooks/stores';
import { useSnackbars } from '@/contexts';
import axios from '@/lib/axios';
import { IPostShoppingItem, IShoppingItem } from '@/types/api';

export const useShoppingItems = () => {
    const { addSnackbar } = useSnackbars();
    const {
        items: storeItems,
        setItems: setStoreItems,
        isLoadingItems: isLoading,
        setIsLoadingItems: setIsLoading,
    } = useShoppingStore();

    /**
     * 取得処理（更新処理の後に呼び出す）
     */
    const fetchShoppingItems = React.useCallback(async () => {
        try {
            setIsLoading(true);
            const res = await axios.get('/shopping/items');
            if (res.data) {
                setStoreItems(res.data.data);
            }
        } catch (error) {
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
        } finally {
            setIsLoading(false);
        }
    }, [isLoading, addSnackbar]);

    /**
     * 作成処理
     * @param item 作成するアイテム
     * @returns 作成結果
     */
    const createShoppingItem = async (item: IPostShoppingItem) => {
        if (isLoading) {
            return;
        }

        try {
            setIsLoading(true);
            const res = await axios.post(`/shopping/items`, {
                ...item,
                timeout: 5000, // 5秒タイムアウト
            });
            if (res.data) {
                addSnackbar(
                    'success',
                    `買い物リストに${item.name}を追加しました`,
                );
                fetchShoppingItems();
            }
        } catch (error) {
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * 更新処理
     * @param items 更新するアイテム
     * @returns 更新結果
     */
    const updateShoppingItems = React.useCallback(
        async (items: IShoppingItem[]) => {
            if (
                isLoading ||
                items.length === 0 ||
                JSON.stringify(items) ===
                    JSON.stringify(
                        storeItems?.flatMap(category => category.items) || [],
                    )
            ) {
                return;
            }

            try {
                setIsLoading(true);
                const res = await axios.put(`/shopping/items/bulk`, {
                    data: items.filter(v => v.name && v.name.length > 0),
                    timeout: 5000, // 5秒タイムアウト
                });
                if (res.data) {
                    addSnackbar('success', '買い物リストを更新しました');
                    fetchShoppingItems();
                }
            } catch (error) {
                if (error.code === 'ECONNABORTED') {
                    addSnackbar('error', 'リクエストがタイムアウトしました');
                } else {
                    console.error(error.response?.data.message);
                    addSnackbar('error', error.response?.data.message);
                }
            } finally {
                setIsLoading(false);
            }
        },
        [storeItems, isLoading],
    );

    /**
     * 削除処理
     * @param id 削除するアイテムのID
     * @returns 削除結果
     */
    const deleteShoppingItem = async (id: string) => {
        if (isLoading) {
            return;
        }

        try {
            setIsLoading(true);
            const res = await axios.delete(`/shopping/items/${id}`, {
                data: { id },
                timeout: 5000, // 5秒タイムアウト
            });
            if (res.data) {
                addSnackbar('success', '買い物アイテムを削除しました');
                await fetchShoppingItems();
            }
        } catch (error) {
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
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
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
        } finally {
            setIsLoading(false);
        }
    };

    return {
        storeData: { isLoading, items: storeItems },
        fetchShoppingItems,
        createShoppingItem,
        updateShoppingItems,
        deleteShoppingItem,
        deleteAllShoppingItems,
    };
};
