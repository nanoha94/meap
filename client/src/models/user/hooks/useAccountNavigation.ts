import { usePathname, useRouter, useSearchParams } from 'next/navigation';

export const useAccountNavigation = () => {
    const router = useRouter();
    const pathname = usePathname();
    const searchParams = useSearchParams();

    /**
     * パスからトークンを削除
     */
    const removeTokenFromPath = () => {
        const newParams = new URLSearchParams(searchParams?.toString());
        newParams.delete('token');
        router.replace(`${pathname}?${newParams.toString()}`);
    };

    return { removeTokenFromPath };
};
