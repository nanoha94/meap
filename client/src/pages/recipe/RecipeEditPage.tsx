'use client';

import { LoadingAnimation } from '@/components/common';
import {
    IngredientEditDialog,
    SeasoningEditDialog,
} from '@/models/recipe/components';
import RecipeCategorySettingDialog from '@/models/recipe/components/RecipeCategorySettingDialog/RecipeCategorySettingDialog';
import RecipeEditForm from '@/models/recipe/components/RecipeEditForm/RecipeEditForm';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';

const RecipeEditPage = () => {
    const { isLoading } = useRecipeStore();
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
