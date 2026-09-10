import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';

import {
    DataHandler,
    FooterNavigation,
    RedirectHandler,
    SideNavigation,
    SnackbarHandler,
    VerifiedHandler,
} from '@/components';
import { LINK_TO } from '@/constants';
import {
    fetchData,
    fetchDataParallel,
    type FetchDataResult,
} from '@/lib/apiClient';
import { IAiUsageStatus, IAiUsageStatusResponse, IGetMasterResponse, IGetUserResponse } from '@/types';
import { isSafeRedirectPath } from '@/utils';

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

    // データ取得エラー時は /login へ
    if (parallelError || !parallelData) {
        redirect(LINK_TO.LOGIN);
    } else {
        const [
            { data: userData, errorMessage: userError },
            { data: masterDataResult, errorMessage: masterError },
            { data: aiUsageResponse, errorMessage: aiUsageError },
        ] = parallelData;

        // ユーザーデータ取得エラー時は /login へ
        if (userError || !userData?.success) {
            redirect(LINK_TO.LOGIN);
        } else {
            user = userData;

            // 未認証は /email/verify へ
            if (!user.data.email_verified_at) {
                redirect(LINK_TO.EMAIL_VERIFY);
            }

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

    const cookieStore = await cookies();
    const rawRedirectPath = cookieStore.get('redirectPath')?.value;
    const redirectPath = rawRedirectPath && isSafeRedirectPath(rawRedirectPath)
        ? rawRedirectPath
        : undefined;

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
