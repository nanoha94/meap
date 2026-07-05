'use client';

import { ChevronRight } from 'lucide-react';
import Link from 'next/link';

import { Header } from '@/components';
import { LINK_TO, colors } from '@/constants';
import { useAlertDialog, useAuth } from '@/hooks';
import { useUserApi } from '@/models/user';

type NavigationItemWithHref = {
    label: string;
    href: string;
    colorClass: string;
};

type NavigationItemWithOnClick = {
    label: string;
    onClick: () => void;
    colorClass: string;
};

type NavigationItem = NavigationItemWithHref | NavigationItemWithOnClick;

const SettingsPage = () => {
    const { deleteUser } = useUserApi();
    const { logout } = useAuth();
    const { openAlertDialog } = useAlertDialog();
    const navigationItems: NavigationItem[] = [
        {
            label: 'アカウント設定',
            href: LINK_TO.SETTINGS.ACCOUNT,
            colorClass: 'text-black'
        },
        {
            label: 'プラン管理',
            href: LINK_TO.SETTINGS.BILLING,
            colorClass: 'text-black'
        },
        {
            label: 'ログアウト',
            onClick: logout,
            colorClass: 'text-black'
        },
        {
            label: 'アカウント削除',
            onClick: () => openAlertDialog(
                {
                    title: 'アカウント削除',
                    message: ['アカウントを削除すると、\nアプリのデータが全て失われます。\nアカウントを削除しますか？'],
                    alertMessage: '',
                    actionButtonText: '削除',
                },
                deleteUser
            ),
            colorClass: 'text-alert-main'
        },
    ];

    const itemClassName: string = 'py-2 px-4 flex items-center justify-between gap-x-4 bg-white shadow-card transition-colors hover:bg-gray-light';

    return (
        <>
            <Header
                title="設定"
            />
            <main className='pt-5 pb-[60px] md:px-10  max-w-[1000px] mx-auto flex flex-col gap-y-4'>
                {navigationItems.map((item) =>
                    "href" in item ? (
                        <Link href={item.href} key={item.label} className={`${itemClassName} ${item.colorClass}`}>{item.label}<ChevronRight size={20} color={colors.gray.main} />
                        </Link>
                    ) : (
                        <button
                            type="button"
                            onClick={item.onClick}
                            key={item.label}
                            className={`${itemClassName} ${item.colorClass}`}
                        >{item.label}<ChevronRight size={20} color={colors.gray.main} />
                        </button>
                    )
                )}
            </main>
        </>
    );
};

export default SettingsPage;