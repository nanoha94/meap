import Link from 'next/link';
import ApplicationLogo from '@/components/ApplicationLogo';
import { IGetUserResponse } from '@/types/api';
import { redirect } from 'next/navigation';
import { apiClient } from '@/lib/apiClient';

export const metadata = {
    title: 'Laravel',
};

interface Props {
    children: React.ReactNode;
}

const AuthLayout = async ({ children }: Props) => {
    let user: IGetUserResponse | null = null;

    try {
        user = await apiClient('/user');
    } catch (error) {
        console.error('Failed to fetch user:', error);
        // ユーザー情報が取得できない場合はnullのまま
    }

    // ユーザーが既にログインしている場合は /plan にリダイレクト
    if (user && !!user.email_verified_at) {
        redirect('/plan');
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
