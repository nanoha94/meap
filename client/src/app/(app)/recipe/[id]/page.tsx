import { Header, Loading } from '@/components/common';
import { SnackbarHandler } from '@/components/handlers';
import { Suspense } from 'react';
import { HeaderRecipeDeleteButton } from '@/models/recipe/components';
import { Pencil } from 'lucide-react';
import { HeaderLinkTextButton } from '@/components/common/HeaderTextButtons';
import { apiClient, fetchDataParallel } from '@/lib/apiClient';
import {
    IGetRecipeShowResponse,
    IGetIngredientCategoryIndexResponse,
} from '@/types/api';
import RecipeDetailPage from '@/pages/recipe/RecipeDetailPage';
import { notFound } from 'next/navigation';

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

    if (errorMessage || !data) {
        notFound();
    }

    const [recipe, ingredientCategories] = data;

    return (
        <>
            <Header title="料理/レシピ">
                <div className="flex items-center gap-x-4">
                    <HeaderRecipeDeleteButton id={id} name={recipe.data.name} />
                    <HeaderLinkTextButton
                        href={`/recipe/${id}/edit`}
                        colorVariant="secondary">
                        <Pencil size={20} />
                        編集
                    </HeaderLinkTextButton>
                </div>
            </Header>
            <main>
                {errorMessage && (
                    <SnackbarHandler type="error" message={errorMessage} />
                )}
                <RecipeDetailPage
                    fetchRecipe={recipe.data}
                    fetchIngredientCategories={ingredientCategories.data}
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
