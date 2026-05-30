"use client";
import React from 'react';

import { TIMEOUT_MS } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { IUploadImageResponse } from '@/types';
import {
    compressImage,
    ImageCompressionError,
} from '@/utils/imageCompression';

export const useImageApi = () => {
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    const bulkUploadImage = React.useCallback(async (files: File[], uploadPath?: string) => {
        try {
            const validFiles = files.filter((file): file is File => Boolean(file));

            let compressedFiles: File[];
            try {
                compressedFiles = await Promise.all(
                    validFiles.map(file => compressImage(file)),
                );
            } catch (error) {
                const message =
                    error instanceof ImageCompressionError
                        ? error.message
                        : '画像の圧縮に失敗しました';
                addSnackbar('error', message);
                return {
                    success: false,
                    message,
                    data: [],
                    total: 0,
                };
            }

            // FormDataを作成してファイルを追加
            const formData = new FormData();

            compressedFiles.forEach(file => {
                formData.append('images[]', file);
            });

            // upload_path が指定されていれば FormData に追加
            if (uploadPath) {
                formData.append('upload_path', uploadPath);
            }

            // Content-Type は付けない（FormData 時にブラウザが boundary 付きで付与する）
            const res = await axios.post<IUploadImageResponse>(
                `/images/upload-bulk`,
                formData,
                {
                    timeout: TIMEOUT_MS,
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
