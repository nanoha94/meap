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
            <div className="min-h-dvh bg-primary-background">
                <header className="px-5 py-4">
                    <Link
                        href={LINK_TO.LP}
                        className="inline-block w-fit transition-opacity hover:opacity-80">
                        <Image
                            src="/images/meap-logo2.png"
                            alt="meap"
                            width={1224}
                            height={456}
                            loading="eager"
                            className="h-[42px] w-auto"
                        />
                    </Link>
                </header>
                <div className="mx-auto flex w-full max-w-xl flex-col gap-y-10 px-5 pb-20 pt-6">
                    {children}
                </div>
            </div>
        </>
    );
};

export default AuthLayout;
