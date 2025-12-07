import { useSnackbars } from '@/contexts';
import { useRouter } from 'next/navigation';
import { useRecipeStore } from './recipeStores';
import axios from '@/lib/axios';
import { TIMEOUT_MS, TMP_ID_PREFIX } from '@/constants';
import React from 'react';
import {
    IPostPutRecipeRequest,
    IPostRecipeResponse,
    IRecipeStep,
} from '@/types/api';
import { useImageApi } from '@/models/image/hooks/useImageApi';
import { RecipeStepEditFormData } from '../types';

/**
 * 手順をフォーマット
 * @param items 手順リスト
 * @returns フォーマットされた手順
 */
export const formatStepItems = (
    items: IRecipeStep[],
): IPostPutRecipeRequest['steps'] => {
    return items
        .filter(v => v.instruction && v.instruction.length > 0)
        .map((v, idx) => {
            const isNew = v.id?.startsWith(TMP_ID_PREFIX.RECIPE_STEP);
            return {
                ...(v.id && !isNew ? { id: v.id } : {}),
                instruction: v.instruction,
                imageId: v.image?.id,
                order: idx,
            };
        });
};

export const useRecipeApi = () => {
    const { isLoadings, setIsLoadings } = useRecipeStore();
    const { bulkUploadImage } = useImageApi();
    const router = useRouter();
    const { addSnackbar } = useSnackbars();

    /**
     * 手順画像のアップロード
     * @param steps 手順リスト
     * @returns アップロードされた手順リスト
     * @description
     * 手順画像をアップロードし、アップロードされた画像IDを反映した新しい手順リストを返す
     */
    const uploadStepImages = React.useCallback(
        async (steps: RecipeStepEditFormData[]) => {
            // 新規アップロードが必要なファイルとそのステップインデックスを取得
            const filesToUpload: { file: File; stepIndex: number }[] = [];
            steps.forEach((step, index) => {
                if (step.image?.file) {
                    filesToUpload.push({
                        file: step.image.file,
                        stepIndex: index,
                    });
                }
            });

            // アップロードされた画像IDをマッピング
            const uploadedImageMap = new Map<number, string>();
            if (filesToUpload.length > 0) {
                // 画像アップロード
                const images = await bulkUploadImage(
                    filesToUpload.map(v => v.file),
                );
                if (images.success) {
                    filesToUpload.forEach((item, uploadIndex) => {
                        const imageId = images.data[uploadIndex]?.id;
                        if (imageId) {
                            uploadedImageMap.set(item.stepIndex, imageId);
                        }
                    });
                }
            }

            // アップロードされた画像IDを反映した新しい配列を作成
            const stepsWithImageIds = steps.map((step, index) => ({
                ...step,
                image: step.image
                    ? {
                          ...step.image,
                          id: uploadedImageMap.get(index) ?? step.image.id,
                      }
                    : null,
            }));

            return stepsWithImageIds;
        },
        [],
    );

    const storeRecipe = React.useCallback(
        async (
            data: IPostPutRecipeRequest,
            thumbnail: File | null,
            steps: RecipeStepEditFormData[],
        ) => {
            const sendData: IPostPutRecipeRequest = data;

            if (isLoadings.recipe) {
                return;
            }

            try {
                setIsLoadings('recipe', true);

                // サムネイル画像のアップロード
                if (thumbnail) {
                    const images = await bulkUploadImage([thumbnail]);
                    if (images.success) {
                        sendData.thumbnailId = images.data[0]?.id;
                    }
                }

                // 手順画像のアップロード
                if (steps.length > 0) {
                    const stepsWithImageIds = await uploadStepImages(steps);
                    sendData.steps = formatStepItems(stepsWithImageIds);
                }

                // APIリクエスト
                const res = await axios.post<IPostRecipeResponse>(
                    `/recipes`,
                    sendData,
                    {
                        timeout: TIMEOUT_MS,
                    },
                );

                // レスポンスデータ
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
        async (
            data: IPostPutRecipeRequest,
            thumbnail: File | null,
            steps: RecipeStepEditFormData[],
        ) => {
            const sendData: IPostPutRecipeRequest = data;
            if (isLoadings.recipe) {
                return;
            }

            try {
                setIsLoadings('recipe', true);

                // サムネイル画像のアップロード
                if (thumbnail) {
                    const images = await bulkUploadImage([thumbnail]);
                    if (images.success) {
                        sendData.thumbnailId = images.data[0]?.id;
                    }
                }

                // 手順画像のアップロード
                if (steps.length > 0) {
                    const stepsWithImageIds = await uploadStepImages(steps);
                    sendData.steps = formatStepItems(stepsWithImageIds);
                }

                // APIリクエスト
                const res = await axios.put(`/recipes/${data.id}`, sendData, {
                    timeout: TIMEOUT_MS,
                });

                // レスポンスデータ
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
