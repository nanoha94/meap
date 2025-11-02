'use client';
import { LoadingAnimation } from '@/components/common';
import { RecipeList } from '@/models/recipe/components';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { IGetRecipesResponse } from '@/types/api/recipe';
import React from 'react';

interface Props {
    fetchRecipes: IGetRecipesResponse;
}

const RecipeListPage = ({ fetchRecipes }: Props) => {
    const { setRecipes: setStoreRecipes, isLoadings } = useRecipeStore();
    const [isLoading, setIsLoading] = React.useState(false);

    React.useEffect(() => {
        setIsLoading(isLoadings.recipe || isLoadings.recipeCategory);
    }, [isLoadings]);

    React.useEffect(() => {
        if (fetchRecipes) {
            setStoreRecipes(fetchRecipes['data']);
        }
    }, [fetchRecipes]);
    return (
        <>
            {isLoading && <LoadingAnimation />}
            <div className="p-5 pb-[60px] md:px-10">
                {fetchRecipes['total'] > 0 ? (
                    <RecipeList />
                ) : (
                    <p>まだ料理/レシピが登録されていません。</p>
                )}
            </div>
        </>
    );
};

export default RecipeListPage;
