'use client';
import React from 'react';
import { MenuButton, Header, HeaderTextButton } from '@/components/common';
import { COLOR_VARIANT, EDIT_MODE } from '@/constants';
import { CalendarDays, CirclePlus, Pencil } from 'lucide-react';
import { ActionButton } from '@/types';
import { useDialog } from '@/hooks/useDialog';
import { SHOPPING_ITEM_SETTING_DIALOG_CONFIGS } from '@/models/shopping/constants';
import { ShoppingItemEditForm } from '@/components/dialog-contents';

const ShoppingListPageHeader = () => {
    const { openDialog } = useDialog();
    const [isOpen, setIsOpen] = React.useState<boolean>(false);

    const actionButtons: ActionButton[] = [
        {
            label: '献立から追加',
            icon: <CalendarDays />,
            // TODO: 実装
            onClick: () => { },
        },
        {
            label: 'テキストで追加',
            icon: <Pencil />,
            onClick: () => {
                openDialog({
                    title: SHOPPING_ITEM_SETTING_DIALOG_CONFIGS[EDIT_MODE.CREATE].title,
                    children: () =>
                        <ShoppingItemEditForm
                            editingItem={undefined}
                            editMode={EDIT_MODE.CREATE}
                        />
                });
            },
        },
    ];

    return (
        <Header title="買い物リスト"
            rightContent={<div className="relative leading-none">
                <MenuButton
                    customButton={<HeaderTextButton
                        disabled={isOpen}
                        colorVariant={COLOR_VARIANT.SECONDARY}
                        onClick={() => setIsOpen(true)}>
                        <CirclePlus size={20} strokeWidth={2} />
                        アイテムを追加
                    </HeaderTextButton>}
                    actionButtons={actionButtons}
                    placement="top-10 right-0"
                />
            </div>}
        />
    );
};

export default ShoppingListPageHeader;
