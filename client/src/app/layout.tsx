import React from 'react';
import type { Metadata, Viewport } from 'next';

import { AlertDialog, Dialog, LoadingAnimation, Snackbars } from '@/components';
import { NOTO_SANS_JP } from '@/constants';
import '@/styles/global.css';
import { LINK_TO, METADATA, createRootSocialMetadata } from '@/constants';

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

export const viewport: Viewport = {
    viewportFit: 'cover',
};

export const metadata: Metadata = {
    metadataBase: new URL(
        process.env.NEXT_PUBLIC_FRONTEND_URL ?? 'http://localhost:3000',
    ),
    title: {
        default: METADATA.SITE_NAME,
        template: `%s | ${METADATA.SITE_NAME}`,
    },
    description: METADATA.SITE_DESCRIPTION,
    ...createRootSocialMetadata(LINK_TO.LP),
};

export default RootLayout;
