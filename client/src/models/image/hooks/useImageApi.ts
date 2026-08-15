'use client';

import React from 'react';

import { UPLOAD_TIMEOUT_MS } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { IUploadImageResponse, IUploadImageSingleResponse } from '@/types';
import {
    compressImage,
    ImageCompressionError,
} from '@/utils/imageCompression';

export const useImageApi = () => {
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    /**
     * グループ画像一括アップロード
     * @param files 画像ファイル
     * @returns アップロード結果
     * @throws エラー
     */
    const bulkUploadImage = React.useCallback(async (files: File[]) => {
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

            // Content-Type は付けない（FormData 時にブラウザが boundary 付きで付与する）
            const res = await axios.post<IUploadImageResponse>(
                `/images/groups/upload-bulk`,
                formData,
                {
                    timeout: UPLOAD_TIMEOUT_MS,
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

    /**
     * ユーザー画像アップロード
     * @param file 画像ファイル
     * @returns アップロード結果
     * @throws エラー
     */
    const uploadUserImage = React.useCallback(async (file: File): Promise<IUploadImageSingleResponse> => {
        try {
            let compressedFile: File;
            try {
                compressedFile = await compressImage(file);
            } catch (error) {
                const message =
                    error instanceof ImageCompressionError
                        ? error.message
                        : '画像の圧縮に失敗しました';
                addSnackbar('error', message);
                return {
                    success: false,
                    message,
                    data: { src: '', width: 0, height: 0 },
                };
            }

            // FormDataを作成してファイルを追加
            const formData = new FormData();
            formData.append('image', compressedFile);

            // Content-Type は付けない（FormData 時にブラウザが boundary 付きで付与する）
            const res = await axios.post<IUploadImageSingleResponse>(
                `/images/users/upload`,
                formData,
                {
                    timeout: UPLOAD_TIMEOUT_MS,
                },
            );
            const responseData = res.data;
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
        uploadUserImage,
    };
};
