import React, { Suspense } from 'react';
import AccountTop from '@/pages/settings/AccountTop';
import { Header } from '@/components/common';
import { IGetGroupUserResponse } from '@/types/api';
import { apiClient } from '@/lib/apiClient';
import { SnackbarHandler } from '@/components/handlers';
import Loading from '../../Loading';

interface Props {
    searchParams: {
        token: string;
    };
}

interface AccountWithDataProps {
    token: string;
}

const AccountWithData = async ({ token }: AccountWithDataProps) => {
    let users: IGetGroupUserResponse = { data: [], total: 0 };
    let errorMessage: string = '';
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);

        users = await apiClient('/users', {
            signal: controller.signal,
        });
        clearTimeout(timeoutId);
    } catch (error) {
        users = { data: [], total: 0 };
        console.error(error);
        // エラーオブジェクトから安全に文字列を抽出
        errorMessage =
            error instanceof Error
                ? error.message
                : typeof error === 'string'
                  ? error
                  : 'データの取得に失敗しました';
    }

    return (
        <>
            <Header title="アカウント設定" />
            <main>
                {errorMessage && (
                    <SnackbarHandler type="error" message={errorMessage} />
                )}
                <AccountTop users={users['data']} token={token} />
            </main>
        </>
    );
};

const Page = ({ searchParams }: Props) => {
    const { token } = searchParams;
    return (
        <Suspense fallback={<Loading />}>
            <AccountWithData token={token} />
        </Suspense>
    );
};
export default Page;
