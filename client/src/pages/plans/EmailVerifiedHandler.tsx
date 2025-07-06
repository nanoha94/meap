'use client';
import { useEffect } from 'react';
import { useSnackbars } from '@/contexts';
import { usePathname, useRouter, useSearchParams } from 'next/navigation';

const EmailVerifiedHandler = () => {
    const router = useRouter();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const { addSnackbar } = useSnackbars();

    useEffect(() => {
        if (searchParams?.get('verified') === '1') {
            addSnackbar('success', 'メールアドレス認証が完了しました');
        }

        const newParams = new URLSearchParams(searchParams?.toString());
        newParams.delete('verified');
        router.replace(`${pathname}?${newParams.toString()}`);
    }, []);

    return <></>;
};

export default EmailVerifiedHandler;
