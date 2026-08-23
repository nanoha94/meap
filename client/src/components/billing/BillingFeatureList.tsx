'use client';

import React from 'react';

import { Check } from 'lucide-react';

interface Props {
    features: readonly string[];
    variant?: 'checklist' | 'bullet';
    className?: string;
}

const BillingFeatureList = ({
    features,
    variant = 'checklist',
    className,
}: Props) => {
    if (variant === 'bullet') {
        return (
            <ul
                className={`mt-1 flex flex-col gap-y-1 pl-4 ${className ?? ''}`}>
                {features.map(feature => (
                    <li key={feature}>・{feature}</li>
                ))}
            </ul>
        );
    }

    return (
        <ul className={`flex flex-col gap-y-2 ${className ?? ''}`}>
            {features.map(feature => (
                <li
                    key={feature}
                    className="flex items-start gap-x-2 leading-relaxed">
                    <Check
                        className="mt-1 text-primary-main"
                        strokeWidth={3}
                        size={16}
                        aria-hidden="true"
                    />
                    <span>{feature}</span>
                </li>
            ))}
        </ul>
    );
};

export default BillingFeatureList;
