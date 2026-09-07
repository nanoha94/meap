import React from 'react';

import { createPageMetadata, LINK_TO, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.TERMS, {
    path: LINK_TO.TERMS,
    description: METADATA.PAGE_DESCRIPTION.TERMS,
});

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
