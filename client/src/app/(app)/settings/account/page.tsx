import React, { Suspense } from 'react';
import AccountPage from '@/pages/settings/account/AccountPage';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import { IGetInvitationDetailResponse } from '@/types';

interface AccountWithDataProps {
    token: string;
}
const AccountWithData = async ({ token }: AccountWithDataProps) => {

    const { data: invitationDetail, errorMessage } = await fetchData<IGetInvitationDetailResponse>(`/invitations/${token}`);

    return (
        <AccountPage
            invitationDetail={invitationDetail?.data ?? null}
            errorMessage={errorMessage}
        />
    );
};

interface Props {
    searchParams: Promise<{
        token?: string;
    }>;
}
const Page = async ({ searchParams }: Props) => {
    const { token } = await searchParams;

    return (
        <Suspense fallback={<Loading />}>
            {token && token.length > 0 ? <AccountWithData token={token} /> : <AccountPage />}
        </Suspense>
    );
};
export default Page;
