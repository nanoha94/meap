import { TIMEOUT_MS } from '@/constants';
import { useSnackbars } from '@/hooks/useSnackbars';
import { useApiErrorHandler } from '@/hooks/api';
import axios from '@/lib/axios';
import { IUploadRecipeResponse } from '@/types/api';
import React from 'react';

export const useImageApi = () => {
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const bulkUploadImage = React.useCallback(async (files: File[]) => {
        try {
            // FormDataを作成してファイルを追加
            const formData = new FormData();

            // ファイル配列を追加（nullやundefinedを除外）
            files.forEach(file => {
                if (file) {
                    formData.append('images[]', file);
                }
            });

            const res = await axios.post<IUploadRecipeResponse>(
                `/images/upload-bulk`,
                formData,
                {
                    timeout: TIMEOUT_MS,
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                },
            );
            const responseData: IUploadRecipeResponse = res.data;
            if (responseData.success) {
                addSnackbar(
                    'success',
                    responseData.message ?? 'リクエストが正常に完了しました',
                );
            }
            return responseData;
        } catch (error) {
            handleApiError(error);
            throw error;
        }
    }, []);

    return {
        bulkUploadImage,
    };
};
