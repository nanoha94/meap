'use client';

import React from 'react';

import Label from '../Label';
import { LabelColorVariant } from '@/types';

interface BadgeProps {
    label: string;
    colorVariant: LabelColorVariant
}

interface HeaderProps {
    title: string;
    price: React.ReactNode;
    subtitle?: React.ReactNode;
}

interface Props {
    badge?: BadgeProps;
    variant?: 'default' | 'current';
    header: HeaderProps;
    children: React.ReactNode;
    footer?: React.ReactNode;
}

const BillingOptionCard = ({
    badge,
    variant = 'default',
    header,
    children,
    footer,
}: Props) => (
    <div
        className={`relative flex flex-1 flex-col gap-y-4 rounded-lg border p-4 ${variant === 'current'
            ? 'border-gray-border bg-gray-background'
            : 'border-gray-border bg-white'
            }`}>
        {badge && (
            <Label
                label={badge.label}
                colorVariant={badge.colorVariant}
                className="absolute left-0 top-0 rounded-tr-none rounded-bl-none"
            />
        )}

        <div className="flex w-full flex-col gap-y-1 border-b border-gray-border py-4">
            <div className="text-center text-lg font-bold">{header.title}</div>
            <div className="text-center text-gray-main">{header.price}</div>
            {header.subtitle && (
                <div className="text-center text-xs text-gray-main">
                    {header.subtitle}
                </div>
            )}
        </div>

        {children}

        {footer && (
            <div className="mt-auto flex flex-col gap-y-4">{footer}</div>
        )}
    </div>
);

export default BillingOptionCard;
