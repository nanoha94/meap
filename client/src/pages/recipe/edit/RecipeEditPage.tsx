'use client';
import React from 'react';
import { IRecipe, IIngredientCategory, IUser } from '@/types/api';
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
import type { RecipeEditFormRef } from '@/models/recipe/components/RecipeEditForm/RecipeEditForm';
import { useGlobalStore } from '@/stores';
import RecipeEditPageHeader from './RecipeEditPageHeader';
import { useSnackbars } from '@/hooks/useSnackbars';

interface Props {
    fetchRecipe?: IRecipe;
    fetchIngredientCategories?: IIngredientCategory[];
    fetchUsers?: IUser[];
    errorMessage?: string;
}

const RecipeEditPage = ({
    fetchRecipe,
    fetchIngredientCategories,
    fetchUsers,
    errorMessage,
}: Props) => {
    const {
        categories: ingredientCategories,
        setCategories: setStoreCategories,
        isLoadings: isLoadingCategories,
    } = useIngredientStore();
    const { addSnackbar } = useSnackbars();
    const { isLoadings: isLoadingRecipe } = useRecipeStore();
    const { setIsLoading } = useGlobalStore();
    const formRef = React.useRef<RecipeEditFormRef>(null);
    const [ownerUserId, setOwnerUserId] = React.useState<string>(
        (fetchRecipe as IRecipe)?.ownerUserId ?? '',
    );

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
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage]);

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
            <RecipeEditPageHeader
                ownerUserId={ownerUserId}
                users={fetchUsers ?? []}
                onChangeOwnerUserId={(userId) => {
                    setOwnerUserId(userId);
                }}
                onClickSaveButton={() => {
                    formRef.current?.submit();
                }}
            />
            <main>
                <RecipeEditForm ref={formRef} fetchRecipe={fetchRecipe} ownerUserId={ownerUserId} />

                {/* 食材編集ダイアログ */}
                <IngredientEditDialog />
                {/* 食材カテゴリー設定ダイアログ */}
                <IngredientCategorySettingDialog />
                {/* レシピカテゴリー設定ダイアログ */}
                <RecipeCategorySettingDialog />
            </main>
        </>
    );
};

export default RecipeEditPage;
