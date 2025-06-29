'use client';
import { Dialog } from '@/components/common';
import EditForm from './EditForm';
import { useShoppingStore } from '../../hooks';
import React from 'react';

const ShoppingCategorySettingDialog = () => {
    const { dialogs, closeDialog } = useShoppingStore();
    const { isOpen } = dialogs.categorySetting;

    const handleClose = () => {
        closeDialog('categorySetting');
    };

    return (
        <Dialog
            title="買い物カテゴリ―設定"
            isOpen={isOpen}
            onClose={handleClose}>
            <EditForm onBack={handleClose} />
        </Dialog>
    );
};

export default ShoppingCategorySettingDialog;
