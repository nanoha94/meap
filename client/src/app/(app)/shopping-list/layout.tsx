import React from 'react';
import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: '買い物リスト',
};

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
