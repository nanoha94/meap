'use client';
import React from 'react';
import { IRecipe, IIngredientCategory, IUser } from '@/types/api';
import { useIngredientStore } from '@/models/ingredient/hooks';
import {
    RecipeEditForm,
} from '@/models/recipe/components';
import type { RecipeEditFormRef } from '@/models/recipe/components/RecipeEditForm/RecipeEditForm';
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
    } = useIngredientStore();
    const { addSnackbar } = useSnackbars();
    const formRef = React.useRef<RecipeEditFormRef>(null);
    const [ownerUserId, setOwnerUserId] = React.useState<string>(
        (fetchRecipe as IRecipe)?.ownerUserId ?? '',
    );

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
                fetchRecipe={fetchRecipe}
                onChangeOwnerUserId={(userId) => {
                    setOwnerUserId(userId);
                }}
                onClickSaveButton={() => {
                    formRef.current?.submit();
                }}
            />
            <main>
                <RecipeEditForm ref={formRef} fetchRecipe={fetchRecipe} ownerUserId={ownerUserId} />
            </main>
        </>
    );
};

export default RecipeEditPage;
