import { Loading } from '@/components/common';
import { fetchData } from '@/lib/apiClient';
import {
    IGetRecipeShowResponse,
} from '@/types/api';
import { notFound } from 'next/navigation';
import RecipeDetailPage from '@/pages/recipe/detail/RecipeDetailPage';
import { Suspense } from 'react';

interface Props {
    params: Promise<{ id: string }>;
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
            fetchRecipe={recipe.data}
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
