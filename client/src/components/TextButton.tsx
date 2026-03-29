'use client';
import React from 'react';
import Link from 'next/link';

import { BUTTON_SIZE, BUTTON_TYPE, ButtonSize, ButtonType, COLOR_VARIANT } from '@/constants';

type Props = {
    className?: string;
    size?: ButtonSize;
    disabled?: boolean;
    colorVariant?: (typeof COLOR_VARIANT)['PRIMARY'] | (typeof COLOR_VARIANT)['SECONDARY'] | (typeof COLOR_VARIANT)['GRAY'] | (typeof COLOR_VARIANT)['ALERT'];
    children: React.ReactNode;
    type?: ButtonType;
} & ({ href: string; onClick?: never } | { onClick?: () => void; href?: never });

const TextButton = ({
    className,
    size = BUTTON_SIZE.NORMAL,
    disabled = false,
    colorVariant = COLOR_VARIANT.PRIMARY,
    children,
    href,
    onClick,
    type = BUTTON_TYPE.BUTTON,
}: Props) => {
    const colorClasses = React.useMemo(() => {
        const colorMappings = {
            primary: 'text-primary-main border-primary-main',
            secondary: 'text-secondary-main border-secondary-main',
            gray: 'text-gray-main border-gray-main',
            alert: 'text-alert-main border-alert-main',
        };
        return colorMappings[colorVariant];
    }, [colorVariant]);

    const buttonClassName = `py-1 px-2 w-fit flex items-center gap-x-1 ${size === BUTTON_SIZE.SMALL ? 'text-xs' : 'text-base'} font-bold bg-white rounded border transition-colors hover:bg-gray-light ${colorClasses} ${disabled ? 'opacity-50 pointer-events-none' : ''} ${className ?? ''}`;

    if (href) {
        return (
            <Link href={href} className={buttonClassName}>
                {children}
            </Link>
        );
    }

    return (
        <button
            type={type}
            onClick={onClick}
            className={buttonClassName}
            disabled={disabled}>
            {children}
        </button>
    );
};

export default TextButton;
