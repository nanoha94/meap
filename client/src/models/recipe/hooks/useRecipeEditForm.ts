import React from 'react';
import { IPostPutRecipeRequest, IRecipe } from '@/types/api';
import { RecipeEditFormData } from '../types';
import { useRecipeApi } from './useRecipeApi';
import { useForm, useWatch } from 'react-hook-form';
import { defaultPostData } from '../constants';
import { EDIT_MODE } from '@/constants';
import { formatIngredientItems } from '../utils';

export const useRecipeEditForm = (fetchRecipe?: IRecipe) => {
    const [errors, setErrors] = React.useState<Record<string, string> | null>(
        null,
    );
    const methods = useForm<RecipeEditFormData>({
        defaultValues: { ...defaultPostData, ...fetchRecipe },
    });
    const { control, handleSubmit } = methods;
    const { storeRecipe, updateRecipe } = useRecipeApi();
    const watchedName = useWatch({ control, name: 'name' });
    const watchedSteps = useWatch({ control, name: 'steps' });
    const editMode: (typeof EDIT_MODE)[keyof typeof EDIT_MODE] = fetchRecipe
        ? EDIT_MODE.UPDATE
        : EDIT_MODE.CREATE;

    /**
     * 送信ボタンの無効化判定
     * 料理名が空の場合は送信ボタンを無効化
     */
    const isDisabledSendButton: boolean = React.useMemo(() => {
        if (watchedName?.length <= 0) {
            return true;
        }
        if (errors && Object.values(errors).some(v => v !== '')) {
            return true;
        }
        return false;
    }, [watchedName, errors]);

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: RecipeEditFormData) => {
        console.log({ data });
        const sendData: IPostPutRecipeRequest = {
            name: data.name,
            url: data.url,
            memo: data.memo,
            thumbnailId: data.thumbnail?.id,
            categoryIds: data.categories.map(v => v.id),
            ingredients: formatIngredientItems(data.ingredients),
            // stepsはstoreRecipe()/updateRecipe()でフォーマットする
        };

        console.log({ sendData });

        if (editMode === EDIT_MODE.CREATE) {
            storeRecipe(sendData, data.thumbnail?.file ?? null, data.steps);
        } else {
            updateRecipe(
                { ...sendData, id: data.id },
                data.thumbnail?.file ?? null,
                data.steps,
            );
        }
    };

    React.useEffect(() => {
        if (watchedSteps?.length > 0) {
            watchedSteps.forEach((item, index) => {
                if (item.instruction === '' && item.image?.src.length !== 0) {
                    setErrors({
                        [`steps.${index}`]: '説明文を入力してください',
                    });
                } else {
                    setErrors({
                        [`steps.${index}`]: '',
                    });
                }
            });
        }
    }, [watchedSteps]);

    return {
        control,
        methods,
        editMode,
        isDisabledSendButton,
        onSubmit: handleSubmit(onSubmit),
        errors,
    };
};
