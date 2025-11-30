'use client';
import { colors, FLEX_ALIGN_ITEMS } from '@/constants';
import { GripVertical, Trash } from 'lucide-react';

interface Props {
    hasDeleteButton?: boolean;
    isDisabledDeleteButton?: boolean;
    onDelete: () => void;
    children: React.ReactNode;
    alignItems?: (typeof FLEX_ALIGN_ITEMS)[keyof typeof FLEX_ALIGN_ITEMS];
    className?: string;
}

const styleConfigs = {
    [FLEX_ALIGN_ITEMS.START]: {
        paddingTop: 'pt-1',
    },
    [FLEX_ALIGN_ITEMS.CENTER]: {
        paddingTop: '',
    },
};

const GrippableEditItem: React.FC<Props> = ({
    hasDeleteButton = false,
    isDisabledDeleteButton = false,
    onDelete,
    children,
    alignItems = 'center',
    className = '',
}) => {
    return (
        <div className={`flex items-${alignItems} gap-x-2 ${className}`}>
            <GripVertical
                color={colors.gray.main}
                className={styleConfigs[alignItems].paddingTop}
            />
            {children}
            {hasDeleteButton && (
                <button
                    type="button"
                    onClick={isDisabledDeleteButton ? () => {} : onDelete}
                    className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0  disabled:cursor-default"
                    disabled={isDisabledDeleteButton}>
                    <Trash color={colors.alert.main} size={28} />
                </button>
            )}
        </div>
    );
};

export default GrippableEditItem;
