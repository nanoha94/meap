'use client';

import React from 'react';

import {
    BILLING_SUBSCRIPTION_TYPE,
    BillingPackType,
    BillingSubscriptionType,
    TIMEOUT_MS,
} from '@/constants';
import axios from '@/lib/axios';
import { useGlobalStore } from '@/stores';
import {
    IBillingInvoices,
    IBillingStatus,
    IGetBillingInvoicesResponse,
    IGetBillingStatusResponse,
    IPostBillingPacksResponse,
    IPostBillingPortalResponse,
    IPostBillingResumeResponse,
    IPostBillingSubscripeResponse,
} from '@/types';
import { openStripeUrl } from '@/utils/stripeUrl';

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
    const isCreatePortalSessionRef = React.useRef(false);
    const isPurchasePackRef = React.useRef(false);
    const isResumeSubscriptionRef = React.useRef(false);

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
                await axios.get<IGetBillingStatusResponse>('/billing/status', {
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
                await axios.get<IGetBillingInvoicesResponse>(
                    '/billing/invoices',
                    {
                        timeout: TIMEOUT_MS,
                    },
                );

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
     * サブスクリプション開始（Stripe Checkout へリダイレクト）
     */
    const createSubscription = React.useCallback(
        async (
            subscriptionType: BillingSubscriptionType = BILLING_SUBSCRIPTION_TYPE.STANDARD,
        ): Promise<boolean> => {
            if (isCreateSubscriptionRef.current) {
                return false;
            }

            try {
                isCreateSubscriptionRef.current = true;
                incrementLoadingCount();

                const { data: responseData } =
                    await axios.post<IPostBillingSubscripeResponse>(
                        `/billing/subscribe/${subscriptionType}`,
                        {},
                        { timeout: TIMEOUT_MS },
                    );

                if (responseData.success && responseData.data?.checkoutUrl) {
                    if (
                        openStripeUrl(responseData.data.checkoutUrl)
                    ) {
                        return true;
                    }

                    addSnackbar(
                        'error',
                        '決済ページへの遷移に失敗しました。しばらく経ってから再度お試しください。',
                    );
                    return false;
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
            addSnackbar,
        ],
    );

    /**
     * Stripe Customer Portal セッション作成（Portal へリダイレクト）
     */
    const createPortalSession = React.useCallback(async (): Promise<boolean> => {
        if (isCreatePortalSessionRef.current) {
            return false;
        }

        try {
            isCreatePortalSessionRef.current = true;
            incrementLoadingCount();

            const { data: responseData } =
                await axios.post<IPostBillingPortalResponse>(
                    '/billing/portal',
                    {},
                    { timeout: TIMEOUT_MS },
                );

            if (responseData.success && responseData.data?.portalUrl) {
                if (openStripeUrl(responseData.data.portalUrl)) {
                    return true;
                }

                addSnackbar(
                    'error',
                    '決済ページへの遷移に失敗しました。しばらく経ってから再度お試しください。',
                );
                return false;
            }

            return false;
        } catch (error) {
            handleApiError(error);
            return false;
        } finally {
            isCreatePortalSessionRef.current = false;
            decrementLoadingCount();
        }
    }, [
        handleApiError,
        incrementLoadingCount,
        decrementLoadingCount,
        addSnackbar,
    ]);

    /**
     * 買い切りパック購入（Stripe Checkout へリダイレクト）
     */
    const purchasePack = React.useCallback(
        async (packType: BillingPackType): Promise<boolean> => {
            if (isPurchasePackRef.current) {
                return false;
            }

            try {
                isPurchasePackRef.current = true;
                incrementLoadingCount();

                const { data: responseData } =
                    await axios.post<IPostBillingPacksResponse>(
                        `/billing/packs/${packType}`,
                        {},
                        { timeout: TIMEOUT_MS },
                    );

                if (responseData.success && responseData.data?.checkoutUrl) {
                    if (
                        openStripeUrl(responseData.data.checkoutUrl)
                    ) {
                        return true;
                    }

                    addSnackbar(
                        'error',
                        '決済ページへの遷移に失敗しました。しばらく経ってから再度お試しください。',
                    );
                    return false;
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
                    {},
                    { timeout: TIMEOUT_MS },
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
        createPortalSession,
        purchasePack,
        resumeSubscription,
    };
};
