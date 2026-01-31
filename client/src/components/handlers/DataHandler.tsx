'use client';
import React from 'react';

import { useIngredientStore } from '@/models/ingredient';
import { useRecipeStore } from '@/models/recipe';
import { useAccountStore } from '@/models/settings';
import { useShoppingStore } from '@/models/shopping';
import { ILoginUser, IMaster } from '@/types';

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
    const { setLoginUser, setUsers } = useAccountStore();
    const { setCategories: setIngredientCategories, setUnits: setIngredientUnits } = useIngredientStore();
    const { setCategories: setRecipeCategories } = useRecipeStore();
    const { setCategories: setShoppingCategories } = useShoppingStore();

    /**
     * ユーザー情報をストアにセット
     * @param user ユーザー情報
     * @returns void
     */
    React.useEffect(() => {
        if (user) {
            setLoginUser(user);
        }
    }, [user]);

    /**
     * ユーザー一覧をストアにセット
     * @param users ユーザー一覧
     * @returns void
     */
    React.useEffect(() => {
        if (masterData?.users && masterData.users.length > 0) {
            setUsers(masterData.users);
        }
    }, [masterData?.users]);

    /**
     * レシピカテゴリーをストアにセット
     * @param fetchCategories レシピカテゴリー
     * @returns void
     */
    React.useEffect(() => {
        if (masterData?.recipeCategories && masterData.recipeCategories.length > 0) {
            setRecipeCategories(masterData.recipeCategories);
        }
    }, [masterData?.recipeCategories]);


    /**
     * 食材カテゴリーをストアにセット
     * @param fetchCategories 食材カテゴリー
     * @returns void
     */
    React.useEffect(() => {
        if (masterData?.ingredientCategories && masterData.ingredientCategories.length > 0) {
            setIngredientCategories(masterData.ingredientCategories);
        }
    }, [masterData?.ingredientCategories]);

    /**
     * 食材単位をストアにセット
     * @param fetchUnits 食材単位
     * @returns void
     */
    React.useEffect(() => {
        if (masterData?.ingredientUnits && masterData.ingredientUnits.length > 0) {
            setIngredientUnits(masterData.ingredientUnits);
        }
    }, [masterData?.ingredientUnits]);

    /**
     * 買い物カテゴリーをストアにセット
     * @param fetchCategories 買い物カテゴリー
     * @returns void
     */
    React.useEffect(() => {
        if (masterData?.shoppingCategories && masterData.shoppingCategories.length > 0) {
            setShoppingCategories(masterData.shoppingCategories);
        }
    }, [masterData?.shoppingCategories]);


    return <></>;
};

export default DataHandler;
