import React from 'react';
import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: 'レシピ作成',
};

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
