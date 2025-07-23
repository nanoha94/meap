'use client';

import {
    IngredientEditDialog,
    SeasoningEditDialog,
} from '@/models/recipe/components';
import RecipeEditForm from '@/models/recipe/components/RecipeEditForm/RecipeEditForm';

const RecipeEditPage = () => {
    return (
        <>
            <RecipeEditForm />
            <IngredientEditDialog />
            <SeasoningEditDialog />
        </>
    );
};

export default RecipeEditPage;
