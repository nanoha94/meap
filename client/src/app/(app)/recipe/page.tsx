import { Loading } from '@/components/common';
import { fetchData } from '@/lib/apiClient';
import RecipeListPage from '@/pages/recipe/list/RecipeListPage';
import { IGetRecipeIndexResponse } from '@/types/api';
import { Suspense } from 'react';

const RecipePageWithData = async () => {
    const { data: recipes, errorMessage } =
        await fetchData<IGetRecipeIndexResponse>('/recipes');
    return (
        <RecipeListPage
            fetchRecipes={recipes?.data ?? []}
            total={recipes?.total ?? 0}
            errorMessage={errorMessage}
        />
    );
};

const Page = () => {
    return (
        <Suspense fallback={<Loading />}>
            <RecipePageWithData />
        </Suspense>
    );
};

export default Page;
