import { useSnackbars } from '@/contexts';
import { useRouter } from 'next/navigation';
import { useRecipeStore } from './recipeStores';
import axios from '@/lib/axios';
import { TIMEOUT_MS } from '@/constants';
import React from 'react';
import { IPostPutRecipeRequest, IPostRecipeResponse } from '@/types/api/recipe';
import { useImageApi } from '@/models/image/hooks/useImageApi';

export const useRecipes = () => {
    const { isLoadings, setIsLoadings } = useRecipeStore();
    const { bulkUploadImage } = useImageApi();
    const router = useRouter();
    const { addSnackbar } = useSnackbars();

    const storeRecipe = React.useCallback(
        async (data: IPostPutRecipeRequest, thumbnail: File | null) => {
            const sendData: IPostPutRecipeRequest = data;

            if (isLoadings.recipe) {
                return;
            }

            try {
                setIsLoadings('recipe', true);

                if (thumbnail) {
                    const images = await bulkUploadImage([thumbnail]);
                    if (images.success) {
                        sendData.thumbnailId = images.data[0]?.id;
                    }
                }

                const res = await axios.post<IPostRecipeResponse>(
                    `/recipes`,
                    sendData,
                    {
                        timeout: TIMEOUT_MS,
                    },
                );
                const responseData: IPostRecipeResponse = res.data;
                if (responseData.success) {
                    router.push('/recipe/');
                    addSnackbar(
                        'success',
                        responseData.message ??
                            'リクエストが正常に完了しました',
                    );
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
        async (data: IPostPutRecipeRequest, thumbnail: File | null) => {
            const sendData: IPostPutRecipeRequest = data;
            if (isLoadings.recipe) {
                return;
            }

            try {
                setIsLoadings('recipe', true);

                if (thumbnail) {
                    const images = await bulkUploadImage([thumbnail]);
                    if (images.success) {
                        sendData.thumbnailId = images.data[0]?.id;
                    }
                }

                const res = await axios.put(`/recipes/${data.id}`, sendData, {
                    timeout: TIMEOUT_MS,
                });
                const responseData: IPostRecipeResponse = res.data;
                if (responseData.success) {
                    router.push(`/recipe/${data.id}`);
                    addSnackbar(
                        'success',
                        responseData.message ??
                            'リクエストが正常に完了しました',
                    );
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
