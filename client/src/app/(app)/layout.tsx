import Navigation2 from '@/components/xxx/Navigation';
import { Navigation } from '@/components/common';
import { IGetUserResponse } from '@/types/api';
import { apiClient } from '@/lib/apiClient';
import { redirect } from 'next/navigation';

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

    if (!user.email_verified_at) {
        redirect('/email/verify');
    }

    // const token = sessionStorage.getItem('invitationToken');
    // if (token) {
    //     redirect(`/settings/account?token=${token}`);
    // }

    return (
        <div className="h-screen flex flex-col">
            <Navigation2 user={user} />
            <div className="flex-1 bg-primary-background">{children}</div>
            <Navigation />
        </div>
    );
};

export default AppLayout;
