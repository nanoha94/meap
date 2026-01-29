'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import React from 'react';
import { navigationItems } from '@/constants';
import { NavigationIcon } from '.';

interface Props {
    className?: string;
}

const FooterNavigation = ({ className }: Props) => {
    const pathname = usePathname();

    if (!pathname) {
        return <></>;
    }

    // URLの深さが2より深い場合は表示しない
    // /settings/account/ など
    // if (pathname.split('/').filter(Boolean).length >= 2) {
    //     return <></>;
    // }

    return (
        <div
            className={`fixed bottom-0 w-full flex bg-white ${className ? className : ''}`}
            style={{ boxShadow: 'inset 0 1px 3px 0 rgba(0, 0, 0, 10%)' }}>
            {navigationItems.map((v, idx) => (
                <Link
                    key={idx}
                    href={v.link}
                    className={`py-2 px-0.5 flex-1 transition-colors hover:bg-gray-ligh ${pathname === v.link ? 'pointer-events-none' : ''}`}>
                    <div className="relative mx-auto w-16 h-auto aspect-square rounded-full transition-colors hover:bg-gray-light">
                        <div
                            className={`absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 flex flex-col items-center gap-y-0.5 text-xs font-bold whitespace-nowrap ${pathname === v.link ? 'text-primary-main' : 'text-black'} `}>
                            <NavigationIcon
                                iconType={v.iconType}
                                isCurrentPage={pathname === v.link}
                            />
                            {v.name}
                        </div>
                    </div>
                </Link>
            ))}
        </div>
    );
};

export default FooterNavigation;
