'use client';
import HeaderTextButton from '@/components/common/HeaderTextButtons/HeaderTextButton';
import { Trash } from 'lucide-react';
import React from 'react';
import { useRecipes } from '../../hooks/useRecipe';
import { AlertDialog } from '@/components/common';
import { AlertDialogData } from '@/types/dialog';
import { ALERT_DIALOG_STATE_DEFAULT } from '@/constants';

interface Props {
    id: string;
    name: string;
}

const HeaderRecipeDeleteButton = ({ id, name }: Props) => {
    const { deleteRecipe } = useRecipes();
    const [deleteCheckDialog, setDeleteCheckDialog] =
        React.useState<AlertDialogData>(ALERT_DIALOG_STATE_DEFAULT);

    /**
     * 削除確認ダイアログを閉じる
     */
    const closeDeleteCheckDialog = () => {
        setDeleteCheckDialog(ALERT_DIALOG_STATE_DEFAULT);
    };

    /**
     * 削除確認ダイアログを開く
     * @param config ダイアログの設定
     */
    const openDeleteCheckDialog = () => {
        setDeleteCheckDialog({
            isOpen: true,
            config: {
                title: '削除',
                message: [`${name}を削除しますか？`],
                alertMessage: '',
                actionButtonText: '削除',
            },
            onCancel: closeDeleteCheckDialog,
            onAction: () => {
                closeDeleteCheckDialog();
                deleteRecipe(id, name);
            },
            isLoading: false,
        });
    };

    return (
        <>
            <HeaderTextButton
                colorVariant="alert"
                onClick={openDeleteCheckDialog}>
                <Trash size={20} strokeWidth={2} />
                削除
            </HeaderTextButton>
            <AlertDialog
                isOpen={deleteCheckDialog.isOpen}
                config={deleteCheckDialog.config}
                onCancel={deleteCheckDialog.onCancel}
                onAction={deleteCheckDialog.onAction}
                isLoading={deleteCheckDialog.isLoading}
            />
        </>
    );
};

export default HeaderRecipeDeleteButton;
