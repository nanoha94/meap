'use client';
import React from 'react';
import Link from 'next/link';

import { ButtonType, BUTTON_VARIANT, COLOR_VARIANT, PRIMARY_BUTTON_COLOR_CLASS } from '@/constants';

type Props = {
    type?: ButtonType;
    disabled?: boolean;
    colorVariant?:
        | (typeof COLOR_VARIANT)['PRIMARY']
        | (typeof COLOR_VARIANT)['GRAY'];
    /** フォーム外の submit ボタンで、紐づける form の id */
    form?: string;
    children: React.ReactNode;
} & ({ href: string; onClick?: never } | { onClick?: () => void; href?: never });

const getHeaderTextButtonClassName = (colorVariant: NonNullable<Props['colorVariant']>) => {
    return `py-1 px-2 w-fit flex items-center gap-x-1 text-base font-bold rounded transition-colors shadow-card ${PRIMARY_BUTTON_COLOR_CLASS[BUTTON_VARIANT.FILLED][colorVariant]} disabled:opacity-50 disabled:pointer-events-none`;
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
    const className = getHeaderTextButtonClassName(colorVariant ?? COLOR_VARIANT.PRIMARY);

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
