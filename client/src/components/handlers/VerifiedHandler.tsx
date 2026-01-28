'use client';
import React from 'react';
import { usePathname, useRouter, useSearchParams } from 'next/navigation';
import { useSnackbars } from '@/hooks/useSnackbars';

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
        if (!hasProcessed.current) {
            if (searchParams?.get('verified') === '1') {
                addSnackbar('success', 'メールアドレス認証が完了しました');
            }

            const newParams = new URLSearchParams(searchParams?.toString());
            newParams.delete('verified');
            router.replace(`${pathname}?${newParams.toString()}`);

            hasProcessed.current = true;
        }
    }, []);

    return <></>;
};

export default VerifiedHandler;
