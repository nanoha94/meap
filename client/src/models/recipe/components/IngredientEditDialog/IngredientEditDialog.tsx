import { Dialog } from '@/components/common';
import {
    RECIPE_SETTING_DIALOG_CONFIGS,
    RECIPE_SETTING_DIALOG_NAME,
} from '../../constants';
import React from 'react';
import EditForm from './EditForm';
import { useRecipeStore } from '../../hooks/recipeStores';
import { IIngredient } from '@/types/api/recipe';

const IngredientEditDialog = () => {
    const { dialogs, closeDialog } = useRecipeStore();
    const {
        isOpen,
        payload: { item: editingItem, editMode, onAction },
    } = dialogs.ingredientSetting;

    const handleSetValue = (value: IIngredient) => {
        onAction(value);
        closeDialog('ingredientSetting');
    };

    return (
        <Dialog
            title={
                RECIPE_SETTING_DIALOG_CONFIGS[
                    RECIPE_SETTING_DIALOG_NAME.INGREDIENT
                ][editMode].title
            }
            isOpen={isOpen}
            onClose={() => closeDialog('ingredientSetting')}>
            <EditForm
                editingItem={editingItem}
                actionButtonText={
                    RECIPE_SETTING_DIALOG_CONFIGS[
                        RECIPE_SETTING_DIALOG_NAME.INGREDIENT
                    ][editMode].actionButtonText
                }
                onClose={() => closeDialog('ingredientSetting')}
                onAction={handleSetValue}
            />
        </Dialog>
    );
};

export default IngredientEditDialog;
