'use client';

import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CirclePlus, Trash2, X } from 'lucide-react';

import { MenuButton, ShoppingItemEditForm, Sortable, TextButton } from '@/components';
import { useAlertDialog, useDialog } from '@/hooks';
import { ActionButton, IShoppingCategory, IShoppingItem } from '@/types';
import { SHOPPING_ALERT_DIALOG_CONFIGS } from '../../constants';
import { useShoppingItemApi, useShoppingStore } from '../../hooks';
import ShoppingItemCard from './ShoppingItemCard';
import { BUTTON_TYPE, EDIT_MODE } from '@/constants';

interface Props {
    category: IShoppingCategory;
    items: IShoppingItem[];
    syncPendingItems: () => Promise<void>;
}

const CategoryItemList: React.FC<Props> = ({
    category,
    items,
    syncPendingItems,
}) => {
    // store
    const storeItems = useShoppingStore(state => state.items);
    const setStoreItems = useShoppingStore(state => state.setItems);

    // hook
    const { deleteShoppingItems } = useShoppingItemApi();
    const { openAlertDialog } = useAlertDialog();
    const { openDialog } = useDialog();
    const { setNodeRef: setDroppableNodeRef } = useDroppable({
        id: category.id,
    });

    /**
     * 削除確認ダイアログを開く
     */
    const openDeleteCheckDialog = async () => {
        await syncPendingItems();
        const config = SHOPPING_ALERT_DIALOG_CONFIGS.deleteItemsFromCategory(
            category.name,
            items.filter(v => !v.isPinned && v.isChecked).map(v => v.name),
        );
        openAlertDialog(config, () => {
            // TODO: 削除可能なものが含まれているかチェックする
            deleteShoppingItems(
                items
                    .filter(v => !v.isPinned && v.isChecked)
                    .map(v => v.id),
            );
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

    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtonConfigs: ActionButton[] =
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
                icon: <Trash2 />,
                onClick: openDeleteCheckDialog,
            },
        ];

    return (
        <>
            <div className="md:min-w-[460px] flex flex-col rounded bg-accent-background shadow-card">
                <div className="px-5 py-4 flex gap-x-4 justify-between items-center text-xl">
                    {category.name}
                    <MenuButton
                        actionButtons={actionButtonConfigs}
                        placement="top-right"
                        className="w-5 h-5"
                    />
                </div>
                <div className='pl-5 pr-4 py-4 flex flex-col gap-y-4'>
                    <SortableContext
                        items={items.map(item => item.id)}
                        id={category.id}
                        strategy={verticalListSortingStrategy}
                    >
                        <div
                            ref={setDroppableNodeRef}
                            className="flex flex-col gap-y-4">
                            {items.length > 0 ? (items.map(
                                item =>
                                    !!item && (
                                        <Sortable key={item.id} id={item.id}>
                                            <ShoppingItemCard item={item} syncPendingItems={syncPendingItems} />
                                        </Sortable>
                                    ),
                            )) : <p>登録されているアイテムはありません</p>}
                        </div>
                    </SortableContext >
                    <TextButton
                        type={BUTTON_TYPE.BUTTON}
                        onClick={async () => {
                            await syncPendingItems();
                            openDialog({
                                title: '買い物アイテムを追加',
                                children: <ShoppingItemEditForm
                                    editingItem={undefined}
                                    editMode={EDIT_MODE.CREATE}
                                />
                            });
                        }}
                        className="!border-none !bg-transparent hover:!bg-gray-light">
                        <CirclePlus size={20} />
                        アイテムを追加
                    </TextButton>
                </div>
            </div >
        </>
    );
};

export default CategoryItemList;
