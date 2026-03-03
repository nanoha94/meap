'use client';
import React from 'react';
import Link from 'next/link';

import { useUserStore } from '@/models/user';

const LoginLinks = () => {
    const loginUser = useUserStore(state => state.loginUser);

    return (
        <div className="fixed top-0 right-0 px-6 py-4">
            {loginUser ? (
                <Link
                    href="/plan"
                    className="ml-4 text-sm text-gray-700 underline">
                    Plan
                </Link>
            ) : (
                <>
                    <Link
                        href="/login"
                        className="text-sm text-gray-700 underline">
                        Login
                    </Link>

                    <Link
                        href="/register"
                        className="ml-4 text-sm text-gray-700 underline">
                        Register
                    </Link>
                </>
            )}
        </div>
    );
};

export default LoginLinks;
