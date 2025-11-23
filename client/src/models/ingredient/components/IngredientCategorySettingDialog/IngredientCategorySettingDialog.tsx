import React from 'react';
import { Dialog } from '@/components/common';
import EditForm from './EditForm';
import { DIALOG_NAME } from '@/constants';
import { useIngredientStore } from '@/models/ingredient/hooks';

const IngredientCategorySettingDialog = () => {
    const dialogName = DIALOG_NAME.INGREDIENT_CATEGORY_SETTING;
    const { dialogs, closeDialog } = useIngredientStore();
    const { isOpen } = dialogs[dialogName];

    const handleClose = () => {
        closeDialog(dialogName);
    };

    return (
        <Dialog
            title="材料カテゴリーを設定"
            isOpen={isOpen}
            onClose={handleClose}>
            <EditForm onClose={handleClose} />
        </Dialog>
    );
};

export default IngredientCategorySettingDialog;
