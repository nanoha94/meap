'use client';
import { LoadingAnimation } from '@/components/common';
import { RecipeList } from '@/models/recipe/components';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { IRecipe } from '@/types/api/recipe';
import React from 'react';

interface Props {
    fetchRecipes: IRecipe[];
    total: number;
}

const RecipeListPage = ({ fetchRecipes = [], total = 0 }: Props) => {
    const { setRecipes: setStoreRecipes, isLoadings } = useRecipeStore();
    const [isLoading, setIsLoading] = React.useState(false);

    React.useEffect(() => {
        setIsLoading(isLoadings.recipe || isLoadings.recipeCategory);
    }, [isLoadings]);

    React.useEffect(() => {
        if (fetchRecipes) {
            setStoreRecipes(fetchRecipes);
        }
    }, [fetchRecipes]);
    return (
        <>
            {isLoading && <LoadingAnimation />}
            <div className="p-5 pb-[60px] md:px-10">
                {total > 0 ? (
                    <RecipeList />
                ) : (
                    <p>まだ料理/レシピが登録されていません。</p>
                )}
            </div>
        </>
    );
};

export default RecipeListPage;
