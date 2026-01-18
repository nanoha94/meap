import React, { Suspense } from 'react';
import AccountPage from '@/pages/settings/account/AccountPage';
import { Loading } from '@/components/common';
import {
    IGetGroupUserResponse,
    IGetInvitationDetailResponse,
} from '@/types/api';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';

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
        <AccountPage
            users={users?.data ?? []}
            invitationDetail={invitationDetail?.data ?? null}
            errorMessage={errorMessage}
        />
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
