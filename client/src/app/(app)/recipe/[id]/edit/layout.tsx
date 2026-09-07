import React from 'react';
import type { Metadata } from 'next';

import { createRecipeEditMetadata } from '@/lib/recipeMetadata';

interface LayoutProps {
    children: React.ReactNode;
    params: Promise<{ id: string }>;
}

export const generateMetadata = async ({
    params,
}: LayoutProps): Promise<Metadata> => {
    const { id } = await params;

    return createRecipeEditMetadata(id);
};

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
