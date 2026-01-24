import { colors, EDIT_MODE, DIALOG_NAME } from '@/constants';
import { CalendarDays, LucideProps, Minus, Pencil, Plus } from 'lucide-react';
import React from 'react';
import itemOpenStyles from '@/styles/itemOpen.module.css';
import { useShoppingStore } from '../../hooks';
import { ActionButton } from '@/types';

const AddShoppingItemButton = () => {
    const [isOpen, setIsOpen] = React.useState<boolean>(false);
    const containerRef = React.useRef<HTMLDivElement>(null);
    const { openDialog } = useShoppingStore();

    const actionButtons: ActionButton[] = [
        {
            label: '献立から追加',
            icon: <CalendarDays />,
            // TODO: 実装
            onClick: () => { },
        },
        {
            label: 'テキストから追加',
            icon: <Pencil />,
            onClick: () =>
                openDialog(DIALOG_NAME.SHOPPING_ITEM_ADD_EDIT, {
                    item: undefined,
                    editMode: EDIT_MODE.CREATE,
                }),
        },
    ];

    const IconWrapper = ({
        children,
    }: {
        children: React.ReactElement<LucideProps>;
    }) => (
        <div className="p-2 bg-white rounded-full shadow-lg">
            {React.cloneElement(children, {
                strokeWidth: 2,
                color: colors.primary.main,
                size: 24,
            })}
        </div>
    );

    return (
        <div className="z-10 md:hidden">
            <div
                className="fixed bottom-[94px] right-5 leading-none"
                ref={containerRef}>
                <button
                    onClick={() => setIsOpen(true)}
                    className="w-12 h-12 bg-primary-main rounded-full flex items-center justify-center shadow-lg transition-colors hover:bg-accent-main">
                    {isOpen ? (
                        <Minus
                            color={colors.white}
                            className="w-8 h-8"
                            strokeWidth={2.5}
                        />
                    ) : (
                        <Plus
                            color={colors.white}
                            className="w-8 h-8"
                            strokeWidth={2.5}
                        />
                    )}
                </button>
            </div>
            {isOpen && (
                <div
                    onClick={() => setIsOpen(false)}
                    className="fixed z-50 top-0 left-0 w-full h-screen bg-black/50"
                />
            )}
            <div
                className={`z-[100] absolute flex flex-col items-end gap-y-2.5 text-xl text-white bottom-[160px] right-6 ${isOpen ? itemOpenStyles.open : itemOpenStyles.close
                    }`}>
                {actionButtons.map((v, idx) => (
                    <button
                        key={idx}
                        onClick={() => {
                            v.onClick();
                            setIsOpen(false);
                        }}
                        className="z-[100] flex items-center gap-x-2 whitespace-nowrap rounded-full pl-4 transition-colors hover:text-primary-main hover:bg-gray-light">
                        {v.label}
                        <IconWrapper>{v.icon}</IconWrapper>
                    </button>
                ))}
            </div>
        </div>
    );
};

export default AddShoppingItemButton;
