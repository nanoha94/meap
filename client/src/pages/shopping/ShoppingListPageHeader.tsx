'use client';
import React from 'react';
import { CalendarDays, CirclePlus, Pencil } from 'lucide-react';

import { Header, HeaderTextButton, MenuButton, ShoppingItemEditForm } from '@/components';
import { COLOR_VARIANT, EDIT_MODE } from '@/constants';
import { useDialog } from '@/hooks';
import { ActionButton } from '@/types';

const ShoppingListPageHeader = () => {
    const { openDialog } = useDialog();
    const [isOpen, setIsOpen] = React.useState<boolean>(false);

    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtonConfigs: ActionButton[] = [
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
                    title: '買い物アイテムを追加',
                    children: <ShoppingItemEditForm
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
                    actionButtons={actionButtonConfigs}
                    placement="top-10 right-0"
                />
            </div>}
        />
    );
};

export default ShoppingListPageHeader;
