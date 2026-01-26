'use client';
import React from 'react';
import { IRecipe } from '@/types/api';
import {
    RecipeEditForm,
} from '@/models/recipe/components';
import type { RecipeEditFormRef } from '@/models/recipe/components/RecipeEditForm/RecipeEditForm';
import RecipeEditPageHeader from './RecipeEditPageHeader';
import { useSnackbars } from '@/hooks/useSnackbars';

interface Props {
    fetchRecipe?: IRecipe;
    errorMessage?: string;
}

const RecipeEditPage = ({
    fetchRecipe,
    errorMessage,
}: Props) => {
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

    return (
        <>
            <RecipeEditPageHeader
                ownerUserId={ownerUserId}
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
