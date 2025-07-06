import { icons } from '@dicebear/collection';
import { createAvatar, Result } from '@dicebear/core';
import { usePathname, useRouter, useSearchParams } from 'next/navigation';

export const useAccountHandlers = () => {
    const iconAvatar = (id: string): Result =>
        createAvatar(icons, {
            seed: id,
            backgroundColor: [
                'b6e3f4', // 水色
                'ffd5dc', // ピンク
                'd1f7c4', // 黄緑
                'f4d03f', // 黄色
                'ffcfab', // オレンジ
                'bdc3c7', // グレー
                'e8daef', // 薄紫
                'aed6f1', // 青
            ],
        });

    /**
     * パスからトークンを削除
     */
    const removeTokenFromPath = () => {
        const router = useRouter();
        const pathname = usePathname();
        const searchParams = useSearchParams();
        const newParams = new URLSearchParams(searchParams?.toString());
        newParams.delete('token');
        router.replace(`${pathname}?${newParams.toString()}`);
    };

    return { iconAvatar, removeTokenFromPath };
};
