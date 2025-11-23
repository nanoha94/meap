import { Header } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { TIMEOUT_MS } from '@/constants';
import RecipeEditPage from '@/pages/recipe/RecipeEditPage';
import { IGetIngredientCategoryIndexResponse } from '@/types/api/ingredient';
import Loading from '../../loading';
import { Suspense } from 'react';
import { apiClient } from '@/lib/apiClient';

async function RecipeNewPageWithData() {
    let ingredientCategories: IGetIngredientCategoryIndexResponse | null = null;
    let errorMessage: string = '';

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

        ingredientCategories =
            await apiClient<IGetIngredientCategoryIndexResponse>(
                '/ingredient-categories',
                {
                    signal: controller.signal,
                },
            );
        clearTimeout(timeoutId);
    } catch (error) {
        console.error(error);
        // エラーオブジェクトから安全に文字列を抽出
        errorMessage =
            error instanceof Error
                ? error.message
                : 'データの取得に失敗しました';
    }

    return (
        <>
            {errorMessage && (
                <SnackbarHandler type="error" message={errorMessage} />
            )}
            <Header title="料理/レシピ追加" />
            <main>
                <RecipeEditPage
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
