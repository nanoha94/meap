import { Loading } from '@/components/common';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';
import RecipeEditPage from '@/pages/recipe/edit/RecipeEditPage';
import {
    IGetGroupUserResponse,
    IGetIngredientCategoryIndexResponse,
} from '@/types/api';
import { Suspense } from 'react';

async function RecipeNewPageWithData() {
    const { data, errorMessage } = await fetchDataParallel<
        [IGetIngredientCategoryIndexResponse, IGetGroupUserResponse]
    >([
        signal =>
            apiClient<IGetIngredientCategoryIndexResponse>('/ingredient-categories', { signal },),
        signal =>
            apiClient<IGetGroupUserResponse>('/users', { signal }),
    ]);

    const [ingredientCategories, users] = data ?? [null, null];

    return (
        <RecipeEditPage
            fetchIngredientCategories={ingredientCategories?.data}
            fetchUsers={users?.data}
            errorMessage={errorMessage}
        />
    );
}

const Page = () => {
    return (
        <Suspense fallback={<Loading />}>
            <RecipeNewPageWithData />
        </Suspense>
    );
};

export default Page;
