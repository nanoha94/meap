'use client';
import React from 'react';

import { BUTTON_SIZE, BUTTON_TYPE, ButtonSize, ButtonType, COLOR_VARIANT } from '@/constants';

interface Props {
    className?: string;
    size?: ButtonSize;
    disabled?: boolean;
    colorVariant?: (typeof COLOR_VARIANT)['PRIMARY'] | (typeof COLOR_VARIANT)['SECONDARY'] | (typeof COLOR_VARIANT)['GRAY'] | (typeof COLOR_VARIANT)['ACCENT'];
    children: React.ReactNode;
    onClick: () => void;
    type?: ButtonType;
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
