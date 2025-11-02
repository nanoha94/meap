'use client';
import HeaderTextButton from '@/components/common/HeaderTextButtons/HeaderTextButton';
import { CalendarDays, CirclePlus, Pencil } from 'lucide-react';
import itemOpenStyles from '@/styles/itemOpen.module.css';
import React from 'react';
import { LucideIconWrapper } from '@/components/common';
import { colors, DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';
import { useShoppingStore } from '../../hooks';

const HeaderButton = () => {
    const { openDialog } = useShoppingStore();
    const [isOpen, setIsOpen] = React.useState<boolean>(false);
    const containerRef = React.useRef<HTMLDivElement>(null);

    const actionButtons = [
        {
            label: '献立から追加',
            icon: <CalendarDays />,
            // TODO: 実装
            onClick: () => {},
        },
        {
            label: 'テキストで追加',
            icon: <Pencil />,
            onClick: () =>
                openDialog(DIALOG_NAME.SHOPPING_ITEM_ADD_EDIT, {
                    item: undefined,
                    editMode: DIALOG_EDIT_MODE.CREATE,
                }),
        },
    ];

    React.useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setIsOpen(false);
            }
        };

        const handleResize = () => {
            if (isOpen) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        window.addEventListener('resize', handleResize);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            window.removeEventListener('resize', handleResize);
        };
    }, [isOpen]);

    return (
        <div className="relative leading-none">
            <HeaderTextButton
                disabled={isOpen}
                colorVariant="secondary"
                onClick={() => setIsOpen(true)}>
                <CirclePlus size={20} strokeWidth={2} />
                アイテムを追加
            </HeaderTextButton>
            <div
                ref={containerRef}
                className={`z-10 absolute top-10 right-0 py-1 flex flex-col items-start text-base bg-white rounded border border-gray-main shadow-lg  ${
                    isOpen ? itemOpenStyles.open : itemOpenStyles.close
                }`}>
                {actionButtons.map((v, idx) => (
                    <button
                        key={idx}
                        onClick={() => {
                            v.onClick();
                            setIsOpen(false);
                        }}
                        className="px-3 py-1 w-full flex items-center gap-x-2 whitespace-nowrap transition-colors hover:bg-gray-light">
                        <LucideIconWrapper
                            strokeWidth={1.5}
                            color={colors.black}
                            size={20}>
                            {v.icon}
                        </LucideIconWrapper>
                        {v.label}
                    </button>
                ))}
            </div>
        </div>
    );
};

export default HeaderButton;
