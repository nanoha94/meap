'use client';
import React from 'react';
import Link from 'next/link';

import {
    BUTTON_SIZE,
    BUTTON_TYPE,
    BUTTON_VARIANT,
    ButtonSize,
    ButtonType,
    ButtonVariant,
    COLOR_VARIANT,
    PRIMARY_BUTTON_COLOR_CLASS,
} from '@/constants';

type Props = {
    className?: string;
    size?: ButtonSize;
    disabled?: boolean;
    variant?: ButtonVariant;
    colorVariant?:
        | (typeof COLOR_VARIANT)['PRIMARY']
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
    const colorClasses = PRIMARY_BUTTON_COLOR_CLASS[variant][colorVariant];

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
