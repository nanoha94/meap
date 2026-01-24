'use client';
import React from 'react';
import { MenuButton, Header, HeaderTextButton } from '@/components/common';
import { COLOR_VARIANT, DIALOG_NAME, EDIT_MODE } from '@/constants';
import { useShoppingStore } from '@/models/shopping/hooks';
import { CalendarDays, CirclePlus, Pencil } from 'lucide-react';
import { ActionButton } from '@/types';

const ShoppingListPageHeader = () => {
    const { openDialog } = useShoppingStore();
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
            onClick: () =>
                openDialog(DIALOG_NAME.SHOPPING_ITEM_ADD_EDIT, {
                    item: undefined,
                    editMode: EDIT_MODE.CREATE,
                }),
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
