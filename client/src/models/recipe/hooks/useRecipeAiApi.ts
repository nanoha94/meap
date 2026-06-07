'use client';

import React from 'react';

import { TIMEOUT_MS } from '@/constants';
import { useAiUsageApi, useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import { IAiRecipeParseResponse, IParsedRecipe } from '@/types';

/**
 * @name useRecipeAiApi
 * @returns useRecipeAiApi
 * @description 画像からレシピ情報を AI 解析する
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
    const {
        aiUsageStatus,
        isAiLimitReached,
        incrementUsageCount,
    } = useAiUsageApi();

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
                        '/ai/recipes/parse',
                        formData,
                        {
                            timeout: TIMEOUT_MS,
                        },
                    );

                if (responseData.success) {
                    addSnackbar(
                        'success',
                        responseData.message ||
                        '画像からレシピ情報を読み取りました。',
                    );
                    incrementUsageCount();
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
            incrementUsageCount,
        ],
    );

    return {
        parseRecipeFromImage,
        isAiLimitReached,
        aiUsageStatus,
    };
};
