import { useSnackbars } from '@/contexts';
import { useRouter } from 'next/navigation';
import { useRecipeStore } from './recipeStores';
import axios from '@/lib/axios';
import { timeout_ms } from '@/constants';
import React from 'react';

export const useRecipes = () => {
    const { isLoadings, setIsLoadings } = useRecipeStore();
    const router = useRouter();
    const { addSnackbar } = useSnackbars();

    const storeRecipe = React.useCallback(async (formData: FormData) => {
        if (isLoadings.recipe) {
            return;
        }

        try {
            setIsLoadings('recipe', true);
            const res = await axios.post(`/recipes`, formData, {
                timeout: timeout_ms,
            });
            if (res.data) {
                addSnackbar(
                    'success',
                    `買い物リストに${formData.get('name')}を追加しました`,
                );
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
    };
};
