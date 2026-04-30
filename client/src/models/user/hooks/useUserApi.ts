"use client";

import { TIMEOUT_MS } from "@/constants";
import { useApiErrorHandler, useSnackbars } from "@/hooks";
import axios from "@/lib/axios";
import { useGlobalStore } from "@/stores";
import React from "react";
import { IBaseApiResponse, IPutUserRequest } from "@/types";
import { useRouter } from "next/navigation";
import { useImageApi } from "@/models/image";
import { useUserStore } from "@/models/user";

export const useUserApi = () => {
    // store
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);
    const loginUser = useUserStore(state => state.loginUser);

    // hook
    const router = useRouter();
    const { bulkUploadImage } = useImageApi();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isUpdateRequestRef = React.useRef(false);
    const isDeleteRequestRef = React.useRef(false);

    /**
     * ユーザー情報を更新する
     * @param data 更新するユーザー情報
     * @returns void
     */
    const updateUser = React.useCallback(async (data: IPutUserRequest, avatarImage: File | null): Promise<void> => {
        // 重複リクエスト防止
        if (isUpdateRequestRef.current) {
            return;
        }

        try {
            isUpdateRequestRef.current = true;
            incrementLoadingCount();

            // アバター画像のアップロード
            if (avatarImage) {
                const uploadPath = `users/${loginUser.id}`;
                const images = await bulkUploadImage([avatarImage], uploadPath);
                if (images.success) {
                    data.avatar_image_id = images.data[0]?.id;
                }
            }

            // APIリクエスト
            const { data: responseData } = await axios.put<IBaseApiResponse>('/user', data, {
                timeout: TIMEOUT_MS,
            });
            if (responseData.success) {
                router.refresh();
                addSnackbar(
                    'success',
                    responseData.message || 'リクエストが正常に完了しました',
                );
            } else {
                addSnackbar(
                    'error',
                    responseData.message || 'ユーザー情報の更新に失敗しました',
                );
            }
        }
        catch (error) {
            handleApiError(error);
        } finally {
            isUpdateRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [incrementLoadingCount, decrementLoadingCount, handleApiError, bulkUploadImage, loginUser.id, router, addSnackbar]);

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

                if (typeof window !== "undefined") {
                    sessionStorage.clear();
                }

                window.location.href = "/login";
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