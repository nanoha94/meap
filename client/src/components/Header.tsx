'use client';
import React from 'react';
import { ChevronLeft } from 'lucide-react';
import { useRouter } from 'next/navigation';
import MenuButton from './MenuButton';

import { colors } from '@/constants';
import { ActionButton } from '@/types';

interface Props {
    title?: string;
    maxWidth?: string;
    leftContent?: React.ReactNode;
    rightContent?: React.ReactNode;
    hasBackButton?: boolean;
    onBackClick?: () => void;
    actionButtons?: ActionButton[];
    className?: string;
}

const Header = ({ title, maxWidth = '1000px', leftContent, rightContent, hasBackButton = false, onBackClick, actionButtons, className }: Props) => {
    const router = useRouter();
    const handleBackClick = () => (onBackClick ? onBackClick() : router.back());
    return (
        <header
            className="bg-white"
            style={{ boxShadow: 'inset 0 -1px 3px 0 rgba(0, 0, 0, 10%)' }}>
            <div className={`py-3 px-4 sm:px-6 lg:px-10 max-w-[${maxWidth}] mx-auto min-h-[60px] flex items-center justify-between gap-x-10 ${className}`}>
                <div className="flex items-center gap-x-4">
                    {hasBackButton && <button
                        onClick={handleBackClick}
                        className="p-1 appearance-none rounded-full transition-colors hover:bg-gray-light">
                        <ChevronLeft color={colors.black} size={24} />
                    </button>}
                    <h2 className="font-semibold text-xl text-gray-800">
                        {title}
                    </h2>
                    {leftContent}
                </div>
                <div className='flex items-center gap-x-4'>
                    {rightContent}
                    {actionButtons && actionButtons.length > 0 && <MenuButton
                        actionButtons={actionButtons}
                        placement="top-right"
                    />}
                </div>
            </div>
        </header>
    );
};

export default Header;
