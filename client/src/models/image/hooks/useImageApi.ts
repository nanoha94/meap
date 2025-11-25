import { TIMEOUT_MS } from '@/constants';
import { useSnackbars } from '@/contexts';
import axios from '@/lib/axios';
import { IUploadRecipeResponse } from '@/types/api/image';
import React from 'react';

export const useImageApi = () => {
    const { addSnackbar } = useSnackbars();
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
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
            throw error;
        }
    }, []);

    return {
        bulkUploadImage,
    };
};
