import React from 'react';

import { AlertDialog, Dialog, LoadingAnimation, Snackbars } from '@/components';
import { NOTO_SANS_JP } from '@/constants';
import '@/styles/global.css';

interface RootLayoutProps {
    children: React.ReactNode;
}

const RootLayout = ({ children }: RootLayoutProps) => {
    return (
        <html lang="en" className={NOTO_SANS_JP.variable}>
            <body className="text-base text-black">
                {children}
                <Snackbars />
                <LoadingAnimation />
                <AlertDialog />
                <Dialog />
            </body>
        </html>
    );
};

export const metadata = {
    title: 'Laravel',
};

export default RootLayout;
