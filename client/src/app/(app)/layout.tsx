import { FooterNavigation, SideNavigation } from '@/components/common';
import { IGetUserResponse } from '@/types/api';
import { apiClient } from '@/lib/apiClient';
import { redirect } from 'next/navigation';
import UserHandler from '@/components/handlers/UserHandler';
import { RedirectHandler } from '@/components/handlers';
import { cookies } from 'next/headers';

export const dynamic = 'force-dynamic';

interface Props {
    children: React.ReactNode;
}
const AppLayout = async ({ children }: Props) => {
    let user: IGetUserResponse;
    try {
        user = await apiClient('/user');
    } catch (error) {
        console.error('Failed to fetch user:', error);
        redirect('/login');
    }

    // メールアドレス未認証の場合はリダイレクト
    if (!user.email_verified_at) {
        redirect('/email/verify');
    }

    // RSCでクッキーを取得する正しい方法
    const cookieStore = cookies();
    const redirectPath = cookieStore.get('redirectPath')?.value;

    return (
        <div className="h-screen flex flex-col">
            {redirectPath && <RedirectHandler redirectPath={redirectPath} />}
            <UserHandler user={user} />
            <div className="flex h-full">
                <SideNavigation user={user} className="z-10 hidden md:block" />
                <div className="flex-1 bg-primary-background">{children}</div>
            </div>
            <FooterNavigation className="md:hidden" />
        </div>
    );
};

export default AppLayout;
