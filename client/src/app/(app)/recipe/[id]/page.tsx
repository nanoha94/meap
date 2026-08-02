import React from 'react';
import { Suspense } from 'react';
import { notFound } from 'next/navigation';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import { createRecipeDetailMetadata } from '@/lib/recipeMetadata';
import RecipeDetailPage from '@/pages/recipe/detail/RecipeDetailPage';
import { IGetRecipeShowResponse } from '@/types';
import { Metadata } from 'next';

interface Props {
    params: Promise<{ id: string }>;
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
    const { id } = await params;
    return createRecipeDetailMetadata(id);
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
