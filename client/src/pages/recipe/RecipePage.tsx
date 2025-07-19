'use client';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import RecipeList from '@/models/recipe/components/RecipeList/RecipeList';
import { IGetRecipesResponse } from '@/types/api/recipe';
import React from 'react';

interface Props {
    fetchRecipes: IGetRecipesResponse['data'];
}

const RecipePage = ({ fetchRecipes }: Props) => {
    const { setRecipes: setStoreRecipes } = useRecipeStore();

    React.useEffect(() => {
        if (fetchRecipes) {
            setStoreRecipes(fetchRecipes);
        }
    }, [fetchRecipes]);
    return (
        <div>
            <RecipeList />
        </div>
    );
};

export default RecipePage;
