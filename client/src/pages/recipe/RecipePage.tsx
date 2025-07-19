'use client';
import { useRecipeStore } from '@/models/recipe/RecipeList/hooks/recipeStores';
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
    return <div>Enter</div>;
};

export default RecipePage;
