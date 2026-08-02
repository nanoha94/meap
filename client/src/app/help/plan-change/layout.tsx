import React from 'react';

import { createPageMetadata, METADATA } from '@/constants';

export const metadata = createPageMetadata(
    METADATA.PAGE.HELP_PLAN_CHANGE,
    METADATA.PAGE_DESCRIPTION.HELP_PLAN_CHANGE,
);

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
