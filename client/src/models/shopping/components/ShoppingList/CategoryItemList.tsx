'use client';

import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import { rectSortingStrategy, SortableContext } from '@dnd-kit/sortable';
import { Trash2, X } from 'lucide-react';

import { MenuButton, Sortable } from '@/components';
import { useAlertDialog } from '@/hooks';
import { ActionButton, IShoppingCategory, IShoppingItem } from '@/types';
import { SHOPPING_ALERT_DIALOG_CONFIGS } from '../../constants';
import { useShoppingItemApi, useShoppingStore } from '../../hooks';
import ShoppingItemCard from './ShoppingItemCard';

interface Props {
    category: IShoppingCategory;
    items: IShoppingItem[];
}

const CategoryItemList: React.FC<Props> = ({ category, items }) => {
    const { deleteShoppingItems } = useShoppingItemApi();
    const { items: storeItems, setItems: setStoreItems } = useShoppingStore();
    const { openAlertDialog } = useAlertDialog();
    const { setNodeRef: setDroppableNodeRef } = useDroppable({
        id: category.id,
    });

    /**
     * 削除確認ダイアログを開く
     */
    const openDeleteCheckDialog = () => {
        const config = SHOPPING_ALERT_DIALOG_CONFIGS.deleteItemsFromCategory(
            category.name,
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
            <div className="flex flex-col gap-y-4">
                <div className="flex gap-x-4 items-center text-gray-main">
                    {category.name}
                    <MenuButton
                        actionButtons={actionButtonConfigs}
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
        </>
    );
};

export default CategoryItemList;
