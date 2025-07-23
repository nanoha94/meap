'use client';
import { useForm } from 'react-hook-form';
import IngredientEditFields from './IngredientEditFields';
import { IPostRecipeRequest } from '@/types/api/recipe';
import { Button } from '@/components/common';
import { defaultPostData } from '../../constants';
import SeasoningEditFields from './SeasoningEditFields';
import React from 'react';

type FormData = IPostRecipeRequest;

const RecipeEditForm = () => {
    const { control, handleSubmit } = useForm<FormData>({
        defaultValues: defaultPostData,
    });

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: FormData) => {
        console.log(data);
    };
    return (
        <form onSubmit={handleSubmit(onSubmit)} className="py-5 px-10 flex">
            <div className="flex flex-col gap-y-5">
                <IngredientEditFields control={control} />
                <SeasoningEditFields control={control} />
            </div>
            <div>
                <Button type="submit">追加</Button>
            </div>
        </form>
    );
};

export default RecipeEditForm;
