'use client';
import React from 'react';
import { LoadingAnimation } from '@/components/common';
import { IRecipe, IIngredientCategory } from '@/types/api';
import { useIngredientStore } from '@/models/ingredient/hooks';
import { useRecipeStore } from '@/models/recipe/hooks';
import {
    IngredientCategorySettingDialog,
    IngredientEditDialog,
} from '@/models/ingredient/components';
import {
    RecipeCategorySettingDialog,
    RecipeEditForm,
} from '@/models/recipe/components';

interface Props {
    fetchRecipe?: IRecipe;
    fetchIngredientCategories?: IIngredientCategory[];
}

const RecipeDetailPage = ({
    fetchRecipe,
    fetchIngredientCategories,
}: Props) => {
    const {
        categories: ingredientCategories,
        setCategories: setStoreCategories,
        isLoadings: isLoadingCategories,
    } = useIngredientStore();
    const { isLoadings: isLoadingRecipe } = useRecipeStore();
    const [isLoading, setIsLoading] = React.useState(false);

    /**
     * ローディング状態を更新
     * @returns void
     */
    React.useEffect(() => {
        setIsLoading(
            isLoadingRecipe.recipe ||
                isLoadingRecipe.recipeCategory ||
                isLoadingCategories.ingredientCategory,
        );
    }, [isLoadingRecipe, isLoadingCategories]);

    /**
     * 食材カテゴリーをストアにセット
     * @param fetchCategories 食材カテゴリー
     * @returns void
     */
    React.useEffect(() => {
        if (fetchIngredientCategories && ingredientCategories.length <= 0) {
            setStoreCategories(fetchIngredientCategories);
        }
    }, [fetchIngredientCategories]);

    return (
        <>
            {isLoading && <LoadingAnimation />}
            <RecipeEditForm fetchRecipe={fetchRecipe} />

            {/* 食材編集ダイアログ */}
            <IngredientEditDialog />
            {/* 食材カテゴリー設定ダイアログ */}
            <IngredientCategorySettingDialog />
            {/* レシピカテゴリー設定ダイアログ */}
            <RecipeCategorySettingDialog />
        </>
    );
};

export default RecipeDetailPage;
