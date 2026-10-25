import React from 'react';

import { createPrivatePageMetadata, METADATA } from '@/constants';

export const metadata = createPrivatePageMetadata(METADATA.PAGE.MAINTENANCE);

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
