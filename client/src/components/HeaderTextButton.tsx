'use client';
import React from 'react';
import Link from 'next/link';

import { ButtonType, COLOR_VARIANT } from '@/constants';

type Props = {
    type?: ButtonType;
    disabled?: boolean;
    colorVariant?: (typeof COLOR_VARIANT)['SECONDARY'] | (typeof COLOR_VARIANT)['GRAY'] | (typeof COLOR_VARIANT)['ACCENT'] | (typeof COLOR_VARIANT)['ALERT'];
    /** フォーム外の submit ボタンで、紐づける form の id */
    form?: string;
    children: React.ReactNode;
} & ({ href: string; onClick?: never } | { onClick?: () => void; href?: never });

const getHeaderTextButtonClassName = ({
    disabled = false,
    colorVariant = COLOR_VARIANT.SECONDARY,
}: {
    disabled?: boolean;
    colorVariant?: Props['colorVariant'];
}) => {
    const colorMappings = {
        secondary:
            'text-secondary-main border-secondary-main bg-secondary-background hover:bg-secondary-main',
        gray: 'text-gray-main border-gray-main bg-gray-background hover:bg-gray-main',
        accent: 'text-accent-main border-accent-main bg-accent-background hover:bg-accent-main',
        alert: 'text-alert-main border-alert-main bg-alert-background hover:bg-alert-main',
    };
    return `py-1 px-2 w-fit flex items-center gap-x-1 font-bold rounded border-2 transition-colors hover:text-white shadow-card ${colorMappings[colorVariant]
        } ${disabled ? 'opacity-50 pointer-events-none' : ''}`;
};

const HeaderTextButton = ({
    type,
    disabled,
    colorVariant,
    form,
    href,
    children,
    onClick,
}: Props) => {
    const className = getHeaderTextButtonClassName({ disabled, colorVariant });

    if (href) {
        return (
            <Link href={href} className={className}>
                {children}
            </Link>
        );
    }

    return (
        <button
            type={type}
            form={form}
            onClick={onClick}
            className={className}
            disabled={disabled}>
            {children}
        </button>
    );
};

export default HeaderTextButton;
