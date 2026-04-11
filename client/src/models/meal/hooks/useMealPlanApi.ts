"use client";
import React from "react";
import { useRouter } from "next/navigation";

import { TIMEOUT_MS } from "@/constants";
import { useApiErrorHandler, useSnackbars } from "@/hooks";
import axios from "@/lib/axios";
import { useGlobalStore } from "@/stores";
import { IGetMealPlanIndexRequest, IGetMealPlanIndexResponse, IPostMealPlanResponse, IPostPutMealPlanRequest } from "@/types";
import { MealPlanFilterFormData } from "../types";

export const useMealPlanApi = () => {
    // store
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const decrementLoadingCount = useGlobalStore(state => state.decrementLoadingCount);

    // hook
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { handleApiError } = useApiErrorHandler();

    // 重複リクエスト防止用のフラグ
    const isFetchRequestRef = React.useRef(false);
    const isStoreRequestRef = React.useRef(false);
    const isUpdateRequestRef = React.useRef(false);
    const isDeleteRequestRef = React.useRef(false);

    /**
     * 献立プラン一覧を取得
     * @param filterOptions フィルターオプション
     * @returns 献立プラン一覧
     */
    const fetchMealPlans = React.useCallback(async (filterOptions?: MealPlanFilterFormData) => {
        // 重複リクエスト防止
        if (isFetchRequestRef.current) {
            return;
        }

        // パラメータをセット
        const params: IGetMealPlanIndexRequest = {
            date_from: filterOptions?.dateFrom,
            date_to: filterOptions?.dateTo,
            include_ingredients: filterOptions?.includeIngredients ?? false,
        };

        try {
            isFetchRequestRef.current = true;
            incrementLoadingCount();

            const { data: responseData } = await axios.get<IGetMealPlanIndexResponse>(
                `/meal-plans`,
                {
                    params,
                    timeout: TIMEOUT_MS,
                },
            );

            if (responseData.success) {
                return responseData.data;
            }
            return [];
        } catch (error) {
            handleApiError(error);
            return [];
        } finally {
            isFetchRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [incrementLoadingCount, decrementLoadingCount, router, addSnackbar, handleApiError]);

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
                    router.refresh();
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

        try {
            isUpdateRequestRef.current = true;
            incrementLoadingCount();

            // APIリクエスト
            const { data: responseData } = await axios.put(`/meal-plans/${data.id}`, data, {
                timeout: TIMEOUT_MS,
            });

            if (responseData.success) {
                router.refresh();
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

    /**
     * 献立プラン削除
     * @param id 削除する献立プランのID
     */
    const deleteMealPlan = React.useCallback(async (id: string) => {
        try {
            isDeleteRequestRef.current = true;
            incrementLoadingCount();
            const { data: responseData } = await axios.delete(`/meal-plans/${id}`, {
                timeout: TIMEOUT_MS,
            });
            if (responseData.success) {
                addSnackbar('success', responseData.message ?? 'リクエストが正常に完了しました');
                router.push('/plan/');
            }
        }
        catch (error) {
            handleApiError(error);
        }
        finally {
            isDeleteRequestRef.current = false;
            decrementLoadingCount();
        }
    }, [incrementLoadingCount, decrementLoadingCount, router, addSnackbar, handleApiError]);

    return {
        fetchMealPlans, storeMealPlan, updateMealPlan, deleteMealPlan
    };
};