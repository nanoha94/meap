'use client';

import React from 'react';

import {
    BILLING_SUBSCRIPTION_TYPE,
    BillingPackType,
    BillingSubscriptionType,
} from '@/constants';
import axios from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import {
    IBillingInvoices,
    IBillingStatus,
    IGetBillingInvoicesResponse,
    IGetBillingStatusResponse,
    IPostBillingCancelResponse,
    IPostBillingPackPurchaseResponse,
    IPostBillingResumeResponse,
    IPostBillingCardUpdateResponse,
    IPostBillingSubscripeResponse,
} from '@/types';

import { useApiErrorHandler } from './useApiErrorHandler';
import { useSnackbars } from '../useSnackbars';

/**
 * 課金 API
 */
export const useBillingApi = () => {
    const incrementLoadingCount = useGlobalStore(
        state => state.incrementLoadingCount,
    );
    const decrementLoadingCount = useGlobalStore(
        state => state.decrementLoadingCount,
    );
    const { handleApiError } = useApiErrorHandler();
    const { addSnackbar } = useSnackbars();
    const [billingStatus, setBillingStatus] =
        React.useState<IBillingStatus | null>(null);
    const [billingInvoices, setBillingInvoices] =
        React.useState<IBillingInvoices | null>(null);

    const isFetchBillingStatusRef = React.useRef(false);
    const isFetchInvoicesRef = React.useRef(false);
    const isCreateSubscriptionRef = React.useRef(false);
    const isCancelSubscriptionRef = React.useRef(false);
    const isPurchasePackRef = React.useRef(false);
    const isResumeSubscriptionRef = React.useRef(false);
    const isUpdateCardRef = React.useRef(false);

    /**
     * 課金・サブスクリプション状態を API から取得する（state は更新しない）
     */
    const loadBillingStatus = React.useCallback(async (): Promise<
        IBillingStatus | null
    > => {
        if (isFetchBillingStatusRef.current) {
            return null;
        }

        try {
            isFetchBillingStatusRef.current = true;
            incrementLoadingCount();

            const { data: responseData } =
                await axios.get<IGetBillingStatusResponse>('/billing/status');

            if (responseData.success && responseData.data) {
                return responseData.data;
            }

            return null;
        } catch (error) {
            handleApiError(error);
            return null;
        } finally {
            isFetchBillingStatusRef.current = false;
            decrementLoadingCount();
        }
    }, [handleApiError, incrementLoadingCount, decrementLoadingCount]);

    /**
     * 課金・サブスクリプション状態を取得し、state に反映する
     */
    const fetchBillingStatus = React.useCallback(async (): Promise<
        IBillingStatus | null
    > => {
        const status = await loadBillingStatus();

        if (status) {
            setBillingStatus(status);
        }

        return status;
    }, [loadBillingStatus]);

    /**
     * 請求履歴・次回お支払い予定を API から取得する（state は更新しない）
     */
    const loadInvoices = React.useCallback(async (): Promise<
        IBillingInvoices | null
    > => {
        if (isFetchInvoicesRef.current) {
            return null;
        }

        try {
            isFetchInvoicesRef.current = true;
            incrementLoadingCount();

            const { data: responseData } =
                await axios.get<IGetBillingInvoicesResponse>('/billing/invoices');

            if (responseData.success && responseData.data) {
                return responseData.data;
            }

            return null;
        } catch (error) {
            handleApiError(error);
            return null;
        } finally {
            isFetchInvoicesRef.current = false;
            decrementLoadingCount();
        }
    }, [handleApiError, incrementLoadingCount, decrementLoadingCount]);

    /**
     * 請求履歴・次回お支払い予定を取得し、state に反映する
     */
    const fetchInvoices = React.useCallback(async (): Promise<
        IBillingInvoices | null
    > => {
        const invoices = await loadInvoices();

        if (invoices) {
            setBillingInvoices(invoices);
        }

        return invoices;
    }, [loadInvoices]);

    /**
     * サブスクリプションを開始する
     */
    const createSubscription = React.useCallback(
        async (
            subscriptionType: BillingSubscriptionType = BILLING_SUBSCRIPTION_TYPE.STANDARD,
            cardToken?: string,
        ): Promise<boolean> => {
            if (isCreateSubscriptionRef.current) {
                return false;
            }

            try {
                isCreateSubscriptionRef.current = true;
                incrementLoadingCount();

                const { data: responseData } =
                    await axios.post<IPostBillingSubscripeResponse>(
                        `/billing/subscription/${subscriptionType}`,
                        cardToken ? { cardToken } : {},
                    );

                if (responseData.success && responseData.data) {
                    setBillingStatus(responseData.data);
                    void fetchInvoices();
                    addSnackbar(
                        'success',
                        responseData.message ||
                        'サブスクリプションを開始しました。',
                    );
                    return true;
                }

                return false;
            } catch (error) {
                handleApiError(error);
                return false;
            } finally {
                isCreateSubscriptionRef.current = false;
                decrementLoadingCount();
            }
        },
        [
            handleApiError,
            incrementLoadingCount,
            decrementLoadingCount,
            fetchInvoices,
            addSnackbar,
        ],
    );

    /**
     * サブスクリプションを期間終了時に解約する
     */
    const cancelSubscription = React.useCallback(async (): Promise<boolean> => {
        if (isCancelSubscriptionRef.current) {
            return false;
        }

        try {
            isCancelSubscriptionRef.current = true;
            incrementLoadingCount();

            const { data: responseData } =
                await axios.post<IPostBillingCancelResponse>(
                    '/billing/subscription/cancel',
                );

            if (responseData.success && responseData.data) {
                setBillingStatus(responseData.data);
                void fetchInvoices();
                addSnackbar(
                    'success',
                    responseData.message ||
                    'サブスクリプションの解約を受け付けました。',
                );
                return true;
            }

            return false;
        } catch (error) {
            handleApiError(error);
            return false;
        } finally {
            isCancelSubscriptionRef.current = false;
            decrementLoadingCount();
        }
    }, [
        handleApiError,
        incrementLoadingCount,
        decrementLoadingCount,
        fetchInvoices,
        addSnackbar,
    ]);

    /**
     * 買い切りパックを購入する
     */
    const purchasePack = React.useCallback(
        async (packType: BillingPackType, cardToken?: string): Promise<boolean> => {
            if (isPurchasePackRef.current) {
                return false;
            }

            try {
                isPurchasePackRef.current = true;
                incrementLoadingCount();

                const { data: responseData } =
                    await axios.post<IPostBillingPackPurchaseResponse>(
                        `/billing/packs/${packType}`,
                        cardToken ? { cardToken } : {},
                    );

                if (responseData.success && responseData.data) {
                    setBillingStatus(responseData.data);
                    void fetchInvoices();
                    addSnackbar(
                        'success',
                        responseData.message ||
                        '買い切りパックを購入しました。',
                    );
                    return true;
                }

                return false;
            } catch (error) {
                handleApiError(error);
                return false;
            } finally {
                isPurchasePackRef.current = false;
                decrementLoadingCount();
            }
        },
        [
            handleApiError,
            incrementLoadingCount,
            decrementLoadingCount,
            fetchInvoices,
            addSnackbar,
        ],
    );

    /**
     * プラン変更予定を取り消してサブスクリプションを継続する
     */
    const resumeSubscription = React.useCallback(async (): Promise<boolean> => {
        if (isResumeSubscriptionRef.current) {
            return false;
        }

        try {
            isResumeSubscriptionRef.current = true;
            incrementLoadingCount();

            const { data: responseData } =
                await axios.post<IPostBillingResumeResponse>(
                    '/billing/subscription/resume',
                );

            if (responseData.success && responseData.data) {
                setBillingStatus(responseData.data);
                void fetchInvoices();
                addSnackbar(
                    'success',
                    responseData.message ||
                    'プラン変更予定を取り消しました。',
                );
                return true;
            }

            return false;
        } catch (error) {
            handleApiError(error);
            return false;
        } finally {
            isResumeSubscriptionRef.current = false;
            decrementLoadingCount();
        }
    }, [
        handleApiError,
        incrementLoadingCount,
        decrementLoadingCount,
        fetchInvoices,
        addSnackbar,
    ]);

    /**
     * カード情報を更新する
     */
    const updateCard = React.useCallback(
        async (cardToken: string): Promise<boolean> => {
            if (isUpdateCardRef.current) {
                return false;
            }

            try {
                isUpdateCardRef.current = true;
                incrementLoadingCount();

                const { data: responseData } =
                    await axios.post<IPostBillingCardUpdateResponse>(
                        '/billing/card',
                        { cardToken },
                    );

                if (responseData.success && responseData.data) {
                    setBillingStatus(responseData.data);
                    addSnackbar(
                        'success',
                        responseData.message ||
                        '支払い方法を更新しました。',
                    );
                    return true;
                }

                return false;
            } catch (error) {
                handleApiError(error);
                return false;
            } finally {
                isUpdateCardRef.current = false;
                decrementLoadingCount();
            }
        },
        [
            handleApiError,
            incrementLoadingCount,
            decrementLoadingCount,
            addSnackbar,
        ],
    );

    // React.useEffect(() => {
    //     void loadBillingStatus().then(status => {
    //         if (status) {
    //             setBillingStatus(status);
    //         }
    //     });

    //     return () => {
    //         isFetchBillingStatusRef.current = false;
    //     };
    // }, [loadBillingStatus]);

    // React.useEffect(() => {
    //     void loadInvoices().then(invoices => {
    //         if (invoices) {
    //             setBillingInvoices(invoices);
    //         }
    //     });

    //     return () => {
    //         isFetchInvoicesRef.current = false;
    //     };
    // }, [loadInvoices]);

    return {
        billingStatus,
        billingInvoices,
        fetchBillingStatus,
        fetchInvoices,
        createSubscription,
        cancelSubscription,
        purchasePack,
        resumeSubscription,
        updateCard,
    };
};
