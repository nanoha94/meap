'use client';

import React from 'react';

import { TIMEOUT_MS } from '@/constants';
import axios from '@/lib/axios';
import { IAiUsageStatus, IAiUsageStatusResponse } from '@/types';

import { useApiErrorHandler } from './useApiErrorHandler';
import { useGlobalStore } from '@/stores';

/**
 * AI 利用状況 API
 */
export const useAiUsageApi = () => {
    const incrementLoadingCount = useGlobalStore(
        state => state.incrementLoadingCount,
    );
    const decrementLoadingCount = useGlobalStore(
        state => state.decrementLoadingCount,
    );
    const { handleApiError } = useApiErrorHandler();
    const [aiUsageStatus, setAiUsageStatus] =
        React.useState<IAiUsageStatus | null>(null);

    // 重複リクエスト防止用のフラグ
    const isFetchAiUsageStatusRef = React.useRef(false);

    /**
 * AI 利用回数が制限に達しているかどうかを判定する
 */
    const isAiLimitReached = React.useMemo(() => {
        if (!aiUsageStatus) {
            return false;
        }

        return aiUsageStatus.usageCount >= aiUsageStatus.usageLimit;
    }, [aiUsageStatus]);

    /**
     * 利用回数をローカルで 1 増やす（再フェッチなし）
     */
    const incrementUsageCount = React.useCallback(() => {
        setAiUsageStatus(prev =>
            prev
                ? {
                    ...prev,
                    usageCount: prev.usageCount + 1,
                }
                : prev,
        );
    }, []);

    /**
     * AI 利用状況を取得する
     */
    const fetchAiUsageStatus = React.useCallback(async (): Promise<
        IAiUsageStatus | null
    > => {
        // 重複リクエスト防止
        if (isFetchAiUsageStatusRef.current) {
            return null;
        }

        try {
            isFetchAiUsageStatusRef.current = true;
            incrementLoadingCount();

            const { data: responseData } =
                await axios.get<IAiUsageStatusResponse>('/ai/usage', {
                    timeout: TIMEOUT_MS,
                });

            if (responseData.success && responseData.data) {
                return responseData.data;
            }

            return null;
        } catch (error) {
            handleApiError(error);
            return null;
        } finally {
            isFetchAiUsageStatusRef.current = false;
            decrementLoadingCount();
        }
    }, [handleApiError, incrementLoadingCount, decrementLoadingCount]);

    /**
     * AI 利用状況を取得する
     * @description
     * AI 利用状況を取得し、ステートに保存する
     */
    React.useEffect(() => {
        let cancelled = false;

        const loadAiUsageStatus = async () => {
            const status = await fetchAiUsageStatus();

            if (!cancelled && status) {
                setAiUsageStatus(status);
            }
        };

        void loadAiUsageStatus();

        return () => {
            cancelled = true;
        };
    }, [fetchAiUsageStatus]);


    return {
        aiUsageStatus,
        isAiLimitReached,
        fetchAiUsageStatus,
        incrementUsageCount,
    };
};
