'use client';
import { RecipeList } from '@/models/recipe/components';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
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
