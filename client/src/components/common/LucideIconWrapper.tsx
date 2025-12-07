'use client';
import { colors } from '@/constants';
import { LucideProps } from 'lucide-react';
import React from 'react';

interface Props {
    strokeWidth?: number;
    color?: string;
    size?: number;
    children: React.ReactElement<LucideProps>;
}

const LucideIconWrapper = ({
    strokeWidth = 2,
    color = colors.black,
    size = 20,
    children,
}: Props) => {
    return React.cloneElement(children, {
        strokeWidth: strokeWidth,
        color: color,
        size: size,
    });
};

export default LucideIconWrapper;
