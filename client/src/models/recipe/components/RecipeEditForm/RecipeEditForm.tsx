'use client';
import { useForm } from 'react-hook-form';
import IngredientEditFields from './IngredientEditFields';
import {
    IIngredient,
    IPostRecipeRequest,
    ISeasoning,
} from '@/types/api/recipe';
import { Button } from '@/components/common';
import { defaultPostData } from '../../constants';
import SeasoningEditFields from './SeasoningEditFields';
import React from 'react';
import CategoryEditFields from './CategoryEditFields';
import { VerticalRowField } from '@/components/react-hook-form';
import ThumbnailEditField from './ThumbnailEditField';
import { useRecipes } from '../../hooks/useRecipe';

type FormData = IPostRecipeRequest;

const RecipeEditForm = () => {
    const { storeRecipe } = useRecipes();
    const { control, handleSubmit } = useForm<FormData>({
        defaultValues: defaultPostData,
    });

    const formatItems = (items: IIngredient[] | ISeasoning[]) =>
        items
            .filter(v => v.name && v.name.length > 0)
            .map(v =>
                (v.id ?? '').length > 0
                    ? v
                    : {
                          name: v.name,
                          quantity: v.quantity ?? '',
                          unitId: v.unitId ?? '',
                      },
            );

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: FormData) => {
        const formData = new FormData();
        const sendData = {
            ...data,
            ingredients: formatItems(data.ingredients as IIngredient[]),
            seasonings: formatItems(data.seasonings as ISeasoning[]),
        };

        // サムネイル画像
        if (
            sendData.thumbnailImage &&
            sendData.thumbnailImage instanceof File
        ) {
            formData.append('thumbnailImage', sendData.thumbnailImage);
        }

        // 他のフィールド
        formData.append('name', sendData.name);
        formData.append('url', sendData.url ?? '');
        formData.append('instructions', sendData.instructions ?? '');
        formData.append('memo', sendData.memo ?? '');
        formData.append('categories', JSON.stringify(sendData.categories));
        formData.append('ingredients', JSON.stringify(sendData.ingredients));
        formData.append('seasonings', JSON.stringify(sendData.seasonings));

        storeRecipe(formData);
    };

    return (
        <form
            onSubmit={handleSubmit(onSubmit)}
            className="p-5 pb-[60px] grid grid-cols-[repeat(auto-fill,_minmax(350px,_1fr))] gap-x-10 gap-y-5 md:px-10">
            <div className="flex-1 flex flex-col gap-y-5">
                {/* サムネイル画像 */}
                <ThumbnailEditField control={control} />
                {/* 料理名 */}
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
            <div className="flex-1 flex flex-col gap-y-5">
                {/* レシピURL */}
                <VerticalRowField
                    control={control}
                    name="url"
                    label="レシピURL">
                    {({ value, onChange }) => (
                        <input
                            type="text"
                            value={value as string}
                            placeholder="レシピURLを入力"
                            onChange={e => onChange(e)}
                            className="py-2 px-4 text-base border rounded-lg "
                        />
                    )}
                </VerticalRowField>
                {/* レシピ（テキスト入力） */}
                <VerticalRowField
                    control={control}
                    name="instructions"
                    label="レシピ（テキスト入力）">
                    {({ value, onChange }) => (
                        <textarea
                            value={value as string}
                            rows={5}
                            placeholder="レシピを入力"
                            onChange={e => onChange(e)}
                            className="py-2 px-4 text-base border rounded-lg"
                        />
                    )}
                </VerticalRowField>
                {/* メモ */}
                <VerticalRowField control={control} name="memo" label="メモ">
                    {({ value, onChange }) => (
                        <textarea
                            value={value as string}
                            rows={5}
                            placeholder="メモを入力"
                            onChange={e => onChange(e)}
                            className="py-2 px-4 text-base border rounded-lg"
                        />
                    )}
                </VerticalRowField>
                <div className="ml-auto mr-0 max-w-[200px] w-full flex gap-x-3">
                    <Button
                        type="button"
                        colorVariant="gray"
                        variant="outlined"
                        onClick={() => {}}>
                        戻る
                    </Button>
                    <Button type="submit">追加</Button>
                </div>
            </div>
        </form>
    );
};

export default RecipeEditForm;
