'use client';

import { LoadingAnimation } from '@/components/common';
import { IngredientEditDialog } from '@/models/recipe/components';
import RecipeCategorySettingDialog from '@/models/recipe/components/RecipeCategorySettingDialog/RecipeCategorySettingDialog';
import RecipeEditForm from '@/models/recipe/components/RecipeEditForm/RecipeEditForm';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { IRecipe } from '@/types/api/recipe';
import React from 'react';

interface Props {
    recipe?: IRecipe | null;
}

const RecipeEditPage = ({ recipe = null }: Props) => {
    const { isLoadings } = useRecipeStore();
    const [isLoading, setIsLoading] = React.useState(false);

    React.useEffect(() => {
        setIsLoading(isLoadings.recipe || isLoadings.recipeCategory);
    }, [isLoadings]);

    return (
        <>
            {isLoading && <LoadingAnimation />}
            <RecipeEditForm recipe={recipe} />
            <IngredientEditDialog />
            <RecipeCategorySettingDialog />
        </>
    );
};

export default RecipeEditPage;
