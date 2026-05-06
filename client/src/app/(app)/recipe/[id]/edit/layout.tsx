import React from 'react';
import type { Metadata } from 'next';

import { fetchData } from '@/lib/apiClient';
import { IGetRecipeShowResponse } from '@/types';

interface LayoutProps {
    children: React.ReactNode;
    params: Promise<{ id: string }>;
}

export const generateMetadata = async ({ params }: LayoutProps): Promise<Metadata> => {
    const { id } = await params;
    const { data: recipe } = await fetchData<IGetRecipeShowResponse>(
        `/recipes/${id}`,
        { suppressNotFoundLog: true },
    );

    const name = recipe?.data?.name;
    return {
        title: name ? `${name}の編集` : 'レシピ編集',
    };
};

const Layout = ({ children }: LayoutProps) => {
    return children;
};

export default Layout;
