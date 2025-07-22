'use client';
import React from 'react';
import { ActionMenu, AlertDialog } from '@/components/common';
import { colors } from '@/constants/colors';
import { Check, GripVertical, Pencil, Pin, PinOff, Trash } from 'lucide-react';
import { IShoppingItem } from '@/types/api';
import {
    useShoppingHandlers,
    useShoppingItems,
    useShoppingStore,
} from '../../hooks';
import {
    SHOPPING_ITEM_EDIT_MODE,
    SHOPPING_ALERT_DIALOG_CONFIGS,
} from '../../constants';
import { AlertDialogData } from '@/types/dialog';
import { ALERT_DIALOG_STATE_DEFAULT } from '@/constants/dialog';

interface Props {
    item: IShoppingItem;
}

const ShoppingItemCard = ({ item }: Props) => {
    const { id, name, isPinned = false, isChecked = false } = item;
    const { handleUpdateItem } = useShoppingHandlers();
    const { deleteShoppingItems } = useShoppingItems();
    const { openDialog } = useShoppingStore();
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
        const config = SHOPPING_ALERT_DIALOG_CONFIGS.deleteItem(name);
        setDeleteCheckDialog({
            isOpen: true,
            config,
            onCancel: closeDeleteCheckDialog,
            onAction: () => {
                deleteShoppingItems([id]);
                closeDeleteCheckDialog();
            },
            isLoading: false,
        });
    };

    const actionButtons = [
        {
            label: '編集する',
            icon: <Pencil />,
            onClick: () => {
                openDialog('itemSetting', {
                    item,
                    editMode: SHOPPING_ITEM_EDIT_MODE.UPDATE,
                });
            },
        },
        {
            label: '削除する',
            icon: <Trash />,
            onClick: openDeleteCheckDialog,
        },
        {
            label: isPinned ? '固定解除する' : '固定する',
            icon: isPinned ? <PinOff /> : <Pin />,
            onClick: () =>
                handleUpdateItem({
                    ...item,
                    isPinned: !isPinned,
                }),
        },
    ];

    return (
        <>
            <div className="relative py-3 px-1 w-full text-left flex items-center justify-between rounded bg-white shadow-card">
                <div className="w-full flex items-center gap-x-4">
                    {isPinned && (
                        <div className="absolute -top-3 -left-3 bg-primary-main rounded-full p-1">
                            <Pin color={colors.white} size={20} />
                        </div>
                    )}
                    <GripVertical color={colors.gray.main} />
                    <div className="flex-1">
                        <input
                            type="checkbox"
                            id={`checkbox-${id}`}
                            checked={isChecked}
                            onChange={() => {
                                handleUpdateItem({
                                    ...item,
                                    isChecked: !isChecked,
                                });
                            }}
                            className="hidden"
                        />
                        <label
                            htmlFor={`checkbox-${id}`}
                            className="relative pl-7 w-full h-full whitespace-nowrap cursor-pointer">
                            <div
                                className={`absolute top-1/2 -translate-y-1/2 left-0 w-5 h-5 rounded border-[1.5px] transition-colors ${
                                    isChecked
                                        ? 'bg-primary-main border-[transparent]'
                                        : 'bg-white border-gray-main'
                                }`}>
                                {isChecked && (
                                    <Check
                                        strokeWidth={3.5}
                                        color={colors.white}
                                        size={20}
                                        className="absolute top-1/2 -translate-y-1/2 left-0"
                                    />
                                )}
                            </div>
                            {name}
                        </label>
                    </div>
                    <ActionMenu
                        actionButtons={actionButtons}
                        placement="top-right"
                    />
                </div>
            </div>

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

export default ShoppingItemCard;
