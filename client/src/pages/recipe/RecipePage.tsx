'use client';
import { LoadingAnimation } from '@/components/common';
import { RecipeList } from '@/models/recipe/components';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { IGetRecipesResponse } from '@/types/api/recipe';
import React from 'react';

interface Props {
    fetchRecipes: IGetRecipesResponse['data'];
}

const RecipePage = ({ fetchRecipes }: Props) => {
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
            <RecipeList />
        </>
    );
};

export default RecipePage;
