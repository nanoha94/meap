'use client';
import React from 'react';
import { LoadingAnimation } from '@/components/common';
import IngredientCategorySettingDialog from '@/models/ingredient/components/IngredientCategorySettingDialog/IngredientCategorySettingDialog';
import { IngredientEditDialog } from '@/models/recipe/components';
import RecipeCategorySettingDialog from '@/models/recipe/components/RecipeCategorySettingDialog/RecipeCategorySettingDialog';
import RecipeEditForm from '@/models/recipe/components/RecipeEditForm/RecipeEditForm';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { IRecipe } from '@/types/api/recipe';

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

            {/* 食材編集ダイアログ */}
            <IngredientEditDialog />
            {/* 食材カテゴリー設定ダイアログ */}
            <IngredientCategorySettingDialog />
            {/* レシピカテゴリー設定ダイアログ */}
            <RecipeCategorySettingDialog />
        </>
    );
};

export default RecipeEditPage;
