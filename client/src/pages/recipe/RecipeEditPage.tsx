'use client';

import { LoadingAnimation } from '@/components/common';
import {
    IngredientEditDialog,
    SeasoningEditDialog,
} from '@/models/recipe/components';
import RecipeCategorySettingDialog from '@/models/recipe/components/RecipeCategorySettingDialog/RecipeCategorySettingDialog';
import RecipeEditForm from '@/models/recipe/components/RecipeEditForm/RecipeEditForm';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import React from 'react';

const RecipeEditPage = () => {
    const { isLoadings } = useRecipeStore();
    const [isLoading, setIsLoading] = React.useState(false);

    React.useEffect(() => {
        setIsLoading(isLoadings.recipe || isLoadings.recipeCategory);
    }, [isLoadings]);

    return (
        <>
            {isLoading && <LoadingAnimation />}
            <RecipeEditForm />
            <IngredientEditDialog />
            <SeasoningEditDialog />
            <RecipeCategorySettingDialog />
        </>
    );
};

export default RecipeEditPage;
