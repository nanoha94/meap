"use client";
import React from "react";
import { useRouter } from "next/navigation";

import { TIMEOUT_MS } from "@/constants";
import { useApiErrorHandler, useSnackbars } from "@/hooks";
import axios from "@/lib/axios";
import { useGlobalStore } from "@/stores";

export const useMealApi = () => {
    // store
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);

    // hook
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isDeleteRequestRef = React.useRef(false);

    /**
     * 献立プラン作成
     * @param data 作成する献立プランデータ
     */
    const deleteMeal = React.useCallback(
        async (
            mealPlanId: string, id: string
        ) => {
            // 重複リクエスト防止
            if (isDeleteRequestRef.current) {
                return;
            }

            try {
                isDeleteRequestRef.current = true;
                incrementLoadingCount();
                const { data: responseData } = await axios.delete(`/meal-plans/${mealPlanId}/meals/${id}`, {
                    timeout: TIMEOUT_MS,
                });
                if (responseData.success) {
                    addSnackbar('success', responseData.message ?? 'リクエストが正常に完了しました');
                    router.refresh();
                }
            } catch (error) {
                handleApiError(error);
            } finally {
                isDeleteRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [incrementLoadingCount, decrementLoadingCount, router, addSnackbar, handleApiError],
    );

    return {
        deleteMeal
    };
};