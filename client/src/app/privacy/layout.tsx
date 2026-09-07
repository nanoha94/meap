import React from 'react';

import { createPageMetadata, LINK_TO, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.PRIVACY, {
    path: LINK_TO.PRIVACY,
    description: METADATA.PAGE_DESCRIPTION.PRIVACY,
});

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
