import React, { Suspense } from 'react';
import AccountTopPage from '@/pages/settings/AccountTopPage';
import { Header } from '@/components/common';
import {
    IGetGroupUserResponse,
    IGetInvitationDetailResponse,
} from '@/types/api';
import { apiClient } from '@/lib/apiClient';
import { SnackbarHandler } from '@/components/handlers';
import Loading from '@/app/(app)/loading';
import { TIMEOUT_MS } from '@/constants';
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
    let users: IGetGroupUserResponse = { data: [], total: 0 };
    let invitationDetail: IGetInvitationDetailResponse | null = null;
    let errorMessage: string = '';

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

        users = await apiClient('/users', {
            signal: controller.signal,
        });

        if (!!token && token.length > 0) {
            invitationDetail = await apiClient(`/invitations/${token}`, {
                signal: controller.signal,
            });
        }

        clearTimeout(timeoutId);
    } catch (error) {
        console.error(error);
        // エラーオブジェクトから安全に文字列を抽出
        if (error instanceof Error && error.name === 'AbortError') {
            errorMessage =
                'リクエストがタイムアウトしました。再度お試しください。';
        } else {
            errorMessage =
                error instanceof Error
                    ? error.message
                    : typeof error === 'string'
                      ? error
                      : 'データの取得に失敗しました';
        }
    }

    return (
        <>
            <Header title="アカウント設定" />
            <main>
                {errorMessage && (
                    <SnackbarHandler type="error" message={errorMessage} />
                )}
                <AccountTopPage users={users['data']} />
                {invitationDetail && token && (
                    <JoinDialog invitationDetail={invitationDetail} />
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
