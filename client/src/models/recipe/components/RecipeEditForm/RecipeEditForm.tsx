'use client';
import { useForm } from 'react-hook-form';
import IngredientEditFields from './IngredientEditFields';
import { IPostRecipeRequest } from '@/types/api/recipe';
import { Button } from '@/components/common';
import { defaultPostData } from '../../constants';
import SeasoningEditFields from './SeasoningEditFields';
import React from 'react';
import CategoryEditFields from './CategoryEditFields';
import { VerticalRowField } from '@/components/react-hook-form';

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
            <div className="flex-1 flex flex-col gap-y-5">
                <VerticalRowField
                    control={control}
                    name="name"
                    label="料理名"
                    required={true}>
                    {({ value, onChange }) => (
                        <input
                            type="text"
                            value={value as string}
                            placeholder="料理名を入力"
                            onChange={e => onChange(e)}
                            className="py-2 px-4 text-base border rounded-lg "
                        />
                    )}
                </VerticalRowField>
                {/* カテゴリー */}
                <CategoryEditFields control={control} />
                {/* 食材 */}
                <IngredientEditFields control={control} />
                {/* 調味料 */}
                <SeasoningEditFields control={control} />
            </div>
            <div className="flex-1">
                <Button type="submit">追加</Button>
            </div>
        </form>
    );
};

export default RecipeEditForm;
