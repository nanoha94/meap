import { Loading } from '@/components/common';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';
import RecipeEditPage from '@/pages/recipe/edit/RecipeEditPage';
import {
    IGetGroupUserResponse,
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
        [
            IGetRecipeShowResponse,
            IGetIngredientCategoryIndexResponse,
            IGetGroupUserResponse,
        ]
    >([
        signal =>
            apiClient<IGetRecipeShowResponse>(`/recipes/${id}`, { signal }),
        signal =>
            apiClient<IGetIngredientCategoryIndexResponse>('/ingredient-categories', { signal },),
        signal =>
            apiClient<IGetGroupUserResponse>('/users', { signal },),

    ]);

    const [recipe, ingredientCategories, users] = data ?? [
        null,
        null,
        null,
    ];

    return (
        <>
            <RecipeEditPage
                fetchRecipe={recipe?.data}
                fetchIngredientCategories={ingredientCategories?.data}
                fetchUsers={users?.data}
                errorMessage={errorMessage}
            />

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