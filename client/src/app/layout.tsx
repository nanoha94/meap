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
    title: 'meap',
};

export default RootLayout;
