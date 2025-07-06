'use client';
import { useEffect } from 'react';
import { usePathname, useRouter } from 'next/navigation';

const RedirectHandler = () => {
    const router = useRouter();
    const pathname = usePathname();

    /**
     * リダイレクトパスがある場合はリダイレクト
     */
    useEffect(() => {
        const redirectPath = document.cookie
            .split('; ')
            .find(row => row.startsWith('redirectPath='))
            ?.split('=')[1];

        if (redirectPath) {
            const decodedPath = decodeURIComponent(redirectPath);
            if (decodedPath !== pathname) {
                router.push(decodedPath);
            }

            // cookieを削除
            document.cookie = `redirectPath=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; sameSite=strict; secure' : ''}`;
        }
    }, [router, pathname]);

    return <></>;
};

export default RedirectHandler;
