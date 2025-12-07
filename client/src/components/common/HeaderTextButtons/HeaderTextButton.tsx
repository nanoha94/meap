'use client';
import React from 'react';
import { getHeaderTextButtonClassName } from './headerTextButtonClass';

interface Props {
    disabled?: boolean;
    colorVariant?: 'secondary' | 'gray' | 'accent' | 'alert';
    children: React.ReactNode;
    onClick: () => void;
}

const HeaderTextButton = ({
    disabled,
    colorVariant,
    children,
    onClick,
}: Props) => {
    const className = getHeaderTextButtonClassName({ disabled, colorVariant });

    return (
        <button
            type="button"
            onClick={onClick}
            className={className}
            disabled={disabled}>
            {children}
        </button>
    );
};

export default HeaderTextButton;
