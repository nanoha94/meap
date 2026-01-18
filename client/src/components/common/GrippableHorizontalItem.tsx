'use client';
import { colors } from '@/constants';
import { GripVertical, Trash } from 'lucide-react';

interface Props {
    hasDeleteButton?: boolean;
    isDisabledDeleteButton?: boolean;
    onDelete: () => void;
    children: React.ReactNode;
    className?: string;
}

const GrippableHorizontalItem: React.FC<Props> = ({
    hasDeleteButton = false,
    isDisabledDeleteButton = false,
    onDelete,
    children,
    className = '',
}) => {
    return (
        <div className={`flex items-center gap-x-2 ${className}`}>
            <GripVertical color={colors.gray.main} />
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

export default GrippableHorizontalItem;
