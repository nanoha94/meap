import { Header, Loading } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';
import RecipeEditPage from '@/pages/recipe/RecipeEditPage';
import {
    IGetIngredientCategoryIndexResponse,
    IGetRecipeShowResponse,
} from '@/types/api';
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
            apiClient<IGetRecipeShowResponse>(`/recipes/${id}`, { signal }),
        signal =>
            apiClient<IGetIngredientCategoryIndexResponse>(
                '/ingredient-categories',
                { signal },
            ),
    ]);

    const [recipe, ingredientCategories] = data ?? [null, null];

    return (
        <>
            <Header title="料理/レシピ編集" />
            <main>
                {errorMessage && (
                    <SnackbarHandler type="error" message={errorMessage} />
                )}
                <RecipeEditPage
                    fetchRecipe={recipe?.data}
                    fetchIngredientCategories={ingredientCategories?.data}
                />
            </main>
        </>
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
