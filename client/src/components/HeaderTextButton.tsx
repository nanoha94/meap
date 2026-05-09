'use client';
import React from 'react';
import Link from 'next/link';

import { ButtonType, COLOR_VARIANT } from '@/constants';

type Props = {
    type?: ButtonType;
    disabled?: boolean;
    colorVariant?:
    | (typeof COLOR_VARIANT)['SECONDARY']
    | (typeof COLOR_VARIANT)['GRAY'];
    /** フォーム外の submit ボタンで、紐づける form の id */
    form?: string;
    children: React.ReactNode;
} & ({ href: string; onClick?: never } | { onClick?: () => void; href?: never });

const getHeaderTextButtonClassName = (colorVariant: NonNullable<Props['colorVariant']>) => {
    const colorMappings = {
        secondary:
            'text-white bg-secondary-main hover:text-secondary-main hover:bg-secondary-light',
        gray: 'text-white bg-gray-main hover:text-gray-main hover:bg-gray-light'
    };
    return `py-1 px-2 w-fit flex items-center gap-x-1 text-base font-bold rounded transition-colors shadow-card ${colorMappings[colorVariant]
        } disabled:opacity-50 disabled:pointer-events-none`;
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
    const className = getHeaderTextButtonClassName(colorVariant ?? COLOR_VARIANT.SECONDARY);

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
