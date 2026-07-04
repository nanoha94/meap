'use client';

import React from 'react';

import { TIMEOUT_MS } from '@/constants';
import axios from '@/lib/axios';
import { useAiUsageStore, useGlobalStore } from '@/stores';
import { IAiUsageStatusResponse } from '@/types';

import { useApiErrorHandler } from './useApiErrorHandler';

/**
 * AI 利用状況 API
 */
export const useAiUsageApi = () => {
    const setAiUsageStatus = useAiUsageStore(state => state.setAiUsageStatus);
    const incrementLoadingCount = useGlobalStore(
        state => state.incrementLoadingCount,
    );
    const decrementLoadingCount = useGlobalStore(
        state => state.decrementLoadingCount,
    );
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isFetchAiUsageStatusRef = React.useRef(false);

    /**
     * AI 利用状況を API から再取得し、ストアに反映する
     */
    const fetchAiUsageStatus = React.useCallback(async () => {
        if (isFetchAiUsageStatusRef.current) {
            return;
        }

        try {
            isFetchAiUsageStatusRef.current = true;
            incrementLoadingCount();

            const { data: responseData } =
                await axios.get<IAiUsageStatusResponse>('/ai/usage', {
                    timeout: TIMEOUT_MS,
                });

            if (responseData.success && responseData.data) {
                setAiUsageStatus(responseData.data);
            }
        } catch (error) {
            handleApiError(error);
        } finally {
            isFetchAiUsageStatusRef.current = false;
            decrementLoadingCount();
        }
    }, [
        incrementLoadingCount,
        decrementLoadingCount,
        setAiUsageStatus,
        handleApiError,
    ]);

    return {
        fetchAiUsageStatus,
    };
};
