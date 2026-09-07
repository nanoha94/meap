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

interface Props {
    searchParams: Promise<{
        checkout?: string;
    }>;
}

const Page = async ({ searchParams }: Props) => {
    const { checkout } = await searchParams;

    return (
        <Suspense fallback={<Loading />}>
            <BillingPageWithData checkoutQuery={checkout} />
        </Suspense>
    );
};

export default Page;

interface BillingPageWithDataProps {
    checkoutQuery: string | undefined;
}

const BillingPageWithData = async ({
    checkoutQuery,
}: BillingPageWithDataProps) => {
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
                checkoutQuery={checkoutQuery}
                billingStatus={billingStatus}
                billingInvoices={billingInvoices}
            />
        </>
    );
};
