import { Dialog } from '@/components/common';
import React from 'react';
import EditForm from './EditForm';
import { DIALOG_NAME } from '@/constants';
import { IIngredient } from '@/types/api/ingredient';
import { useIngredientStore } from '@/models/ingredient/hooks';
import { INGREDIENT_SETTING_DIALOG_CONFIGS } from '@/models/ingredient/constants';

const IngredientEditDialog = () => {
    const dialogName = DIALOG_NAME.INGREDIENT_ADD_EDIT;
    const { dialogs, closeDialog } = useIngredientStore();
    const {
        isOpen,
        payload: { item: editingItem, editMode, onAction },
    } = dialogs[dialogName];
    const dialogConfig =
        INGREDIENT_SETTING_DIALOG_CONFIGS[dialogName][editMode];

    const handleSetValue = (value: IIngredient) => {
        onAction(value);
        closeDialog(dialogName);
    };

    return (
        <Dialog
            title={dialogConfig.title}
            isOpen={isOpen}
            onClose={() => closeDialog(dialogName)}>
            <EditForm
                editingItem={editingItem}
                actionButtonText={dialogConfig.actionButtonText}
                onClose={() => closeDialog(dialogName)}
                onAction={handleSetValue}
            />
        </Dialog>
    );
};

export default IngredientEditDialog;
