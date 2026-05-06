import React from 'react';
import type { Metadata } from 'next';

export const metadata: Metadata = {
    title: 'プライバシーポリシー',
    description:
        'meap のプライバシーポリシー。お客様から取得する個人情報の項目、利用目的、第三者提供、安全管理措置、お問い合わせ窓口等について記載しています。',
};

interface LayoutProps {
    children: React.ReactNode;
}

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
