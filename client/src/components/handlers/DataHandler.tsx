'use client';
import React from 'react';

import { useIngredientStore } from '@/models/ingredient';
import { useRecipeStore } from '@/models/recipe';
import { useUserStore } from '@/models/user';
import { useShoppingStore } from '@/models/shopping';
import { ILoginUser, IMaster } from '@/types';
import { useMealStore } from '@/models/meal';

interface Props {
    user: ILoginUser;
    masterData: IMaster | null;
}

/**
 * データをストアにセット
 * @param user ユーザー情報
 * @param masterData マスターデータ
 * @returns void
 */
const DataHandler = ({ user, masterData }: Props) => {
    // store
    const setIngredientCategories = useIngredientStore(state => state.setCategories);
    const setIngredientUnits = useIngredientStore(state => state.setUnits);
    const setRecipeCategories = useRecipeStore(state => state.setCategories);
    const setMealCategories = useMealStore(state => state.setMealCategories);
    const setShoppingCategories = useShoppingStore(state => state.setCategories);
    const setLoginUser = useUserStore(state => state.setLoginUser);
    const setUsers = useUserStore(state => state.setUsers);

    /**
     * ユーザー情報をストアにセット
     */
    React.useEffect(() => {
        if (user) {
            setLoginUser(user);
        }
    }, [user, setLoginUser]);

    /**
     * ユーザー一覧をストアにセット
     */
    React.useEffect(() => {
        if (masterData?.users && masterData.users.length > 0) {
            setUsers(masterData.users);
        }
    }, [masterData?.users, setUsers]);

    /**
     * レシピカテゴリーをストアにセット
     */
    React.useEffect(() => {
        if (masterData?.recipeCategories && masterData.recipeCategories.length > 0) {
            setRecipeCategories(masterData.recipeCategories);
        }
    }, [masterData?.recipeCategories, setRecipeCategories]);


    /**
     * 食材カテゴリーをストアにセット
     */
    React.useEffect(() => {
        if (masterData?.ingredientCategories && masterData.ingredientCategories.length > 0) {
            setIngredientCategories(masterData.ingredientCategories);
        }
    }, [masterData?.ingredientCategories, setIngredientCategories]);

    /**
     * 食材単位をストアにセット
     */
    React.useEffect(() => {
        if (masterData?.ingredientUnits && masterData.ingredientUnits.length > 0) {
            setIngredientUnits(masterData.ingredientUnits);
        }
    }, [masterData?.ingredientUnits, setIngredientUnits]);

    /**
     * 献立カテゴリ―をストアにセット
     */
    React.useEffect(() => {
        if (masterData?.mealCategories && masterData.mealCategories.length > 0) {
            setMealCategories(masterData.mealCategories);
        }
    }, [masterData?.mealCategories, setMealCategories]);

    /**
     * 買い物カテゴリーをストアにセット
     */
    // eslint-disable-next-line no-console -- デバッグ用（問題解決後は削除）
    console.log('[DataHandler] masterData?.shoppingCategories:', masterData?.shoppingCategories);
    React.useEffect(() => {
        if (masterData?.shoppingCategories && masterData.shoppingCategories.length > 0) {
            setShoppingCategories(masterData.shoppingCategories);
        }
    }, [masterData?.shoppingCategories, setShoppingCategories]);


    return <></>;
};

export default DataHandler;
