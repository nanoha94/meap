import { FooterNavigation, SideNavigation } from '@/components/common';
import { IGetUserResponse } from '@/types/api';
import { apiClient } from '@/lib/apiClient';
import { DataHandler, RedirectHandler } from '@/components/handlers';
import { cookies } from 'next/headers';
import { IGetMasterResponse } from '@/types/api/master';
import { handleAuthRedirect } from '@/utils/authHelpers';
import { defaultMasterData } from '@/models/master';

export const dynamic = 'force-dynamic';

interface Props {
    children: React.ReactNode;
}

const AppLayout = async ({ children }: Props) => {
    let user: IGetUserResponse;
    let masterData: IGetMasterResponse = { data: defaultMasterData };

    try {
        user = await apiClient('/user');
        handleAuthRedirect(user, false);

        masterData = await apiClient('/master');
    } catch (error) {
        console.error('Failed to fetch data:', error);
        handleAuthRedirect(null, false);
    }

    // RSCでクッキーを取得する正しい方法
    const cookieStore = cookies();
    const redirectPath = cookieStore.get('redirectPath')?.value;

    return (
        <div className="min-h-screen h-full flex flex-col">
            {redirectPath && <RedirectHandler redirectPath={redirectPath} />}
            <DataHandler user={user!} masterData={masterData} />
            <div className="flex h-full mb-20 md:mb-0">
                <SideNavigation user={user!} className="z-10 hidden md:block" />
                <div className="flex-1 min-h-screen h-full bg-primary-background md:ml-[160px]">
                    {children}
                </div>
            </div>
            <FooterNavigation className="md:hidden" />
        </div>
    );
};

export default AppLayout;
