import React from 'react';

import { createPageMetadata, LINK_TO, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.LEGAL_COMMERCIAL, {
    path: LINK_TO.LEGAL.COMMERCIAL,
    description: METADATA.PAGE_DESCRIPTION.LEGAL_COMMERCIAL,
});

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
