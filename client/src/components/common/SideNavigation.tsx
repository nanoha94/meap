'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import React from 'react';
import { navigationItems } from '@/constants';
import NavigationIcon from './NavigationIcon';
import { useAuth } from '@/hooks/api';
import { LogOut } from 'lucide-react';
import ApplicationLogo from '../ApplicationLogo';
import { IGetUserResponse } from '@/types/api';
import { useAccountHandlers } from '@/models/settings/hooks/useAccountHandlers';

interface Props {
    user: IGetUserResponse;
    className?: string;
}

const SideNavigation = ({ user, className }: Props) => {
    const { logout } = useAuth();
    const { iconAvatar } = useAccountHandlers();
    const pathname = usePathname();

    if (!pathname) {
        return <></>;
    }

    return (
        <div
            className={`fixed top-0 left-0 w-[160px] h-full bg-white ${className ? className : ''}`}
            style={{ boxShadow: '5px 0 8px 0 rgba(0, 0, 0, 10%)' }}>
            <div className="py-3 flex flex-col border-b border-gray-border">
                <Link href="/" className="w-fit mx-auto block">
                    <ApplicationLogo className="w-[100px] h-auto fill-current text-gray-500" />
                </Link>
            </div>
            <div className="py-3 flex flex-col border-b border-gray-border">
                {user && (
                    <Link
                        href="/settings/account"
                        key={user.avatar_seed}
                        className="py-2 px-3 w-full mx-auto flex flex-col items-center gap-y-1 transition-colors hover:bg-gray-light ">
                        {/* TODO: アイコンの指定がある場合はアイコン、指定がない場合はiconsを使用する */}
                        <div
                            className="w-14 h-auto aspect-square rounded-full overflow-hidden"
                            dangerouslySetInnerHTML={{
                                __html: iconAvatar(
                                    user.avatar_seed ?? '',
                                ).toString(),
                            }}
                        />
                        <div className="text-sm">{user.name}</div>
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
                        href={v.link}
                        className={`py-3 px-4 flex-1 transition-colors hover:bg-gray-light ${pathname === v.link ? 'pointer-events-none' : ''} `}>
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
