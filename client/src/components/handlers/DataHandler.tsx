'use client';
import { useAccountStore } from '@/models/settings/hooks';
import { useShoppingStore } from '@/models/shopping/hooks';
import { IGetUserResponse } from '@/types/api';
import { IGetMasterResponse } from '@/types/api/master';
import React from 'react';

interface Props {
    user: IGetUserResponse;
    masterData: IGetMasterResponse;
}

const DataHandler = ({ user, masterData }: Props) => {
    const { setLoginUser } = useAccountStore();
    const { setCategories } = useShoppingStore();

    React.useEffect(() => {
        if (user) {
            setLoginUser(user);
        }
        if (masterData.shoppingCategories) {
            setCategories(masterData.shoppingCategories);
        }
    }, [user, masterData]);

    return <></>;
};

export default DataHandler;
