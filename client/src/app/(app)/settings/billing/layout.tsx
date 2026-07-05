import React from 'react';
import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: 'プラン管理',
};

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
