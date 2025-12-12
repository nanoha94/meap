import { Header, Loading } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { fetchData } from '@/lib/apiClient';
import RecipeListPage from '@/pages/recipe/RecipeListPage';
import { IGetRecipeIndexResponse } from '@/types/api';
import { Suspense } from 'react';
import { CirclePlus } from 'lucide-react';
import { HeaderLinkTextButton } from '@/components/common/HeaderTextButtons';

const RecipePageWithData = async () => {
    const { data: recipes, errorMessage } =
        await fetchData<IGetRecipeIndexResponse>('/recipes');
    return (
        <>
            <Header title="料理/レシピ一覧">
                <div className="hidden md:flex">
                    <HeaderLinkTextButton
                        href="/recipe/new"
                        colorVariant="secondary">
                        <CirclePlus size={20} />
                        料理/レシピを追加
                    </HeaderLinkTextButton>
                </div>
            </Header>
            <main>
                {errorMessage && (
                    <SnackbarHandler type="error" message={errorMessage} />
                )}
                <RecipeListPage
                    fetchRecipes={recipes?.data ?? []}
                    total={recipes?.total ?? 0}
                />
            </main>
        </>
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
