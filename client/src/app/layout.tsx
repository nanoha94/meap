import React from 'react';
import type { Metadata } from 'next';

import { AlertDialog, Dialog, LoadingAnimation, Snackbars } from '@/components';
import { NOTO_SANS_JP } from '@/constants';
import '@/styles/global.css';

interface RootLayoutProps {
    children: React.ReactNode;
}

const RootLayout = ({ children }: RootLayoutProps) => {
    return (
        <html lang="ja" className={NOTO_SANS_JP.variable}>
            <body
                className={`${NOTO_SANS_JP.className} text-base text-black`}>
                {children}
                <Snackbars />
                <Dialog />
                <AlertDialog />
                <LoadingAnimation />
            </body>
        </html>
    );
};

export const metadata: Metadata = {
    metadataBase: new URL(
        process.env.NEXT_PUBLIC_FRONTEND_URL ??
        'http://localhost:3000',
    ),
    title: {
        default: 'meap',
        template: '%s | meap',
    },
    description: 'meapは、レシピ・献立・買い物リストをまとめて管理できるアプリです。',
    openGraph: {
        title: 'meap',
        description: 'meapは、レシピ・献立・買い物リストをまとめて管理できるアプリです。',
    },
};

export default RootLayout;
