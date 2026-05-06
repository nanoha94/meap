'use client';
import { useEffect } from 'react';

interface Props {
    redirectPath: string;
}

const RedirectHandler = ({ redirectPath }: Props) => {
    useEffect(() => {
        // リダイレクト
        window.location.href = redirectPath;

        // クッキーを削除
        document.cookie = `redirectPath=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; sameSite=strict; secure`;
    }, [redirectPath]);

    return <></>;
};

export default RedirectHandler;
