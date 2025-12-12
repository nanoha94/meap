import React, { Suspense } from 'react';
import AccountTopPage from '@/pages/settings/AccountTopPage';
import { Header, Loading } from '@/components/common';
import {
    IGetGroupUserResponse,
    IGetInvitationDetailResponse,
    IUser,
} from '@/types/api';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';
import { SnackbarHandler } from '@/components/handlers';
import dynamic from 'next/dynamic';

// 動的インポートでダイアログコンポーネントを遅延読み込み
const JoinDialog = dynamic(
    () => import('@/models/settings/components/JoinDialog/JoinDialog'),
    {
        ssr: false, // SSRでは読み込まない
        loading: () => null, // ローディング中は何も表示しない
    },
);

interface AccountWithDataProps {
    token: string;
}
const AccountWithData = async ({ token }: AccountWithDataProps) => {
    // tokenがある場合は2つのリクエストを並列実行、ない場合は1つだけ
    const requests: Array<
        (
            signal: AbortSignal,
        ) => Promise<IGetGroupUserResponse | IGetInvitationDetailResponse>
    > = [signal => apiClient<IGetGroupUserResponse>('/users', { signal })];

    if (token && token.length > 0) {
        requests.push(signal =>
            apiClient<IGetInvitationDetailResponse>(`/invitations/${token}`, {
                signal,
            }),
        );
    }

    const { data, errorMessage } = await fetchDataParallel<
        [IGetGroupUserResponse, IGetInvitationDetailResponse?]
    >(
        requests as Array<
            (
                signal: AbortSignal,
            ) => Promise<IGetGroupUserResponse | IGetInvitationDetailResponse>
        >,
    );

    const [users, invitationDetail] = data ?? [{ data: [], total: 0 }, null];

    return (
        <>
            <Header title="アカウント設定" />
            <main>
                {errorMessage && (
                    <SnackbarHandler type="error" message={errorMessage} />
                )}
                <AccountTopPage users={users?.data as IUser[]} />
                {invitationDetail && token && (
                    <JoinDialog invitationDetail={invitationDetail.data} />
                )}
            </main>
        </>
    );
};

interface Props {
    searchParams: {
        token: string;
    };
}
const Page = ({ searchParams }: Props) => {
    const { token } = searchParams;

    return (
        <Suspense fallback={<Loading />}>
            <AccountWithData token={token} />
        </Suspense>
    );
};
export default Page;
