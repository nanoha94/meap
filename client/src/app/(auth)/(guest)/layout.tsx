import { redirect } from 'next/navigation';

import { LINK_TO } from '@/constants';
import { fetchCurrentUser } from '@/lib/fetchCurrentUser';

export const dynamic = 'force-dynamic';

interface Props {
    children: React.ReactNode;
}

/**
 * ゲスト向け (auth) ルート。ログイン中・未認証は /email/verify へ。
 */
const GuestAuthLayout = async ({ children }: Props) => {
    const { data: user } = await fetchCurrentUser();

    if (user?.data && !user.data.email_verified_at) {
        redirect(LINK_TO.EMAIL_VERIFY);
    }

    return children;
};

export default GuestAuthLayout;
