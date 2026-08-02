import React from 'react';

import { createPageMetadata, METADATA } from '@/constants';

export const metadata = createPageMetadata(
    METADATA.PAGE.PRIVACY,
    METADATA.PAGE_DESCRIPTION.PRIVACY,
);

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
