'use client';
import { Dialog } from '@/components/common';
import EditForm from './EditForm';
import React from 'react';
import { useShoppingStore } from '../../hooks';
import { SHOPPING_ITEM_SETTING_DIALOG_CONFIGS } from '../../constants/dialogs';

const ShoppingItemSettingDialog: React.FC = () => {
    const { dialogs, closeDialog } = useShoppingStore();
    const {
        isOpen,
        payload: { editMode },
    } = dialogs.itemSetting;

    const handleClose = () => {
        closeDialog('itemSetting');
    };

    return (
        <Dialog
            title={SHOPPING_ITEM_SETTING_DIALOG_CONFIGS[editMode].title}
            isOpen={isOpen}
            onClose={handleClose}>
            <EditForm onClose={handleClose} />
        </Dialog>
    );
};

export default ShoppingItemSettingDialog;
