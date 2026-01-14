'use client';
import { RecipeList } from '@/models/recipe/components';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { useGlobalStore } from '@/stores';
import { IRecipe } from '@/types/api';
import React from 'react';

interface Props {
    fetchRecipes: IRecipe[];
    total: number;
}

const RecipeListPage = ({ fetchRecipes = [], total = 0 }: Props) => {
    const { setRecipes: setStoreRecipes, isLoadings } = useRecipeStore();
    const { setIsLoading } = useGlobalStore();

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
