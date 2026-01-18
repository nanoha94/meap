import { Loading } from '@/components/common';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';
import {
    IGetRecipeShowResponse,
    IGetIngredientCategoryIndexResponse,
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
    const { data, errorMessage } = await fetchDataParallel<
        [IGetRecipeShowResponse, IGetIngredientCategoryIndexResponse]
    >([
        signal =>
            apiClient<IGetRecipeShowResponse>(`/recipes/${id}`, {
                signal,
            }),
        signal =>
            apiClient<IGetIngredientCategoryIndexResponse>(
                '/ingredient-categories',
                { signal },
            ),
    ]);

    if (errorMessage || !data) {
        notFound();
    }

    const [recipe, ingredientCategories] = data;

    return (
        <RecipeDetailPage
            fetchRecipe={recipe.data}
            fetchIngredientCategories={ingredientCategories.data}
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
