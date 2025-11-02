import React from 'react';
import { Dialog } from '@/components/common';
import EditForm from './EditForm';
import { DIALOG_NAME } from '@/constants';
import { useIngredientStore } from '@/models/ingredient/hooks';
import { INGREDIENT_SETTING_DIALOG_CONFIGS } from '../../constants';

const IngredientCategorySettingDialog = () => {
    const dialogName = DIALOG_NAME.INGREDIENT_CATEGORY_SETTING;
    const { dialogs, closeDialog } = useIngredientStore();
    const { isOpen } = dialogs[dialogName];

    return (
        <Dialog
            title={INGREDIENT_SETTING_DIALOG_CONFIGS[dialogName].title}
            isOpen={isOpen}
            onClose={() => closeDialog(dialogName)}>
            <EditForm onClose={() => closeDialog(dialogName)} />
        </Dialog>
    );
};

export default IngredientCategorySettingDialog;
