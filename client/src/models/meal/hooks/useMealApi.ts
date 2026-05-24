'use client';

import React from 'react';

import { TIMEOUT_MS } from '@/constants';
import { useApiErrorHandler, useSnackbars } from '@/hooks';
import axios from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import { IBaseApiResponse } from '@/types';

export const useMealApi = () => {
    // store
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);

    // hook
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isDeleteRequestRef = React.useRef(false);

    /**
     * 献立（meal）削除
     * @param mealPlanId 献立プランID
     * @param id 削除する献立のID
     */
    const deleteMeal = React.useCallback(
        async (mealPlanId: string, id: string): Promise<boolean> => {
            if (isDeleteRequestRef.current) {
                return false;
            }

            try {
                isDeleteRequestRef.current = true;
                incrementLoadingCount();
                const { data: responseData } = await axios.delete<IBaseApiResponse>(
                    `/meal-plans/${mealPlanId}/meals/${id}`,
                    {
                        timeout: TIMEOUT_MS,
                    },
                );
                if (responseData.success) {
                    addSnackbar(
                        'success',
                        responseData.message || 'リクエストが正常に完了しました',
                    );
                    return true;
                }
                addSnackbar(
                    'error',
                    responseData.message || '献立の削除に失敗しました',
                );
                return false;
            } catch (error) {
                handleApiError(error);
                return false;
            } finally {
                isDeleteRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [incrementLoadingCount, decrementLoadingCount, addSnackbar, handleApiError],
    );

    return {
        deleteMeal
    };
};