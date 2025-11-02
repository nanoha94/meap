import React from 'react';
import { Dialog } from '@/components/common';
import EditForm from './EditForm';
import { RECIPE_SETTING_DIALOG_CONFIGS } from '../../constants';
import { DIALOG_NAME } from '@/constants';
import { useIngredientStore } from '@/models/ingredient/hooks';

const IngredientCategorySettingDialog = () => {
    const { dialogs, closeDialog } = useIngredientStore();
    const { isOpen } = dialogs[DIALOG_NAME.INGREDIENT_CATEGORY_SETTING];

    const dialogName = DIALOG_NAME.INGREDIENT_CATEGORY_SETTING;

    return (
        <Dialog
            title={RECIPE_SETTING_DIALOG_CONFIGS[dialogName].title}
            isOpen={isOpen}
            onClose={() => closeDialog(dialogName)}>
            <EditForm onClose={() => closeDialog(dialogName)} />
        </Dialog>
    );
};

export default IngredientCategorySettingDialog;
