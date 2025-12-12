import Link from 'next/link';
import ApplicationLogo from '@/components/ApplicationLogo';
import { IGetUserResponse } from '@/types/api';
import { fetchData } from '@/lib/apiClient';
import { handleAuthRedirect } from '@/utils';
import { SnackbarHandler } from '@/components/handlers';

// 動的レンダリングを強制（クッキーを使用するため）
export const dynamic = 'force-dynamic';

export const metadata = {
    title: 'Laravel',
};

interface Props {
    children: React.ReactNode;
}

const AuthLayout = async ({ children }: Props) => {
    const { data: user, errorMessage } =
        await fetchData<IGetUserResponse>('/user');
    if (user) {
        handleAuthRedirect(user, true);
    }

    return (
        <>
            {errorMessage && (
                <SnackbarHandler type="error" message={errorMessage} />
            )}
            <div className="max-w-xl mx-auto pt-10 pb-20 px-5 flex flex-col gap-y-16">
                <Link href="/" className="w-fit mx-auto block">
                    <ApplicationLogo className="w-60 h-auto fill-current text-gray-500" />
                </Link>
                {children}
            </div>
        </>
    );
};

export default AuthLayout;
