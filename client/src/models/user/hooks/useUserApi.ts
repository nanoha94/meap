'use client';

import React from 'react';

import { LINK_TO, TIMEOUT_MS } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { useImageApi } from '@/models/image';
import { useGlobalStore } from '@/stores';
import { IBaseApiResponse, IPutUserRequest } from '@/types';

export const useUserApi = () => {
    // store
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);

    // hook
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const { uploadUserImage } = useImageApi();

    // 重複リクエスト防止用のフラグ
    const isUpdateRequestRef = React.useRef(false);
    const isDeleteRequestRef = React.useRef(false);

    /**
     * ユーザー情報を更新する
     * @param data 更新するユーザー情報
     * @returns 成功時 true、失敗時 false
     */
    const updateUser = React.useCallback(async (data: IPutUserRequest, avatarImage: File | null): Promise<boolean> => {
        // 重複リクエスト防止
        if (isUpdateRequestRef.current) {
            return false;
        }

        try {
            isUpdateRequestRef.current = true;
            incrementLoadingCount();

            // アバター画像のアップロード
            if (avatarImage) {
                const response = await uploadUserImage(avatarImage);
                if (response.success) {
                    data.avatar_image_id = response.data?.id;
                }
            }

            // APIリクエスト
            const { data: responseData } = await axios.put<IBaseApiResponse>('/user', data, {
                timeout: TIMEOUT_MS,
            });
            if (responseData.success) {
                addSnackbar(
                    'success',
                    responseData.message || 'リクエストが正常に完了しました',
                );
                return true;
            }
            addSnackbar(
                'error',
                responseData.message || 'ユーザー情報の更新に失敗しました',
            );
            return false;
        }
        catch (error) {
            handleApiError(error);
            return false;
        } finally {
            isUpdateRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [incrementLoadingCount, decrementLoadingCount, handleApiError, addSnackbar, uploadUserImage]);

    /**
     * ユーザーを削除する
     * @returns void
     */
    const deleteUser = React.useCallback(async () => {
        // 重複リクエスト防止
        if (isDeleteRequestRef.current) {
            return;
        }

        try {
            isDeleteRequestRef.current = true;
            incrementLoadingCount();
            const { data: responseData } = await axios.delete<IBaseApiResponse>('/user', {
                timeout: TIMEOUT_MS,
            });
            if (responseData.success) {
                addSnackbar(
                    'success',
                    responseData.message || 'リクエストが正常に完了しました',
                );

                if (typeof window !== 'undefined') {
                    sessionStorage.clear();
                }

                window.location.href = LINK_TO.LOGIN;
            } else {
                addSnackbar(
                    'error',
                    responseData.message || 'アカウントの削除に失敗しました',
                );
            }
        }
        catch (error) {
            handleApiError(error);
        } finally {
            isDeleteRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [incrementLoadingCount, decrementLoadingCount, handleApiError, addSnackbar]);

    return {
        updateUser, deleteUser
    };
};
