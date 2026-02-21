import React from 'react';
import { Suspense } from 'react';
import RecipeListPage from '@/pages/recipe/list/RecipeListPage';

import { Loading } from '@/components';
import { fetchData } from '@/lib/apiClient';
import { IGetRecipeIndexResponse } from '@/types';

const RecipePageWithData = async () => {
    const { data: recipes, errorMessage } = await fetchData<IGetRecipeIndexResponse>('/recipes');

    return (
        <RecipeListPage
            fetchedRecipes={recipes?.data ?? []}
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
