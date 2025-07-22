'use client';
import React from 'react';
import { getHeaderTextButtonClassName } from './headerTextButtonClass';
import Link from 'next/link';

interface Props {
    href: string;
    colorVariant?: 'secondary' | 'gray' | 'accent' | 'alert';
    children: React.ReactNode;
}

const HeaderLinkTextButton = ({ href, colorVariant, children }: Props) => {
    const className = getHeaderTextButtonClassName({ colorVariant });

    return (
        <Link href={href} className={className}>
            {children}
        </Link>
    );
};

export default HeaderLinkTextButton;
