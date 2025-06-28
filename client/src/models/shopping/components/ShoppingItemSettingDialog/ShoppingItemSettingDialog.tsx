'use client';
import { Dialog } from '@/components/common';
import { IShoppingItem } from '@/types/api';
import EditForm from './EditForm';
import React from 'react';
import { useShoppingDialogs } from '../../hooks';

interface Props {
    item?: IShoppingItem | undefined;
}

const editMode: { [key: string]: string } = {
    create: '追加',
    update: '編集',
};

const ShoppingItemSettingDialog: React.FC<Props> = ({ item = undefined }) => {
    const { dialogs, closeDialog } = useShoppingDialogs();

    const type = React.useMemo(
        () => (item !== undefined ? 'update' : 'create'),
        [item],
    );

    const handleClose = () => {
        closeDialog('itemSetting');
    };
    return (
        <Dialog
            title={`買い物アイテムを${editMode[type]}`}
            isOpen={dialogs.itemSetting}
            onClose={handleClose}>
            <EditForm
                item={item}
                actionButtonText={editMode[type]}
                onBack={handleClose}
            />
        </Dialog>
    );
};

export default ShoppingItemSettingDialog;
