'use client';

import { IngredientEditDialog } from '@/models/recipe/components';
import RecipeEditForm from '@/models/recipe/components/RecipeEditForm/RecipeEditForm';

const RecipeEditPage = () => {
    return (
        <>
            <RecipeEditForm />
            <IngredientEditDialog />
        </>
    );
};

export default RecipeEditPage;
