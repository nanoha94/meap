import { useSnackbars } from '@/contexts';
import { useRouter } from 'next/navigation';
import { useRecipeStore } from './recipeStores';
import axios from '@/lib/axios';
import { TIMEOUT_MS } from '@/constants';
import React from 'react';
import { IPostPutRecipeRequest } from '@/types/api/recipe';

export const useRecipes = () => {
    const { isLoadings, setIsLoadings } = useRecipeStore();
    const router = useRouter();
    const { addSnackbar } = useSnackbars();

    const storeRecipe = React.useCallback(
        async (data: IPostPutRecipeRequest) => {
            if (isLoadings.recipe) {
                return;
            }

            try {
                setIsLoadings('recipe', true);
                const res = await axios.post(`/recipes`, data, {
                    timeout: TIMEOUT_MS,
                });
                if (res.data) {
                    router.push('/recipe/');
                    addSnackbar('success', `${data.name}を追加しました`);
                }
            } catch (error) {
                if (error.code === 'ECONNABORTED') {
                    addSnackbar('error', 'リクエストがタイムアウトしました');
                } else {
                    console.error(error.response?.data.message);
                    addSnackbar('error', error.response?.data.message);
                }
            } finally {
                setIsLoadings('recipe', false);
            }
        },
        [],
    );

    const updateRecipe = React.useCallback(
        async (data: IPostPutRecipeRequest) => {
            if (isLoadings.recipe) {
                return;
            }

            try {
                setIsLoadings('recipe', true);
                const res = await axios.put(`/recipes/${data.id}`, data, {
                    timeout: TIMEOUT_MS,
                });
                if (res.data) {
                    router.push(`/recipe/${data.id}`);
                    addSnackbar('success', `${data.name}を更新しました`);
                }
            } catch (error) {
                if (error.code === 'ECONNABORTED') {
                    addSnackbar('error', 'リクエストがタイムアウトしました');
                } else {
                    console.error(error.response?.data.message);
                    addSnackbar('error', error.response?.data.message);
                }
            } finally {
                setIsLoadings('recipe', false);
            }
        },
        [],
    );

    const deleteRecipe = React.useCallback(async (id: string, name: string) => {
        if (isLoadings.recipe) {
            return;
        }

        try {
            setIsLoadings('recipe', true);
            const res = await axios.delete(`/recipes/${id}`);
            if (res.data) {
                addSnackbar('success', `${name}を削除しました`);
                router.push('/recipe/');
            }
        } catch (error) {
            if (error.code === 'ECONNABORTED') {
                addSnackbar('error', 'リクエストがタイムアウトしました');
            } else {
                console.error(error.response?.data.message);
                addSnackbar('error', error.response?.data.message);
            }
        } finally {
            setIsLoadings('recipe', false);
        }
    }, []);

    return {
        storeRecipe,
        updateRecipe,
        deleteRecipe,
    };
};
