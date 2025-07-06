import Navigation2 from '@/components/xxx/Navigation';
import { Navigation } from '@/components/common';
import { IGetUserResponse } from '@/types/api';
import { apiClient } from '@/lib/apiClient';
import { redirect } from 'next/navigation';
import UserHandler from '@/components/handlers/UserHandler';
import { RedirectHandler } from '@/components/handlers';

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

    return (
        <div className="h-screen flex flex-col">
            <RedirectHandler />
            <UserHandler user={user} />
            <Navigation2 user={user} />
            <div className="flex-1 bg-primary-background">{children}</div>
            <Navigation />
        </div>
    );
};

export default AppLayout;
