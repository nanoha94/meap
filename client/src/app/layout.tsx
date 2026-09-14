import React from 'react';
import type { Metadata, Viewport } from 'next';

import { AlertDialog, Dialog, LoadingAnimation, Snackbars } from '@/components';
import { NOTO_SANS_JP } from '@/constants';
import '@/styles/global.css';
import {
    FRONTEND_BASE_URL,
    LINK_TO,
    METADATA,
    createRootSocialMetadata,
    getRobotsMetadata,
} from '@/constants';

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
    metadataBase: new URL(FRONTEND_BASE_URL),
    title: {
        default: METADATA.SITE_NAME,
        template: `%s | ${METADATA.SITE_NAME}`,
    },
    description: METADATA.SITE_DESCRIPTION,
    robots: getRobotsMetadata(),
    ...createRootSocialMetadata(LINK_TO.LP),
};

export default RootLayout;
