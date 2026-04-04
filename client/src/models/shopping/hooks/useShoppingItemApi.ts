"use client";
import React from 'react';

import { TIMEOUT_MS } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import {
    IPostShoppingItemRequest,
    IPostShoppingItemResponse,
    IPutShoppingItemRequest,
} from '@/types';
import { useShoppingStore } from '../hooks';

export const useShoppingItemApi = () => {
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);
    const {
        items: storeItems,
        serverItems,
        setServerItems,
        setItems: setStoreItems,
    } = useShoppingStore();

    // 重複リクエスト防止用のフラグ
    const isFetchRequestRef = React.useRef(false);
    const isStoreRequestRef = React.useRef(false);
    const isBulkStoreRequestRef = React.useRef(false);
    const isUpdateBulkRequestRef = React.useRef(false);
    const isDeleteBulkRequestRef = React.useRef(false);

    /**
     * 取得処理（更新処理の後に呼び出す）
     */
    const fetchShoppingItems = React.useCallback(
        async (serverOnly = false) => {
            // 重複リクエスト防止
            if (isFetchRequestRef.current) {
                return;
            }

            try {
                // ローディング状態をセット
                isFetchRequestRef.current = true;
                incrementLoadingCount();

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
                handleApiError(error);
            } finally {
                isFetchRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [setServerItems, setStoreItems, handleApiError],
    );

    /**
     * 作成処理
     * @param item 作成するアイテム
     * @returns 作成結果
     */
    const storeShoppingItem = async (item: IPostShoppingItemRequest) => {
        // 重複リクエスト防止
        if (isStoreRequestRef.current) {
            return;
        }

        try {
            isStoreRequestRef.current = true;
            incrementLoadingCount();

            const { data: responseData } = await axios.post<IPostShoppingItemResponse>(`/shopping-items`, {
                ...item,
                timeout: TIMEOUT_MS,
            });
            if (responseData.success) {
                addSnackbar(
                    'success',
                    responseData.message ?? 'リクエストが正常に完了しました',
                );
                await fetchShoppingItems();
            }
        } catch (error) {
            handleApiError(error);
        } finally {
            isStoreRequestRef.current = false;
            decrementLoadingCount();
        }
    };

    /**
     * 一括作成処理
     * @param items 作成するアイテム
     * @returns 作成結果
     */
    const bulkStoreShoppingItems = React.useCallback(
        async (items: IPostShoppingItemRequest[]) => {
            if (
                isBulkStoreRequestRef.current ||
                items.length <= 0
            ) {
                return;
            }

            try {
                isBulkStoreRequestRef.current = true;
                incrementLoadingCount();

                const { data: responseData } = await axios.post(`/shopping-items/bulk`, {
                    data: items,
                    timeout: TIMEOUT_MS,
                });
                if (responseData.success) {
                    await fetchShoppingItems();
                    addSnackbar('success', responseData.message ?? 'リクエストが正常に完了しました');
                }
            } catch (error) {
                handleApiError(error);
            } finally {
                isBulkStoreRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [incrementLoadingCount, decrementLoadingCount, fetchShoppingItems, addSnackbar, handleApiError],
    );

    /**
     * 更新処理
     * @param items 更新するアイテム
     * @returns 更新結果
     */
    const updateShoppingItems = React.useCallback(
        async (items: IPutShoppingItemRequest[]) => {
            if (
                isUpdateBulkRequestRef.current ||
                items.length <= 0 ||
                JSON.stringify(items) === JSON.stringify(serverItems)
            ) {
                return;
            }

            try {
                isUpdateBulkRequestRef.current = true;
                incrementLoadingCount();

                const { data: responseData } = await axios.put(`/shopping-items/bulk`, {
                    data: items.filter(v => v.name && v.name.length > 0),
                    timeout: TIMEOUT_MS,
                });
                if (responseData.success) {
                    await fetchShoppingItems();
                    addSnackbar('success', responseData.message ?? 'リクエストが正常に完了しました');
                }
            } catch (error) {
                handleApiError(error);
                // エラーが発生した場合は再取得して状態を復元
                await fetchShoppingItems();
            } finally {
                isUpdateBulkRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [serverItems, incrementLoadingCount, decrementLoadingCount, fetchShoppingItems, addSnackbar, handleApiError],
    );

    /**
     * 削除処理
     * @param id 削除するアイテムのID
     * @returns 削除結果
     */
    const deleteShoppingItems = React.useCallback(async (ids: string[]) => {
        // 重複リクエスト防止
        if (isDeleteBulkRequestRef.current) {
            return;
        }

        try {
            isDeleteBulkRequestRef.current = true;
            incrementLoadingCount();

            const { data: responseData } = await axios.delete('/shopping-items/bulk', {
                data: { ids },
                timeout: TIMEOUT_MS,
            });
            if (responseData.success) {
                await fetchShoppingItems();
                addSnackbar('success', responseData.message ?? 'リクエストが正常に完了しました');
            }
        } catch (error) {
            handleApiError(error);
            // エラーが発生した場合は再取得して状態を復元
            await fetchShoppingItems();
        } finally {
            isDeleteBulkRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [incrementLoadingCount, decrementLoadingCount, fetchShoppingItems, addSnackbar, handleApiError]);

    return {
        storeData: { items: storeItems },
        storeShoppingItem,
        bulkStoreShoppingItems,
        updateShoppingItems,
        deleteShoppingItems,
    };
};
