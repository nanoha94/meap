'use client';
import React from 'react';
import { usePathname, useRouter, useSearchParams } from 'next/navigation';

import { useSnackbars } from '@/hooks';

const VerifiedHandler = () => {
    const router = useRouter();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const { addSnackbar } = useSnackbars();
    const hasProcessed = React.useRef(false);

    /**
     * メールアドレス認証が完了した場合に成功メッセージを表示
     */
    React.useEffect(() => {
        if (hasProcessed.current) {
            return;
        }
        hasProcessed.current = true;

        if (searchParams?.get('verified') !== '1') {
            return;
        }

        if (!pathname || !searchParams) {
            return;
        }

        addSnackbar('success', 'メールアドレス認証が完了しました');

        const newParams = new URLSearchParams(searchParams.toString());
        newParams.delete('verified');
        const query = newParams.toString();
        router.replace(query ? `${pathname}?${query}` : pathname);
    }, [addSnackbar, pathname, router, searchParams]);

    return <></>;
};

export default VerifiedHandler;
