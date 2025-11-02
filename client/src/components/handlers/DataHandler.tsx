'use client';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
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
    const { setCategories: SetShoppingCategories } = useShoppingStore();
    const { setCategories: SetRecipeCategories, setIngredientUnits } =
        useRecipeStore();

    console.log(masterData);

    React.useEffect(() => {
        if (user) {
            setLoginUser(user);
        }
        SetShoppingCategories(masterData.data.shoppingCategories);
        SetRecipeCategories(masterData.data.recipeCategories);
        setIngredientUnits(masterData.data.ingredientUnits);
    }, [user, masterData]);

    return <></>;
};

export default DataHandler;
