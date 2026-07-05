'use client';
import React from 'react';
import Link from 'next/link';

import { BUTTON_SIZE, BUTTON_TYPE, BUTTON_VARIANT, ButtonSize, ButtonType, ButtonVariant, COLOR_VARIANT } from '@/constants';

type Props = {
    className?: string;
    size?: ButtonSize;
    disabled?: boolean;
    variant?: ButtonVariant;
    colorVariant?:
    | (typeof COLOR_VARIANT)['PRIMARY']
    | (typeof COLOR_VARIANT)['SECONDARY']
    | (typeof COLOR_VARIANT)['ACCENT']
    | (typeof COLOR_VARIANT)['GRAY']
    | (typeof COLOR_VARIANT)['ALERT'];
    children: React.ReactNode;
    type?: ButtonType;
} & (
        | { href: string; onClick?: never }
        | { href?: never; onClick: React.MouseEventHandler<HTMLButtonElement> }
    );

const TextButton = ({
    className,
    size = BUTTON_SIZE.NORMAL,
    disabled = false,
    variant = BUTTON_VARIANT.OUTLINED,
    colorVariant = COLOR_VARIANT.PRIMARY,
    children,
    href,
    onClick,
    type = BUTTON_TYPE.BUTTON,
}: Props) => {
    const colorClasses = React.useMemo(() => {
        const colorMappings = {
            filled: {
                primary: 'text-white bg-primary-main hover:bg-primary-light',
                secondary: 'text-white bg-secondary-main hover:bg-secondary-light',
                accent: 'text-white bg-accent-main hover:bg-accent-light',
                gray: 'text-white bg-gray-main hover:bg-gray-light',
                alert: 'text-white bg-alert-main hover:bg-alert-light',
            },
            outlined: {
                primary: 'text-primary-main bg-white border-primary-main hover:bg-gray-light',
                secondary: 'text-secondary-main bg-white border-secondary-main hover:bg-gray-light',
                accent: 'text-accent-main bg-white border-accent-main hover:bg-gray-light',
                gray: 'text-gray-main bg-white border-gray-main hover:bg-gray-light',
                alert: 'text-alert-main bg-white border-alert-main hover:bg-gray-light',
            },
        };
        return colorMappings[variant][colorVariant];
    }, [variant, colorVariant]);

    const buttonClassName = `py-1 px-2 w-fit flex items-center gap-x-1 ${size === BUTTON_SIZE.SMALL ? 'text-xs' : 'text-base'} font-bold rounded-md border transition-colors ${colorClasses} ${disabled ? 'opacity-50 pointer-events-none' : ''} ${className ?? ''}`;

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
