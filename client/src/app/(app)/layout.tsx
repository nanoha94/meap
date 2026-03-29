import { cookies } from 'next/headers';

import {
    DataHandler,
    FooterNavigation,
    RedirectHandler,
    SideNavigation,
    VerifiedHandler,
} from '@/components';
import { fetchData } from '@/lib/apiClient';
import { IGetMasterResponse, IGetUserResponse } from '@/types';
import { handleAuthRedirect } from '@/utils';

export const dynamic = 'force-dynamic';

interface Props {
    children: React.ReactNode;
}

const AppLayout = async ({ children }: Props) => {
    let user: IGetUserResponse | null = null;
    let masterData: IGetMasterResponse | null = null;

    // まずuserを取得して認証チェック
    const { data: userData, errorMessage: userError } =
        await fetchData<IGetUserResponse>('/user');

    if (userError || !userData?.success) {
        handleAuthRedirect(null, false);
    } else {
        user = userData;
        handleAuthRedirect(user.data, false);

        // 認証が成功した場合のみmasterDataを取得
        const { data: masterDataResult } =
            await fetchData<IGetMasterResponse>('/master');
        if (masterDataResult) {
            masterData = masterDataResult;
        }
    }

    // RSCでクッキーを取得する正しい方法
    const cookieStore = cookies();
    const redirectPath = cookieStore.get('redirectPath')?.value;

    return (
        <div className="min-h-screen h-full flex flex-col">
            <div className="flex h-[calc(100vh-80px)] md:h-full mb-20 md:mb-0">
                <SideNavigation className="z-10 hidden md:block" />
                <div className="flex-1 h-[calc(100vh-80px)] md:h-screen bg-primary-background md:w-[calc(100vw-160px)] md:ml-[160px] overflow-y-auto">
                    {children}
                </div>
            </div>
            <FooterNavigation className="md:hidden" />
            {redirectPath && <RedirectHandler redirectPath={redirectPath} />}
            <VerifiedHandler />
            <DataHandler user={user!.data} masterData={masterData?.data ?? null} />
        </div>
    );
};

export default AppLayout;
