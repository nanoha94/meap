import React from 'react';
import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: 'プラン変更の仕組み',
    description:
        'meap のプラン変更（アップグレード・ダウングレード・解約）について、料金の請求タイミングと AI 利用回数の変動ルールを具体例とともに説明します。',
};

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
