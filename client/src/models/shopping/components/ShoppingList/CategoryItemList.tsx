import React from 'react';
import { Trash, X } from 'lucide-react';
import { rectSortingStrategy, SortableContext } from '@dnd-kit/sortable';
import { useDroppable } from '@dnd-kit/core';
import Sortable from '@/components/dnd/Sortable';
import ShoppingItemCard from './ShoppingItemCard';
import { MenuButton, AlertDialog } from '@/components/common';
import { useShoppingItemApi, useShoppingStore } from '../../hooks';
import { SHOPPING_ALERT_DIALOG_CONFIGS } from '../../constants';
import { AlertDialogData } from '@/types';
import { ALERT_DIALOG_STATE_DEFAULT } from '@/constants/dialog';
import { IShoppingCategory, IShoppingItem } from '@/types/api';
import { ActionButton } from '@/types';

interface Props {
    category: IShoppingCategory;
    items: IShoppingItem[];
}

const CategoryItemList: React.FC<Props> = ({ category, items }) => {
    const { deleteShoppingItems } = useShoppingItemApi();
    const { items: storeItems, setItems: setStoreItems } = useShoppingStore();
    const [deleteCheckDialog, setDeleteCheckDialog] =
        React.useState<AlertDialogData>(ALERT_DIALOG_STATE_DEFAULT);
    const { setNodeRef: setDroppableNodeRef } = useDroppable({
        id: category.id,
    });

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

    /**
     * チェック済みのアイテムをクリアしてストアにセットする
     * @param items アイテムの配列
     */
    const handleAllClearChecked = React.useCallback(
        (items: IShoppingItem[]) => {
            const itemsMap = new Map(items.map(v => [v.id, v]));
            setStoreItems(
                storeItems.map(v =>
                    itemsMap.has(v.id) ? { ...v, isChecked: false } : v,
                ),
            );
        },
        [storeItems],
    );

    const actionButtons: ActionButton[] =
        [
            {
                label: 'チェック解除',
                icon: <X />,
                onClick: () => {
                    handleAllClearChecked(items);
                },
            },
            {
                label: 'チェック済みを削除',
                icon: <Trash />,
                onClick: openDeleteCheckDialog,
            },
        ];

    return (
        <>
            <div className="flex flex-col gap-y-4">
                <div className="flex gap-x-4 items-center text-gray-main">
                    {category.name}
                    <MenuButton
                        actionButtons={actionButtons}
                        placement="top-left"
                        className="w-5 h-5"
                    />
                </div>
                <SortableContext
                    items={items.map(item => item.id)}
                    id={category.id}
                    strategy={rectSortingStrategy}>
                    <div
                        ref={setDroppableNodeRef}
                        className="grid grid-cols-[repeat(auto-fill,_minmax(320px,_1fr))] gap-4">
                        {items.length > 0 ? (
                            items.map(
                                item =>
                                    !!item && (
                                        <Sortable key={item.id} id={item.id}>
                                            <ShoppingItemCard item={item} />
                                        </Sortable>
                                    ),
                            )
                        ) : (
                            <div>登録されているアイテムはありません</div>
                        )}
                    </div>
                </SortableContext>
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
