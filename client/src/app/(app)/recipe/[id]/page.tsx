import React from 'react';
import { Suspense } from 'react';
import type { Metadata } from 'next';
import { notFound } from 'next/navigation';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import RecipeDetailPage from '@/pages/recipe/detail/RecipeDetailPage';
import { IGetRecipeShowResponse } from '@/types';

interface Props {
    params: Promise<{ id: string }>;
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
    const { id } = await params;
    const { data: recipe } = await fetchData<IGetRecipeShowResponse>(
        `/recipes/${id}`,
        { suppressNotFoundLog: true },
    );

    return {
        title: recipe?.data?.name ?? 'レシピ',
    };
}

interface PageWithDataProps {
    id: string;
}
const PageWithData = async ({ id }: PageWithDataProps) => {
    const { data: recipe, errorMessage } = await fetchData<IGetRecipeShowResponse>(`/recipes/${id}`);

    if (errorMessage || !recipe) {
        notFound();
    }

    return (
        <RecipeDetailPage
            fetchedRecipe={recipe.data}
            errorMessage={errorMessage}
        />
    );
};

const Page = async ({ params }: Props) => {
    const { id } = await params;
    return (
        <Suspense fallback={<Loading />}>
            <PageWithData id={id} />
        </Suspense>
    );
};

export default Page;
