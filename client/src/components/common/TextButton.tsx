'use client';
import { BUTTON_SIZE, BUTTON_TYPE, COLOR_VARIANT } from '@/constants';
import React from 'react';

interface Props {
    className?: string;
    size?: BUTTON_SIZE;
    disabled?: boolean;
    colorVariant?: COLOR_VARIANT.PRIMARY | COLOR_VARIANT.SECONDARY | COLOR_VARIANT.GRAY | COLOR_VARIANT.ACCENT;
    children: React.ReactNode;
    onClick: () => void;
    type?: BUTTON_TYPE;
}

const TextButton = ({
    className,
    size = BUTTON_SIZE.NORMAL,
    disabled = false,
    colorVariant = COLOR_VARIANT.PRIMARY,
    children,
    onClick,
    type = BUTTON_TYPE.BUTTON,
}: Props) => {
    const colorClasses = React.useMemo(() => {
        const colorMappings = {
            primary: 'text-primary-main border-primary-main',
            secondary: 'text-secondary-main border-secondary-main',
            gray: 'text-gray-main border-gray-main',
            accent: 'text-accent-main border-accent-main',
        };
        return colorMappings[colorVariant];
    }, [colorVariant]);

    return (
        <button
            type={type}
            onClick={onClick}
            className={`py-1 px-2 w-fit flex items-center gap-x-1 ${size === 'small' ? 'text-sm' : 'text-base'
                } font-bold bg-white rounded border transition-colors hover:bg-gray-light ${colorClasses
                } ${disabled ? 'opacity-50' : ''} ${className}`}
            disabled={disabled}>
            {children}
        </button>
    );
};

export default TextButton;
