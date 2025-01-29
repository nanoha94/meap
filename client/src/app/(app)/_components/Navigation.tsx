import Link from 'next/link';
import { usePathname } from 'next/navigation';
import React from 'react';
import { BookOpenCheck, CalendarDate, CookingPot, Settings } from './svg';
import { colors } from '@/constants/colors';

const Navigation = () => {
    const pathname = usePathname();
    const items: { link: string; name: string; icon: React.ReactNode }[] = [
        {
            link: '/plan',
            name: '献立表',
            icon: (
                <CalendarDate
                    strokeColor={
                        pathname === '/plan'
                            ? colors.primary.main
                            : colors.black
                    }
                    fillColor={
                        pathname === '/plan'
                            ? colors.primary.light
                            : colors.gray.iconFill
                    }
                />
            ),
        },
        {
            link: '/dishes',
            name: '料理/レシピ',
            icon: (
                <CookingPot
                    strokeColor={
                        pathname === '/dishes'
                            ? colors.primary.main
                            : colors.black
                    }
                    fillColor={
                        pathname === '/dishes'
                            ? colors.primary.light
                            : colors.gray.iconFill
                    }
                />
            ),
        },
        {
            link: '/shopping-lists',
            name: '買い物リスト',
            icon: (
                <BookOpenCheck
                    strokeColor={
                        pathname === '/shopping-lists'
                            ? colors.primary.main
                            : colors.black
                    }
                    fillColor={
                        pathname === '/shopping-lists'
                            ? colors.primary.light
                            : colors.gray.iconFill
                    }
                />
            ),
        },
        {
            link: '/settings',
            name: '設定',
            icon: (
                <Settings
                    strokeColor={
                        pathname === '/settings'
                            ? colors.primary.main
                            : colors.black
                    }
                    fillColor={
                        pathname === '/settings'
                            ? colors.primary.light
                            : colors.gray.iconFill
                    }
                />
            ),
        },
    ];

    return (
        <div
            className="w-full flex "
            style={{ boxShadow: 'inset 0 1px 2px rgba(0, 0, 0, 10%)' }}>
            {items.map((v, idx) => (
                <Link
                    key={idx}
                    href={v.link}
                    className={`py-2 px-0.5 flex-1 transition-colors hover:bg-gray-ligh ${pathname === v.link ? 'pointer-events-none' : ''}`}>
                    <div className="relative mx-auto w-16 h-auto aspect-square rounded-full transition-colors hover:bg-gray-light">
                        <div
                            className={`absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 flex flex-col items-center gap-y-0.5 text-xs font-bold whitespace-nowrap ${pathname === v.link ? 'text-primary-main' : 'text-black'} `}>
                            {v.icon}
                            {v.name}
                        </div>
                    </div>
                </Link>
            ))}
        </div>
    );
};

export default Navigation;
