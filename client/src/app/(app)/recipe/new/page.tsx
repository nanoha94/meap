import { Header, Loading } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { fetchData } from '@/lib/apiClient';
import RecipeDetailPage from '@/pages/recipe/RecipeDetailPage';
import { IGetIngredientCategoryIndexResponse } from '@/types/api';
import { Suspense } from 'react';

async function RecipeNewPageWithData() {
    const { data: ingredientCategories, errorMessage } =
        await fetchData<IGetIngredientCategoryIndexResponse>(
            '/ingredient-categories',
        );

    return (
        <>
            {errorMessage && (
                <SnackbarHandler type="error" message={errorMessage} />
            )}
            <Header title="料理/レシピ追加" />
            <main>
                <RecipeDetailPage
                    fetchIngredientCategories={ingredientCategories?.data}
                />
            </main>
        </>
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
