import React from 'react';
import Link from 'next/link';

import { BUTTON_VARIANT, ButtonVariant, COLOR_VARIANT, PRIMARY_BUTTON_COLOR_CLASS } from '@/constants';


type Props = {
    href: string;
    variant?: ButtonVariant;
    colorVariant?:
    | (typeof COLOR_VARIANT)['PRIMARY']
    | (typeof COLOR_VARIANT)['GRAY']
    | (typeof COLOR_VARIANT)['ALERT'];
    className?: string;
    children: React.ReactNode;
    isExternal?: boolean;
    /** 外部リンク（isExternal）を新しいタブで開くか。同一タブ完結させたいときは false。 */
    openInNewTab?: boolean;
};

const ButtonLink = ({
    href,
    variant = BUTTON_VARIANT.FILLED,
    colorVariant = COLOR_VARIANT.PRIMARY,
    className,
    children,
    isExternal = false,
    openInNewTab = true,
}: Props) => {
    const linkClassName = `${className ?? ''} block text-center appearance-none p-3 w-full font-bold rounded-lg transition-colors ${PRIMARY_BUTTON_COLOR_CLASS[variant][colorVariant]}`.trim();

    if (isExternal) {
        return (
            <a
                href={href}
                className={linkClassName}
                {...(openInNewTab
                    ? ({ target: '_blank', rel: 'noopener noreferrer' } as const)
                    : {})}
            >
                {children}
            </a>
        );
    }

    return (
        <Link
            href={href}
            className={linkClassName}
        >
            {children}
        </Link>
    );
};

export default ButtonLink;
