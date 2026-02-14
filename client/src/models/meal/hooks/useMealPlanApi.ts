"use client";
import React from "react";
import { useRouter } from "next/navigation";

import { TIMEOUT_MS } from "@/constants";
import { useApiErrorHandler, useSnackbars } from "@/hooks";
import axios from "@/lib/axios";
import { useGlobalStore } from "@/stores";
import { IPostMealPlanResponse, IPostPutMealPlanRequest, IPutMealPlanResponse } from "@/types";



export const useMealPlanApi = () => {
    const { incrementLoadingCount, decrementLoadingCount } = useGlobalStore();
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isStoreRequestRef = React.useRef(false);
    const isUpdateRequestRef = React.useRef(false);
    //  const isDeleteRequestRef = React.useRef(false);

    /**
     * 献立プラン作成
     * @param data 作成する献立プランデータ
     */
    const storeMealPlan = React.useCallback(
        async (
            data: IPostPutMealPlanRequest,
        ) => {
            // 重複リクエスト防止
            if (isStoreRequestRef.current) {
                return;
            }

            const sendData: IPostPutMealPlanRequest = data;

            try {
                isStoreRequestRef.current = true;
                incrementLoadingCount();

                // APIリクエスト
                const res = await axios.post<IPostMealPlanResponse>(
                    `/meal-plans`,
                    sendData,
                    {
                        timeout: TIMEOUT_MS,
                    },
                );

                // レスポンスデータ
                const responseData: IPostMealPlanResponse = res.data;
                if (responseData.success) {
                    router.push('/plan/');
                    addSnackbar(
                        'success',
                        responseData.message ??
                        'リクエストが正常に完了しました',
                    );
                }
            } catch (error) {
                handleApiError(error);
            } finally {
                isStoreRequestRef.current = false;
                decrementLoadingCount();
            }
        },
        [incrementLoadingCount, decrementLoadingCount, router, addSnackbar, handleApiError],
    );

    /**
     * 献立プラン更新
     * @param data 更新する献立プランデータ
     */
    const updateMealPlan = React.useCallback(async (data: IPostPutMealPlanRequest) => {
        // 重複リクエスト防止
        if (isUpdateRequestRef.current) {
            return;
        }

        const sendData: IPostPutMealPlanRequest = data;

        try {
            isUpdateRequestRef.current = true;
            incrementLoadingCount();

            // APIリクエスト
            const res = await axios.put(`/meal-plans/${data.id}`, sendData, {
                timeout: TIMEOUT_MS,
            });

            // レスポンスデータ
            const responseData: IPutMealPlanResponse = res.data;
            if (responseData.success) {
                // TODO: プランページのクエリパラメータを変更するか検討（現状はyearとmonthを渡すことになっている）
                // 日付を変更してもリロード（再データフェッチ）しないようにする
                // planページでデータフェッチするのはyearかmonthが変更された場合のみ
                // router.push(`/plan?date=${date}`);
                addSnackbar(
                    'success',
                    responseData.message ??
                    'リクエストが正常に完了しました',
                );
            }
        } catch (error) {
            handleApiError(error);
        } finally {
            isUpdateRequestRef.current = false;
            decrementLoadingCount();
        }
    },
        [incrementLoadingCount, decrementLoadingCount, router, addSnackbar, handleApiError],
    );

    return {
        storeMealPlan, updateMealPlan,
    };
};