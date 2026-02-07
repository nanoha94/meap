'use client';
import React from 'react';
import { LucideProps } from 'lucide-react';

import { colors } from '@/constants';

interface Props {
    strokeWidth?: number;
    color?: string;
    size?: number;
    children: React.ReactElement<LucideProps>;
}

/**
 * LucideIconWrapper は LucideIcon の props をラップして、strokeWidth、color、size を指定できるようにしています。   
 * @param strokeWidth LucideIcon の strokeWidth を指定します。  
 * @param color LucideIcon の color を指定します。  colors の中から指定できます。
 * @param size LucideIcon の size を指定します。
 * @param children LucideIcon の children を指定します。
 * @returns React.ReactElement<LucideProps>
 */
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
