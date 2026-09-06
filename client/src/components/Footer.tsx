import React from 'react';
import Link from 'next/link';

import { LINK_TO } from '@/constants';

const Footer = () => {
    return (
        <footer
            className="bg-white py-8"
            style={{ boxShadow: 'inset 0 1px 3px 0 rgba(0, 0, 0, 10%)' }}>
            <div className="flex justify-center px-4 sm:px-6">
                <div className="flex w-full max-w-5xl flex-col items-center gap-3 sm:flex-row sm:justify-center sm:gap-6">
                    <nav
                        aria-label="フッターナビゲーション"
                        className="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm">
                        <Link
                            href={LINK_TO.TERMS}
                            className="text-primary-main underline transition-opacity hover:text-opacity-70">
                            利用規約
                        </Link>
                        <Link
                            href={LINK_TO.PRIVACY}
                            className="text-primary-main underline transition-opacity hover:text-opacity-70">
                            プライバシーポリシー
                        </Link>
                        <Link
                            href={LINK_TO.LEGAL.COMMERCIAL}
                            className="text-primary-main underline transition-opacity hover:text-opacity-70">
                            特定商取引法に基づく表記
                        </Link>
                    </nav>
                    <p className="text-sm text-gray-main">
                        © {new Date().getFullYear()} meap
                    </p>
                </div>
            </div>
        </footer>
    );
};

export default Footer;
