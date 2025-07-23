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
    const {
        setCategories: SetRecipeCategories,
        setIngredientUnits,
        setSeasoningUnits,
    } = useRecipeStore();

    React.useEffect(() => {
        if (user) {
            setLoginUser(user);
        }
        SetShoppingCategories(masterData.shoppingCategories);
        SetRecipeCategories(masterData.recipeCategories);
        setIngredientUnits(masterData.ingredientUnits);
        setSeasoningUnits(masterData.seasoningUnits);
    }, [user, masterData]);

    return <></>;
};

export default DataHandler;
