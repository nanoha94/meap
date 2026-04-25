'use client';
import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { LogOut } from 'lucide-react';
import { NavigationIcon } from '.';
import ApplicationLogo from '../ApplicationLogo';

import { navigationItems } from '@/constants';
import { useAuth } from '@/hooks';
import { useRecipeStore } from '@/models/recipe';
import { useUserStore, iconAvatar } from '@/models/user';
import { getBrowserQueryString } from '@/models/recipe/utils';
import Image from 'next/image';

interface Props {
    className?: string;
}

const SideNavigation = ({ className }: Props) => {
    // store
    const loginUser = useUserStore(state => state.loginUser);
    const listSortOptions = useRecipeStore(state => state.listSortOptions);
    const listFilterOptions = useRecipeStore(state => state.listFilterOptions);
    const listCurrentPage = useRecipeStore(state => state.listCurrentPage);

    // hook
    const { logout } = useAuth();
    const pathname = usePathname();

    if (!pathname) {
        return <></>;
    }

    const formattedLink = (link: string) =>
        link === '/recipe'
            ? `/recipe?${getBrowserQueryString(listSortOptions, listFilterOptions, listCurrentPage)}`
            : link;

    return (
        <div
            className={`fixed top-0 left-0 w-[160px] h-full bg-white ${className ?? ''}`}
            style={{ boxShadow: '5px 0 8px 0 rgba(0, 0, 0, 10%)' }}>
            <div className="py-3 flex flex-col border-b border-gray-border">
                <Link href="/" className="w-fit mx-auto block">
                    <ApplicationLogo className="w-[100px] h-auto fill-current text-gray-500" />
                </Link>
            </div>
            <div className="py-3 flex flex-col border-b border-gray-border">
                {loginUser && (
                    <Link
                        href="/settings/account"
                        key={loginUser.id}
                        className="py-2 px-3 w-full mx-auto flex flex-col items-center gap-y-1 transition-colors hover:bg-gray-light ">
                        <div className="w-14 h-auto aspect-square rounded-full overflow-hidden">
                            {loginUser?.avatar?.image ? (
                                <Image
                                    src={loginUser.avatar.image.src}
                                    alt="avatar"
                                    width={loginUser.avatar.image.width}
                                    height={loginUser.avatar.image.height}
                                    className="w-full h-full object-cover"
                                />
                            ) : (
                                <div
                                    dangerouslySetInnerHTML={{
                                        __html: iconAvatar(
                                            loginUser?.avatar?.seed ?? '',
                                        ).toString(),
                                    }}
                                />
                            )}</div>
                        <div className="text-sm">{loginUser.name}</div>
                    </Link>
                )}
                <button
                    onClick={logout}
                    className="py-3 px-4 flex-1 flex items-center gap-x-2 transition-colors hover:bg-gray-light ">
                    <LogOut strokeWidth={1.5} className="w-5 h-5" />
                    ログアウト
                </button>
            </div>
            <div className="py-3 flex flex-col">
                {navigationItems.map((v, idx) => (
                    <Link
                        key={idx}
                        href={formattedLink(v.link)}
                        className={`py-3 px-4 flex-1 transition-colors hover:bg-gray-light ${pathname === v.link ? 'pointer-events-none' : ''}`}>
                        <div
                            className={`flex items-center gap-x-2 whitespace-nowrap ${pathname === v.link ? 'text-primary-main' : 'text-black'} `}>
                            <NavigationIcon
                                iconType={v.iconType}
                                isCurrentPage={pathname === v.link}
                                className="w-5 h-5"
                            />
                            {v.name}
                        </div>
                    </Link>
                ))}
            </div>
        </div>
    );
};

export default SideNavigation;
