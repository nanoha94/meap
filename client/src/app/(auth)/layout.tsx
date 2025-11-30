import Link from 'next/link';
import ApplicationLogo from '@/components/ApplicationLogo';
import { IGetUserResponse } from '@/types/api';
import { apiClient } from '@/lib/apiClient';
import { handleAuthRedirect } from '@/utils';

// 動的レンダリングを強制（クッキーを使用するため）
export const dynamic = 'force-dynamic';

export const metadata = {
    title: 'Laravel',
};

interface Props {
    children: React.ReactNode;
}

const AuthLayout = async ({ children }: Props) => {
    let user: IGetUserResponse | null = null;

    try {
        user = await apiClient('/user'); // 認証状態に基づいてリダイレクト
        handleAuthRedirect(user, true);
    } catch (error) {
        console.error('Failed to fetch user:', error);
        // ユーザー情報が取得できない場合はnullのまま
    }

    return (
        <div className="max-w-xl mx-auto pt-10 pb-20 px-5 flex flex-col gap-y-16">
            <Link href="/" className="w-fit mx-auto block">
                <ApplicationLogo className="w-60 h-auto fill-current text-gray-500" />
            </Link>
            {children}
        </div>
    );
};

export default AuthLayout;
