'use client';
import { useForm } from 'react-hook-form';
import IngredientEditFields from './IngredientEditFields';
import { IPostRecipeRequest } from '@/types/api/recipe';
import { Button } from '@/components/common';
import { defaultPostData } from '../../constants';

type FormData = IPostRecipeRequest;

const RecipeEditForm = () => {
    const { control, watch, handleSubmit } = useForm<FormData>({
        defaultValues: defaultPostData,
    });

    /**
     * カテゴリ―、食材、調味料の監視
     */
    // const watchedCategories = watch('categories');
    const watchedIngredients = watch('ingredients');
    // const watchedSeasonings = watch('seasonings');

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
                <IngredientEditFields
                    ingredients={watchedIngredients ?? []}
                    control={control}
                />
            </div>
            <div>
                <Button type="submit">追加</Button>
            </div>
        </form>
    );
};

export default RecipeEditForm;
