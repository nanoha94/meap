import React from 'react';
import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: '利用規約',
    description:
        'meap の利用規約。本サービスをご利用いただく際の条件、お客様が登録するコンテンツの取扱い、グループ共有機能、禁止事項、免責事項等について記載しています。',
};

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
