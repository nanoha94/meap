import { Suspense } from 'react';

import { Loading, SnackbarHandler } from '@/components';
import {
    fetchData,
    fetchDataParallel,
    type FetchDataResult,
} from '@/lib/apiClient';
import BillingPage from '@/pages/settings/billing/BillingPage';
import {
    IBillingInvoices,
    IBillingStatus,
    IGetBillingInvoicesResponse,
    IGetBillingStatusResponse,
} from '@/types';

const Page = () => {
    return (
        <Suspense fallback={<Loading />}>
            <BillingPageWithData />
        </Suspense>
    );
};

export default Page;

const BillingPageWithData = async () => {
    let billingStatus: IBillingStatus | null = null;
    let billingInvoices: IBillingInvoices | null = null;
    let fetchErrorMessages: string[] = [];

    const { data: parallelData, errorMessage: parallelError } =
        await fetchDataParallel<
            [
                FetchDataResult<IGetBillingStatusResponse>,
                FetchDataResult<IGetBillingInvoicesResponse>,
            ]
        >([
            signal =>
                fetchData<IGetBillingStatusResponse>('/billing/status', {
                    signal,
                }),
            signal =>
                fetchData<IGetBillingInvoicesResponse>('/billing/invoices', {
                    signal,
                }),
        ]);

    if (parallelError || !parallelData) {
        fetchErrorMessages = [parallelError].filter(Boolean);
    } else {
        const [
            { data: billingStatusResponse, errorMessage: billingStatusError },
            { data: billingInvoicesResponse, errorMessage: billingInvoicesError },
        ] = parallelData;

        if (billingStatusResponse?.success) {
            billingStatus = billingStatusResponse.data;
        }

        if (billingInvoicesResponse?.success) {
            billingInvoices = billingInvoicesResponse.data;
        }

        fetchErrorMessages = [
            billingStatusError ||
            (billingStatusResponse && !billingStatusResponse.success
                ? billingStatusResponse.message
                : ''),
            billingInvoicesError ||
            (billingInvoicesResponse && !billingInvoicesResponse.success
                ? billingInvoicesResponse.message
                : ''),
        ].filter(Boolean);
    }

    return (
        <>
            {fetchErrorMessages.map((message, index) => (
                <SnackbarHandler key={index} type="error" message={message} />
            ))}
            <BillingPage
                billingStatus={billingStatus}
                billingInvoices={billingInvoices}
            />
        </>
    );
};
