import { AlertDialog, FooterNavigation, SideNavigation } from '@/components/common';
import { IGetUserResponse, IGetMasterResponse } from '@/types/api';
import { fetchData } from '@/lib/apiClient';
import { DataHandler, RedirectHandler } from '@/components/handlers';
import { cookies } from 'next/headers';
import { handleAuthRedirect } from '@/utils';
import { defaultMasterData } from '@/models/master';

export const dynamic = 'force-dynamic';

interface Props {
    children: React.ReactNode;
}

const AppLayout = async ({ children }: Props) => {
    let user: IGetUserResponse | null = null;
    let masterData: IGetMasterResponse = { data: defaultMasterData };

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
            {redirectPath && <RedirectHandler redirectPath={redirectPath} />}
            <DataHandler user={user!.data} masterData={masterData} />
            <div className="flex h-full mb-20 md:mb-0">
                <SideNavigation user={user!} className="z-10 hidden md:block" />
                <div className="flex-1 min-h-screen h-full bg-primary-background md:ml-[160px]">
                    {children}
                </div>
            </div>
            <FooterNavigation className="md:hidden" />
            <AlertDialog />
        </div>
    );
};

export default AppLayout;
