'use client';
import { Dialog } from '@/components/common';
import EditForm from './EditForm';
import React from 'react';
import { useShoppingStore } from '../../hooks';

const editMode: { [key: string]: string } = {
    create: '追加',
    update: '編集',
};

const ShoppingItemSettingDialog: React.FC = () => {
    const { dialogs, closeDialog } = useShoppingStore();
    const { isOpen, payload: editingItem } = dialogs.itemSetting;

    const type = React.useMemo(
        () => (editingItem ? 'update' : 'create'),
        [editingItem],
    );

    const handleClose = () => {
        closeDialog('itemSetting');
    };

    return (
        <Dialog
            title={`買い物アイテムを${editMode[type]}`}
            isOpen={isOpen}
            onClose={handleClose}>
            <EditForm
                item={editingItem}
                actionButtonText={editMode[type]}
                onBack={handleClose}
            />
        </Dialog>
    );
};

export default ShoppingItemSettingDialog;
