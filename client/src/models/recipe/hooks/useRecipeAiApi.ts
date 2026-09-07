'use client';

import React from 'react';

import { AI_TIMEOUT_MS } from '@/constants';
import { useAiUsageApi, useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import { IAiRecipeParseResponse, IParsedRecipe, IPostAiRecipeParseUrlRequest } from '@/types';

/**
 * @name useRecipeAiApi
 * @returns useRecipeAiApi
 * @description 画像・URL からレシピ情報を AI 解析する
 */
export const useRecipeAiApi = () => {
    const incrementLoadingCount = useGlobalStore(
        state => state.incrementLoadingCount,
    );
    const decrementLoadingCount = useGlobalStore(
        state => state.decrementLoadingCount,
    );
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();
    const { fetchAiUsageStatus } = useAiUsageApi();

    // 重複リクエスト防止用のフラグ
    const isParseRequestRef = React.useRef(false);

    /**
     * 画像からレシピ情報を AI 解析する
     */
    const parseRecipeFromImage = React.useCallback(
        async (file: File): Promise<IParsedRecipe | null> => {
            if (isParseRequestRef.current) {
                return null;
            }

            const formData = new FormData();
            formData.append('image', file);

            try {
                isParseRequestRef.current = true;
                incrementLoadingCount();

                const { data: responseData } =
                    await axios.post<IAiRecipeParseResponse>(
                        '/ai/recipes/parse-img',
                        formData,
                        {
                            timeout: AI_TIMEOUT_MS,
                        },
                    );

                if (responseData.success) {
                    addSnackbar(
                        'success',
                        responseData.message ||
                        '画像からレシピ情報を読み取りました。',
                    );
                    await fetchAiUsageStatus();
                    return responseData.data;
                }

                addSnackbar(
                    'error',
                    responseData.message ||
                    '画像からのレシピ読み込みに失敗しました',
                );
                return null;
            } catch (error) {
                handleApiError(error);
                return null;
            } finally {
                isParseRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [
            incrementLoadingCount,
            decrementLoadingCount,
            addSnackbar,
            handleApiError,
            fetchAiUsageStatus,
        ],
    );

    /**
     * URL からレシピ情報を AI 解析する
     */
    const parseRecipeFromUrl = React.useCallback(
        async (url: string): Promise<IParsedRecipe | null> => {
            if (isParseRequestRef.current) {
                return null;
            }

            try {
                isParseRequestRef.current = true;
                incrementLoadingCount();

                const body: IPostAiRecipeParseUrlRequest = { url };

                const { data: responseData } =
                    await axios.post<IAiRecipeParseResponse>(
                        '/ai/recipes/parse-url',
                        body,
                        {
                            timeout: AI_TIMEOUT_MS,
                        },
                    );

                if (responseData.success) {
                    addSnackbar(
                        'success',
                        responseData.message ||
                            'URLからレシピ情報を読み取りました。',
                    );
                    await fetchAiUsageStatus();
                    return responseData.data;
                }

                addSnackbar(
                    'error',
                    responseData.message ||
                        'URLからのレシピ読み込みに失敗しました',
                );
                return null;
            } catch (error) {
                handleApiError(error);
                return null;
            } finally {
                isParseRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [
            incrementLoadingCount,
            decrementLoadingCount,
            addSnackbar,
            handleApiError,
            fetchAiUsageStatus,
        ],
    );

    return {
        parseRecipeFromImage,
        parseRecipeFromUrl,
    };
};
