'use client';
import { useAccountStore } from '@/models/settings/hooks';
import { IGetUserResponse } from '@/types/api';
import React from 'react';

interface Props {
    user: IGetUserResponse;
}

const UserHandler = ({ user }: Props) => {
    const { setLoginUser } = useAccountStore();

    React.useEffect(() => {
        setLoginUser(user);
    }, [user]);

    return <></>;
};

export default UserHandler;
