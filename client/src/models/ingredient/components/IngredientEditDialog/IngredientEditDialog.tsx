import { Dialog } from '@/components/common';
import React from 'react';
import EditForm from './EditForm';
import { DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';
import { IIngredientItem } from '@/types/api/ingredient';
import { useIngredientStore } from '@/models/ingredient/hooks';

const IngredientEditDialog = () => {
    const dialogName = DIALOG_NAME.INGREDIENT_ADD_EDIT;
    const { dialogs, closeDialog } = useIngredientStore();
    const {
        isOpen,
        payload: { item: editingItem, editMode, onAction },
    } = dialogs[dialogName];

    const { categories } = useIngredientStore();
    const category = React.useMemo(
        () =>
            categories.find(
                category => category.id === editingItem?.categoryId,
            ),
        [categories, editingItem],
    );

    const handleSetValue = (value: IIngredientItem) => {
        onAction(value);
        closeDialog(dialogName);
    };

    // タイトルとボタンテキストを直接定義
    const title =
        editMode === DIALOG_EDIT_MODE.CREATE
            ? `${category?.name ?? '材料'}を追加`
            : `${category?.name ?? '材料'}を編集`;
    const actionButtonText =
        editMode === DIALOG_EDIT_MODE.CREATE ? '追加' : '保存';

    return (
        <Dialog
            title={title}
            isOpen={isOpen}
            onClose={() => closeDialog(dialogName)}>
            <EditForm
                editingItem={editingItem}
                actionButtonText={actionButtonText}
                onClose={() => closeDialog(dialogName)}
                onAction={handleSetValue}
            />
        </Dialog>
    );
};

export default IngredientEditDialog;
