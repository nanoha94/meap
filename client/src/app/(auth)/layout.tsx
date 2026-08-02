import { headers } from 'next/headers';
import Image from 'next/image';
import Link from 'next/link';

import { SnackbarHandler } from '@/components';
import { LINK_TO } from '@/constants';
import { fetchData } from '@/lib/apiClient';
import { IGetUserResponse } from '@/types';
import { handleAuthRedirect } from '@/utils';

// 動的レンダリングを強制（クッキーを使用するため）
export const dynamic = 'force-dynamic';

interface Props {
    children: React.ReactNode;
}

const AuthLayout = async ({ children }: Props) => {
    const headerList = await headers();
    const pathname = headerList.get('x-pathname') ?? '';

    const { data: user, errorMessage } = await fetchData<IGetUserResponse>(
        '/user',
        { suppressUnauthorizedLog: true },
    );

    handleAuthRedirect(user?.data ?? null, true, { pathname });

    // 認証エラー（AUTHENTICATION_REQUIRED）はログインページでは表示しない
    const shouldShowError =
        errorMessage && errorMessage !== 'AUTHENTICATION_REQUIRED';

    return (
        <>
            {shouldShowError && (
                <SnackbarHandler type="error" message={errorMessage} />
            )}
            <div className="max-w-xl mx-auto pt-10 pb-20 px-5 flex flex-col gap-y-16">
                <Link href={LINK_TO.LP} className="w-[60%] mx-auto block">
                    <Image src="/images/meap-logo.png" alt="meap" width={297} height={307} loading="eager" className="w-full h-auto" />
                </Link>
                {children}
            </div>
        </>
    );
};

export default AuthLayout;
