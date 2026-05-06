'use client';
import React from 'react';
import { Check, GripVertical, Pencil, Pin, PinOff, Trash2 } from 'lucide-react';

import { MenuButton, ShoppingItemEditForm } from '@/components';
import { SortableHandle } from '@/components/dnd';
import { EDIT_MODE, colors } from '@/constants';
import { useAlertDialog, useDialog } from '@/hooks';
import { ActionButton, IShoppingItem } from '@/types';
import { SHOPPING_ALERT_DIALOG_CONFIGS } from '../../constants';
import { useShoppingItemApi, useShoppingStore } from '../../hooks';

interface Props {
    item: IShoppingItem;
    syncPendingItems: () => Promise<void>;
}

const ShoppingItemCard = ({ item, syncPendingItems }: Props) => {
    // state
    const { id, name, isPinned = false, isChecked = false } = item;

    //store
    const storeItems = useShoppingStore(state => state.items);
    const setStoreItems = useShoppingStore(state => state.setItems);

    // hook
    const { deleteShoppingItems } = useShoppingItemApi();
    const { openDialog } = useDialog();
    const { openAlertDialog } = useAlertDialog();

    /**
     * アイテムのプロパティを切り替えてストアにセットする
     * @param item アイテム
     * @param propertyName プロパティ名
     */
    const handleToggleItemProperty = React.useCallback(
        (item: IShoppingItem, propertyName: 'isPinned' | 'isChecked') => {
            // ストアにセット
            setStoreItems(
                storeItems.map(v =>
                    v.id === item.id
                        ? { ...v, [propertyName]: !v[propertyName] }
                        : v,
                ),
            );
        },

        [storeItems, setStoreItems],
    );

    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtonConfigs: ActionButton[] = [
        {
            label: '編集する',
            icon: <Pencil />,
            onClick: async () => {
                await syncPendingItems();
                openDialog({
                    title: '買い物アイテムを編集',
                    children: <ShoppingItemEditForm
                        editingItem={item}
                        editMode={EDIT_MODE.UPDATE}
                    />
                });
            },
        },
        {
            label: '削除する',
            icon: <Trash2 />,
            onClick: async () => {
                await syncPendingItems();
                openAlertDialog(SHOPPING_ALERT_DIALOG_CONFIGS.deleteItem(name), () => {
                    setStoreItems(storeItems.filter(v => v.id !== id));
                    deleteShoppingItems([id]);
                });
            },
        },
        {
            label: isPinned ? '固定解除する' : '固定する',
            icon: isPinned ? <PinOff /> : <Pin />,
            onClick: () => handleToggleItemProperty(item, 'isPinned'),
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
                    <SortableHandle>
                        <GripVertical color={colors.gray.main} />
                    </SortableHandle>
                    <div className="flex-1">
                        <input
                            type="checkbox"
                            id={`checkbox-${id}`}
                            checked={isChecked}
                            onChange={() => {
                                handleToggleItemProperty(item, 'isChecked');
                            }}
                            className="hidden"
                        />
                        <label
                            htmlFor={`checkbox-${id}`}
                            className="relative pl-7 w-full h-full whitespace-nowrap cursor-pointer">
                            <div
                                className={`absolute top-1/2 -translate-y-1/2 left-0 w-5 h-5 rounded border-[1.5px] transition-colors ${isChecked
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
                    <MenuButton
                        actionButtons={actionButtonConfigs}
                        placement="top-right"
                    />
                </div>
            </div>
        </>
    );
};

export default ShoppingItemCard;
