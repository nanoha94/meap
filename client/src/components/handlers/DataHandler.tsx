'use client';
import { useIngredientStore } from '@/models/ingredient/hooks';
import { useRecipeStore } from '@/models/recipe/hooks/recipeStores';
import { useAccountStore } from '@/models/settings/hooks';
import { IGetUserResponse, IGetMasterResponse } from '@/types/api';
import React from 'react';

interface Props {
    user: IGetUserResponse;
    masterData: IGetMasterResponse;
}

const DataHandler = ({ user, masterData }: Props) => {
    const { setLoginUser } = useAccountStore();
    const { setCategories: SetRecipeCategories } = useRecipeStore();
    const { setUnits: setIngredientUnits } = useIngredientStore();

    React.useEffect(() => {
        if (user) {
            setLoginUser(user);
        }
        SetRecipeCategories(masterData.data.recipeCategories);
        setIngredientUnits(masterData.data.ingredientUnits);
    }, [user, masterData]);

    return <></>;
};

export default DataHandler;
