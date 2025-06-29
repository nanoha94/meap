'use client';
import { Dialog } from '@/components/common';
import EditForm from './EditForm';
import React from 'react';
import { useShoppingStore } from '../../hooks';

const textConfig: { [key: string]: { title: string; buttonText: string } } = {
    create: { title: '追加', buttonText: '追加' },
    update: { title: '編集', buttonText: '更新' },
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
            title={`買い物アイテムを${textConfig[type].title}`}
            isOpen={isOpen}
            onClose={handleClose}>
            <EditForm
                editingItem={editingItem}
                actionButtonText={textConfig[type].buttonText}
                onBack={handleClose}
            />
        </Dialog>
    );
};

export default ShoppingItemSettingDialog;
