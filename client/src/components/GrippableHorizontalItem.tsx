'use client';
import React from 'react';
import { GripVertical, Trash2 } from 'lucide-react';

import { SortableHandle } from '@/components/dnd';
import { colors } from '@/constants';

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
        <div className={`flex items-center ${className ?? ''}`}>
            <SortableHandle className="pr-2">
                <GripVertical color={colors.gray.main} />
            </SortableHandle>
            {children}
            {hasDeleteButton && (
                <button
                    type="button"
                    onClick={isDisabledDeleteButton ? undefined : onDelete}
                    className="ml-2 p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0 disabled:cursor-default"
                    disabled={isDisabledDeleteButton}>
                    <Trash2 color={colors.alert.main} size={28} />
                </button>
            )}
        </div>
    );
};

export default GrippableHorizontalItem;
