import { Loading } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';
import RecipeEditPage from '@/pages/recipe/RecipeEditPage';
import {
    IGetGroupUserResponse,
    IGetIngredientCategoryIndexResponse,
    IGetRecipeShowResponse,
    IUser,
} from '@/types/api';
import { Suspense } from 'react';
import EditHeader from './EditHeader';

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
            apiClient<IGetIngredientCategoryIndexResponse>(
                '/ingredient-categories',
                { signal },
            ),
        signal => apiClient<IGetGroupUserResponse>('/users', { signal }),
    ]);

    const [recipe, ingredientCategories, users] = data ?? [
        null,
        null,
        { data: [], total: 0 },
    ];

    return (
        <>
            <EditHeader
                initialUserId={recipe?.data?.userId as string}
                users={users.data as IUser[]}
            />
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
