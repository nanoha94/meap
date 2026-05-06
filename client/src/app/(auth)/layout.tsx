import type { Metadata } from 'next';
import Link from 'next/link';

import { SnackbarHandler } from '@/components';
import { fetchData } from '@/lib/apiClient';
import { IGetUserResponse } from '@/types';
import { handleAuthRedirect } from '@/utils';
import Image from 'next/image';

// 動的レンダリングを強制（クッキーを使用するため）
export const dynamic = 'force-dynamic';

export const metadata: Metadata = {
    title: 'アカウント | meap',
};

interface Props {
    children: React.ReactNode;
}

const AuthLayout = async ({ children }: Props) => {
    const { data: user, errorMessage } = await fetchData<IGetUserResponse>(
        '/user',
        { suppressUnauthorizedLog: true },
    );
    if (user) {
        handleAuthRedirect(user.data, true);
    }

    // 認証エラー（AUTHENTICATION_REQUIRED）はログインページでは表示しない
    const shouldShowError =
        errorMessage && errorMessage !== 'AUTHENTICATION_REQUIRED';

    return (
        <>
            {shouldShowError && (
                <SnackbarHandler type="error" message={errorMessage} />
            )}
            <div className="max-w-xl mx-auto pt-10 pb-20 px-5 flex flex-col gap-y-16">
                <Link href="/" className="w-[60%] mx-auto block">
                    <Image src="/images/meap-logo.png" alt="meap" width={297} height={307} className="w-full h-auto" />
                </Link>
                {children}
            </div>
        </>
    );
};

export default AuthLayout;
