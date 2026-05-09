"use client";
import React from 'react';

import { TIMEOUT_MS } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { IUploadImageResponse } from '@/types';

export const useImageApi = () => {
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const bulkUploadImage = React.useCallback(async (files: File[], uploadPath?: string) => {
        try {
            // FormDataを作成してファイルを追加
            const formData = new FormData();

            // ファイル配列を追加（nullやundefinedを除外）
            files.forEach(file => {
                if (file) {
                    formData.append('images[]', file);
                }
            });

            // upload_path が指定されていれば FormData に追加
            if (uploadPath) {
                formData.append('upload_path', uploadPath);
            }

            const res = await axios.post<IUploadImageResponse>(
                `/images/upload-bulk`,
                formData,
                {
                    timeout: TIMEOUT_MS,
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                },
            );
            const responseData: IUploadImageResponse = res.data;
            if (responseData.success) {
                addSnackbar(
                    'success',
                    responseData.message || 'リクエストが正常に完了しました',
                );
            } else {
                addSnackbar(
                    'error',
                    responseData.message || '画像のアップロードに失敗しました',
                );
            }
            return responseData;
        } catch (error) {
            handleApiError(error);
            throw error;
        }
    }, [addSnackbar, handleApiError]);

    return {
        bulkUploadImage,
    };
};
