import React from 'react';
import { MAX_IMAGE_SIZE } from '@/constants';
import { IIngredientItem } from '@/types/api/ingredient';
import { IPostPutRecipeRequest, IRecipe } from '@/types/api/recipe';
import { Thumbnail } from '../types';

export const useRecipeEditForm = (fetchRecipe?: IRecipe) => {
    const [errorMessage, setErrorMessage] = React.useState<string | null>(null);
    const [thumbnailState, setThumbnailState] = React.useState<Thumbnail>({
        file: null,
        src: fetchRecipe?.thumbnail?.src ?? '',
        width: fetchRecipe?.thumbnail?.width ?? 0,
        height: fetchRecipe?.thumbnail?.height ?? 0,
    });

    /**
     * サムネイル画像を変更
     * @param file 画像ファイル
     * @returns void
     */
    const onChangeThumbnail = (file: File | null) => {
        // エラーを解除
        setErrorMessage(null);

        // 画像サイズが大きすぎる場合はエラーを表示して画像を削除
        // TODO: 画像アップロードとレシピ作成リクエストでAPIを分ける
        if (file && file.size > MAX_IMAGE_SIZE) {
            setErrorMessage(
                `画像サイズが大きすぎます（最大${MAX_IMAGE_SIZE / 1024 / 1024}MB）`,
            );
            return;
        }

        // 画像を設定
        if (file) {
            const objectUrl = URL.createObjectURL(file);
            const img = new window.Image();

            img.onload = () => {
                // 古いobjectURLを解放
                if (thumbnailState.file && thumbnailState.src) {
                    URL.revokeObjectURL(thumbnailState.src);
                }
                setThumbnailState({
                    file,
                    src: objectUrl,
                    width: img.width,
                    height: img.height,
                });
            };
            img.src = objectUrl;
        }
    };

    /**
     * サムネイル画像を削除
     * @returns void
     */
    const onDeleteThumbnail = () => {
        // エラーを解除
        setErrorMessage(null);

        // 古いobjectURLを解放
        if (thumbnailState.file && thumbnailState.src) {
            URL.revokeObjectURL(thumbnailState.src);
        }
        setThumbnailState({
            file: null,
            src: '',
            width: 0,
            height: 0,
        });
        // if (fileInputRef.current) {
        //     fileInputRef.current.value = '';
        // }
        // setValue('thumbnail', null);
    };

    /**
     * 食材をフォーマット
     * @param items 食材
     * @param prefix 食材IDのプレフィックス
     * @returns フォーマットされた食材
     */
    const formatIngredientItems = React.useCallback(
        (
            items: IIngredientItem[],
            prefix: string,
        ): IPostPutRecipeRequest['ingredients'] => {
            return items
                .filter(v => v.name && v.name.length > 0)
                .map((v, idx) =>
                    v.id?.startsWith(prefix)
                        ? {
                              name: v.name,
                              quantity: v.quantity,
                              unitId: v.unit?.id ?? '',
                              categoryId: v.categoryId,
                              order: idx,
                          }
                        : { ...v, unitId: v.unit?.id ?? '', order: idx },
                );
        },
        [],
    );

    return {
        errorMessage,
        thumbnailState,
        onChangeThumbnail,
        onDeleteThumbnail,
        formatIngredientItems,
    };
};
