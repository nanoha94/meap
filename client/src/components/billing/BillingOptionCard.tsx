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
    header: HeaderProps;
    children: React.ReactNode;
    footer?: React.ReactNode;
    className?: string;
}

const BillingOptionCard = ({
    badge,
    header,
    children,
    footer,
    className,
}: Props) => (
    <div
        className={`relative flex flex-1 flex-col gap-y-4 rounded-lg p-4 bg-white ${className || 'border-gray-border border'}`}>
        {badge && (
            <Label
                label={badge.label}
                colorVariant={badge.colorVariant}
                className="absolute left-0 top-0 rounded-tr-none rounded-bl-none"
            />
        )}

        <div className="flex w-full flex-col gap-y-1 border-b border-gray-border py-4">
            <div className="text-center text-lg font-bold">{header.title}</div>
            <div className="text-center">{header.price}</div>
            {header.subtitle && (
                <div className="text-center text-xs">
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
