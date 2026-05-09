"use client";
import React from 'react';

import { TIMEOUT_MS } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import {
    IBaseApiResponse,
    IPostShoppingItemRequest,
    IPostShoppingItemResponse,
    IPutShoppingItemRequest,
} from '@/types';
import { useShoppingStore } from '../hooks';

/** 削除後に続く一括更新が来ない場合のフラグ残留を防ぐ（ms） */
const SKIP_NEXT_BULK_SNACKBAR_CLEAR_MS = 15000;

export const useShoppingItemApi = () => {
    // store
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);
    const storeItems = useShoppingStore(state => state.items);
    const setStoreItems = useShoppingStore(state => state.setItems);
    const serverItems = useShoppingStore(state => state.serverItems);
    const setServerItems = useShoppingStore(state => state.setServerItems);
    const setIsSkipNextBulkSnackbar = useShoppingStore(
        state => state.setIsSkipNextBulkSnackbar,
    );

    //hook
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isFetchRequestRef = React.useRef(false);
    const isStoreRequestRef = React.useRef(false);
    const isUpdateBulkRequestRef = React.useRef(false);
    const isDeleteBulkRequestRef = React.useRef(false);
    const skipNextBulkSnackbarTimeoutRef = React.useRef<
        ReturnType<typeof setTimeout> | undefined
    >(undefined);

    const clearIsSkipNextBulkSnackbar = React.useCallback(() => {
        setIsSkipNextBulkSnackbar(false);
        if (skipNextBulkSnackbarTimeoutRef.current !== undefined) {
            clearTimeout(skipNextBulkSnackbarTimeoutRef.current);
            skipNextBulkSnackbarTimeoutRef.current = undefined;
        }
    }, [setIsSkipNextBulkSnackbar]);

    /**
     * 取得処理（更新処理の後に呼び出す）
     */
    const fetchShoppingItems = React.useCallback(
        async (silent = false) => {
            // 重複リクエスト防止
            if (isFetchRequestRef.current) {
                return;
            }

            try {
                isFetchRequestRef.current = true;
                if (!silent) incrementLoadingCount();

                const res = await axios.get('/shopping-items', {
                    timeout: TIMEOUT_MS,
                });
                if (res.data) {
                    setServerItems(res.data.data);
                    setStoreItems(res.data.data);
                }
            } catch (error) {
                handleApiError(error);
            } finally {
                isFetchRequestRef.current = false;
                if (!silent) decrementLoadingCount();
            }
        },
        [
            setServerItems,
            setStoreItems,
            handleApiError,
            incrementLoadingCount,
            decrementLoadingCount,
        ],
    );

    /**
     * 作成処理（一括API）
     * @param items 作成するアイテム
     */
    const storeShoppingItems = React.useCallback(
        async (items: IPostShoppingItemRequest[]) => {
            if (isStoreRequestRef.current || items.length <= 0) {
                return;
            }

            try {
                isStoreRequestRef.current = true;
                incrementLoadingCount();

                const { data: responseData } = await axios.post<IPostShoppingItemResponse>(
                    `/shopping-items/bulk`,
                    {
                        data: items,
                        timeout: TIMEOUT_MS,
                    },
                );
                if (responseData.success) {
                    await fetchShoppingItems();
                    addSnackbar(
                        'success',
                        responseData.message || 'リクエストが正常に完了しました',
                    );
                } else {
                    addSnackbar(
                        'error',
                        responseData.message || '買い物アイテムの登録に失敗しました',
                    );
                }
            } catch (error) {
                handleApiError(error);
            } finally {
                isStoreRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [
            incrementLoadingCount,
            decrementLoadingCount,
            fetchShoppingItems,
            addSnackbar,
            handleApiError,
        ],
    );

    /**
     * 更新処理
     * @param items 更新するアイテム
     * @param silent true のときローディング表示を出さず、後続の fetch も同様に silent にする
     * @returns 更新結果
     */
    const updateShoppingItems = React.useCallback(
        async (items: IPutShoppingItemRequest[], silent = false) => {
            if (
                isUpdateBulkRequestRef.current ||
                items.length <= 0 ||
                JSON.stringify(items) === JSON.stringify(serverItems)
            ) {
                return;
            }

            try {
                isUpdateBulkRequestRef.current = true;
                if (!silent) incrementLoadingCount();

                const { data: responseData } = await axios.put<IBaseApiResponse>(
                    `/shopping-items/bulk`,
                    {
                        data: items.filter(v => v.name && v.name.length > 0),
                        timeout: TIMEOUT_MS,
                    },
                );
                if (responseData.success) {
                    await fetchShoppingItems(silent);
                    // await 後はクロージャより getState() で最新のフラグを参照する
                    const { isSkipNextBulkSnackbar } = useShoppingStore.getState();
                    if (isSkipNextBulkSnackbar) {
                        clearIsSkipNextBulkSnackbar();
                    } else if (!silent) {
                        addSnackbar(
                            'success',
                            responseData.message || 'リクエストが正常に完了しました',
                        );
                    }
                } else if (!silent) {
                    addSnackbar(
                        'error',
                        responseData.message || '買い物アイテムの更新に失敗しました',
                    );
                }
            } catch (error) {
                handleApiError(error);
                clearIsSkipNextBulkSnackbar();
                // エラーが発生した場合は再取得して状態を復元
                await fetchShoppingItems(silent);
            } finally {
                isUpdateBulkRequestRef.current = false;
                if (!silent) decrementLoadingCount();
            }
        },
        [
            serverItems,
            incrementLoadingCount,
            decrementLoadingCount,
            fetchShoppingItems,
            addSnackbar,
            handleApiError,
            clearIsSkipNextBulkSnackbar,
        ],
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

            const { data: responseData } = await axios.delete<IBaseApiResponse>(
                '/shopping-items/bulk',
                {
                    data: { ids },
                    timeout: TIMEOUT_MS,
                },
            );
            if (responseData.success) {
                setIsSkipNextBulkSnackbar(true);
                if (skipNextBulkSnackbarTimeoutRef.current !== undefined) {
                    clearTimeout(skipNextBulkSnackbarTimeoutRef.current);
                }
                skipNextBulkSnackbarTimeoutRef.current = setTimeout(() => {
                    setIsSkipNextBulkSnackbar(false);
                    skipNextBulkSnackbarTimeoutRef.current = undefined;
                }, SKIP_NEXT_BULK_SNACKBAR_CLEAR_MS);

                await fetchShoppingItems();
                addSnackbar(
                    'success',
                    responseData.message || 'リクエストが正常に完了しました',
                );
            } else {
                addSnackbar(
                    'error',
                    responseData.message || '買い物アイテムの削除に失敗しました',
                );
            }
        } catch (error) {
            handleApiError(error);
            // エラーが発生した場合は再取得して状態を復元
            await fetchShoppingItems();
        } finally {
            isDeleteBulkRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [
        incrementLoadingCount,
        decrementLoadingCount,
        fetchShoppingItems,
        addSnackbar,
        handleApiError,
        setIsSkipNextBulkSnackbar,
    ]);

    return {
        storeData: { items: storeItems },
        storeShoppingItems,
        updateShoppingItems,
        deleteShoppingItems,
    };
};
