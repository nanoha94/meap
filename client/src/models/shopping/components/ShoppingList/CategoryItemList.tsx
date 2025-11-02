import React from 'react';
import { Trash, X } from 'lucide-react';
import { useSortable } from '@dnd-kit/sortable';
import { IShoppingCategory, IShoppingItem } from '@/types/api';
import Sortable from '@/components/dnd/Sortable';
import ShoppingItemCard from './ShoppingItemCard';
import { ActionMenu, AlertDialog } from '@/components/common';
import { useShoppingHandlers, useShoppingItems } from '../../hooks';
import { SHOPPING_ALERT_DIALOG_CONFIGS } from '../../constants';
import { AlertDialogData } from '@/types/dialog';
import { ALERT_DIALOG_STATE_DEFAULT } from '@/constants/dialog';

interface Props {
    category: IShoppingCategory;
    items: IShoppingItem[];
}

const CategoryItemList: React.FC<Props> = ({ category, items }) => {
    const { handleUpdateItems } = useShoppingHandlers();
    const { deleteShoppingItems } = useShoppingItems();
    const { setNodeRef } = useSortable({ id: category.id });
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
        const config = SHOPPING_ALERT_DIALOG_CONFIGS.deleteItemsFromCategory(
            category.name,
        );
        setDeleteCheckDialog({
            isOpen: true,
            config,
            onCancel: closeDeleteCheckDialog,
            onAction: () => {
                // TODO: 削除可能なものが含まれているかチェックする
                deleteShoppingItems(
                    items
                        .filter(v => !v.isPinned && v.isChecked)
                        .map(v => v.id),
                );
                closeDeleteCheckDialog();
            },
            isLoading: false,
        });
    };

    return (
        <>
            <div className="flex flex-col gap-y-4">
                <div className="flex gap-x-4 items-center text-gray-main">
                    {category.name}
                    <ActionMenu
                        actionButtons={[
                            {
                                label: 'チェック解除',
                                icon: <X />,
                                onClick: () => {
                                    handleUpdateItems(
                                        items
                                            .filter(v => v.isChecked)
                                            .map(v => ({
                                                ...v,
                                                isChecked: false,
                                            })),
                                    );
                                },
                            },
                            {
                                label: 'チェック済みを削除',
                                icon: <Trash />,
                                onClick: openDeleteCheckDialog,
                            },
                        ]}
                        placement="top-left"
                        className="w-5 h-5"
                    />
                </div>
                <div className="grid grid-cols-[repeat(auto-fill,_minmax(320px,_1fr))] gap-4">
                    {items.length > 0 ? (
                        items.map(
                            (item, idx) =>
                                !!item && (
                                    <Sortable key={idx} id={item.id}>
                                        <ShoppingItemCard item={item} />
                                    </Sortable>
                                ),
                        )
                    ) : (
                        <div>登録されているアイテムはありません</div>
                    )}
                    <div ref={setNodeRef} />
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

export default CategoryItemList;
