import { headers } from 'next/headers';
import { redirect } from 'next/navigation';

import { redirectIfServiceUnavailable } from '@/lib/apiClient';
import { fetchCurrentUser } from '@/lib/fetchCurrentUser';
import { buildLoginRedirectUrl } from '@/utils';

export const dynamic = 'force-dynamic';

interface Props {
    children: React.ReactNode;
}

/** セッション必須の (auth) ルート。未ログインは /login へ */
const SessionAuthLayout = async ({ children }: Props) => {
    const headerList = await headers();
    const search = headerList.get('x-search') ?? '';

    const result = await fetchCurrentUser();
    redirectIfServiceUnavailable(result);

    if (!result.data?.data) {
        redirect(buildLoginRedirectUrl(search));
    }

    return children;
};

export default SessionAuthLayout;
