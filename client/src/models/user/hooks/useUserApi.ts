"use client";

import { TIMEOUT_MS } from "@/constants";
import { useApiErrorHandler, useSnackbars } from "@/hooks";
import axios from "@/lib/axios";
import { useGlobalStore } from "@/stores";
import React from "react";
import { IPutUserRequest } from "@/types";
import { useRouter } from "next/navigation";
import { useImageApi } from "@/models/image";
import { useUserStore } from "@/models/user";

export const useUserApi = () => {
    const router = useRouter();
    const { bulkUploadImage } = useImageApi();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);
    const loginUser = useUserStore(state => state.loginUser);

    // 重複リクエスト防止用のフラグ
    const isUpdateRequestRef = React.useRef(false);


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
            const { data: responseData } = await axios.put('/user', data, {
                timeout: TIMEOUT_MS,
            });
            if (responseData.success) {
                router.refresh();
                addSnackbar('success', responseData.message ?? 'リクエストが正常に完了しました');
            }
        }
        catch (error) {
            handleApiError(error);
        } finally {
            isUpdateRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [incrementLoadingCount, decrementLoadingCount, handleApiError, bulkUploadImage, loginUser.id, router, addSnackbar]);

    return {
        updateUser
    };
};