import React from 'react';
import { useShoppingStore } from './shoppingStores';
import { useSnackbars } from '@/contexts';
import axios from '@/lib/axios';
import { IPostShoppingItemRequest, IPutShoppingItemRequest } from '@/types/api';
import { TIMEOUT_MS } from '@/constants';

export const useShoppingItems = () => {
    const { addSnackbar } = useSnackbars();
    const {
        items: storeItems,
        serverItems,
        setServerItems,
        setItems: setStoreItems,
        isLoadingItems: isLoading,
        setIsLoadingItems: setIsLoading,
    } = useShoppingStore();

    /**
     * 取得処理（更新処理の後に呼び出す）
     */
    const fetchShoppingItems = React.useCallback(
        async (serverOnly = false) => {
            try {
                setIsLoading(true);
                const res = await axios.get('/shopping-items', {
                    timeout: TIMEOUT_MS,
                });
                if (res.data) {
                    setServerItems(res.data.data);
                    // serverOnlyがfalseの場合のみローカル状態も更新
                    if (!serverOnly) {
                        setStoreItems(res.data.data);
                    }
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
        [isLoading, addSnackbar],
    );

    /**
     * 作成処理
     * @param item 作成するアイテム
     * @returns 作成結果
     */
    const storeShoppingItem = async (item: IPostShoppingItemRequest) => {
        if (isLoading) {
            return;
        }

        try {
            setIsLoading(true);
            const res = await axios.post(`/shopping-items`, {
                ...item,
                timeout: TIMEOUT_MS,
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
        async (items: IPutShoppingItemRequest['data']) => {
            const serverItemsFlat =
                serverItems?.flatMap(category => category.items) || [];

            if (
                isLoading ||
                items.length === 0 ||
                JSON.stringify(items) === JSON.stringify(serverItemsFlat)
            ) {
                return;
            }

            try {
                setIsLoading(true);
                const res = await axios.put(`/shopping-items/bulk`, {
                    data: items.filter(v => v.name && v.name.length > 0),
                    timeout: TIMEOUT_MS,
                });
                if (res.data) {
                    await fetchShoppingItems(true); // サーバーデータのみ更新
                    addSnackbar('success', '買い物リストを更新しました');
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
        [serverItems],
    );

    /**
     * 削除処理
     * @param id 削除するアイテムのID
     * @returns 削除結果
     */
    const deleteShoppingItems = async (ids: string[]) => {
        if (isLoading) {
            return;
        }

        try {
            setIsLoading(true);
            const res = await axios.delete('/shopping-items/bulk', {
                data: { ids },
                timeout: TIMEOUT_MS,
            });
            if (res.data) {
                await fetchShoppingItems();
                addSnackbar('success', '買い物アイテムを削除しました');
            }
        } catch (error) {
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
            // エラーが発生した場合は再取得して状態を復元
            await fetchShoppingItems();
        } finally {
            setIsLoading(false);
        }
    };

    return {
        storeData: { isLoading, items: storeItems },
        fetchShoppingItems,
        storeShoppingItem,
        updateShoppingItems,
        deleteShoppingItems,
    };
};
