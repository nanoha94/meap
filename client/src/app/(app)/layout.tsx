import { cookies } from 'next/headers';

import {
    DataHandler,
    FooterNavigation,
    RedirectHandler,
    SideNavigation,
    SnackbarHandler,
    VerifiedHandler,
} from '@/components';
import {
    fetchData,
    fetchDataParallel,
    type FetchDataResult,
} from '@/lib/apiClient';
import { IAiUsageStatus, IAiUsageStatusResponse, IGetMasterResponse, IGetUserResponse } from '@/types';
import { handleAuthRedirect } from '@/utils';

export const dynamic = 'force-dynamic';

interface Props {
    children: React.ReactNode;
}

const AppLayout = async ({ children }: Props) => {
    let user: IGetUserResponse | null = null;
    let masterData: IGetMasterResponse | null = null;
    let aiUsageStatus: IAiUsageStatus | null = null;
    let fetchErrorMessages: string[] = [];

    const { data: parallelData, errorMessage: parallelError } =
        await fetchDataParallel<
            [
                FetchDataResult<IGetUserResponse>,
                FetchDataResult<IGetMasterResponse>,
                FetchDataResult<IAiUsageStatusResponse>,
            ]
        >([
            signal =>
                fetchData<IGetUserResponse>('/user', {
                    suppressUnauthorizedLog: true,
                    signal,
                }),
            signal =>
                fetchData<IGetMasterResponse>('/master', {
                    suppressUnauthorizedLog: true,
                    signal,
                }),
            signal =>
                fetchData<IAiUsageStatusResponse>('/ai/usage', {
                    suppressUnauthorizedLog: true,
                    signal,
                }),
        ]);

    if (parallelError || !parallelData) {
        handleAuthRedirect(null, false);
    } else {
        const [
            { data: userData, errorMessage: userError },
            { data: masterDataResult, errorMessage: masterError },
            { data: aiUsageResponse, errorMessage: aiUsageError },
        ] = parallelData;

        if (userError || !userData?.success) {
            handleAuthRedirect(null, false);
        } else {
            user = userData;
            handleAuthRedirect(user.data, false);

            if (masterDataResult?.success) {
                masterData = masterDataResult;
            }

            if (aiUsageResponse?.success) {
                aiUsageStatus = aiUsageResponse.data;
            }

            fetchErrorMessages = [
                masterError ||
                (masterDataResult && !masterDataResult.success
                    ? masterDataResult.message
                    : ''),
                aiUsageError ||
                (aiUsageResponse && !aiUsageResponse.success
                    ? aiUsageResponse.message
                    : ''),
            ].filter(Boolean);
        }
    }

    // RSCでクッキーを取得する正しい方法
    const cookieStore = await cookies();
    const redirectPath = cookieStore.get('redirectPath')?.value;

    return (
        <div className="min-h-dvh h-full flex flex-col">
            <div className="flex h-[calc(100dvh-80px)] md:h-full mb-20 md:mb-0">
                <SideNavigation className="z-10 hidden md:block" />
                <div className="flex-1 h-[calc(100dvh-80px)] md:h-dvh bg-primary-background md:w-[calc(100vw-160px)] md:ml-[160px] overflow-y-auto">
                    {children}
                </div>
            </div>
            <FooterNavigation className="md:hidden" />
            {redirectPath && <RedirectHandler redirectPath={redirectPath} />}
            {fetchErrorMessages.map((message, index) => (
                <SnackbarHandler key={index} type="error" message={message} />
            ))}
            <VerifiedHandler />
            <DataHandler
                user={user!.data}
                masterData={masterData?.data ?? null}
                aiUsageStatus={aiUsageStatus}
            />
        </div>
    );
};

export default AppLayout;
