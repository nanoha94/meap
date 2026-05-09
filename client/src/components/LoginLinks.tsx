'use client';

import React from 'react';
import Link from 'next/link';

import { COLOR_VARIANT } from '@/constants';
import { useUserStore } from '@/models/user';
import { getLinkButtonClassName } from '@/utils';

const LoginLinks = () => {
    // store
    const loginUser = useUserStore(state => state.loginUser);

    return (
        <div className="flex gap-x-4">
            {loginUser?.id ? (
                <Link
                    href="/plan"
                    className={`${getLinkButtonClassName(COLOR_VARIANT.PRIMARY)} text-sm md:text-base`}
                >
                    マイ献立表へ
                </Link>
            ) : (
                <>

                    <Link
                        href="/register"
                        className={`${getLinkButtonClassName(COLOR_VARIANT.PRIMARY)} text-sm md:text-base`}
                    >
                        アカウント登録
                    </Link>
                    <Link
                        href="/login"
                        className={`${getLinkButtonClassName(COLOR_VARIANT.GRAY)} text-sm md:text-base`}
                    >
                        ログイン
                    </Link>
                </>
            )}
        </div>
    );
};

export default LoginLinks;
