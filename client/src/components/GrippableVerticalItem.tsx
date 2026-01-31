'use client';
import React from 'react';
import { GripVertical, Trash2 } from 'lucide-react';

import { colors } from '@/constants';

interface Props {
    order?: number;
    hasDeleteButton?: boolean;
    isDisabledDeleteButton?: boolean;
    onDelete: () => void;
    children: React.ReactNode;
    className?: string;
}

const GrippableVerticalItem: React.FC<Props> = ({
    order,
    hasDeleteButton = false,
    isDisabledDeleteButton = false,
    onDelete,
    children,
    className = '',
}) => {
    return (
        <div className={`flex flex-col gap-x-2 gap-y-1 ${className}`}>
            <div className="flex justify-between gap-x-2">
                <div className="flex items-center gap-x-2">
                    <GripVertical color={colors.gray.main} />
                    {!!order && <div className="text-xl">{order}.</div>}
                </div>
                {hasDeleteButton && (
                    <button
                        type="button"
                        onClick={isDisabledDeleteButton ? () => { } : onDelete}
                        className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0  disabled:cursor-default"
                        disabled={isDisabledDeleteButton}>
                        <Trash2 color={colors.alert.main} size={28} />
                    </button>
                )}
            </div>
            {children}
        </div>
    );
};

export default GrippableVerticalItem;
