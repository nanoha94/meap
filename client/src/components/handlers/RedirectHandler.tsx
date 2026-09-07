'use client';

import { useEffect } from 'react';

import { clearRedirectCookie } from '@/actions/clearRedirectCookie';
import { isSafeRedirectPath } from '@/utils/redirectPath';

interface Props {
    redirectPath: string;
}

const RedirectHandler = ({ redirectPath }: Props) => {
    useEffect(() => {
        if (!isSafeRedirectPath(redirectPath)) {
            return;
        }

        clearRedirectCookie().then(() => {
            window.location.href = redirectPath;
        });
    }, [redirectPath]);

    return <></>;
};

export default RedirectHandler;
