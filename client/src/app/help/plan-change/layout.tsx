import React from 'react';

import { createPageMetadata, LINK_TO, METADATA } from '@/constants';

export const metadata = createPageMetadata(METADATA.PAGE.HELP_PLAN_CHANGE, {
    path: LINK_TO.HELP.PLAN_CHANGE,
    description: METADATA.PAGE_DESCRIPTION.HELP_PLAN_CHANGE,
});

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
