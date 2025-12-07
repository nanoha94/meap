'use client';
import React from 'react';
import { FormProvider } from 'react-hook-form';
import { IRecipe } from '@/types/api';
import { Button } from '@/components/common';
import CategoryEditFields from './CategoryEditFields';
import { VerticalRowField } from '@/components/react-hook-form';
import { useRouter } from 'next/navigation';
import { IngredientEditFields } from './IngredientEditFields';
import { useRecipeEditForm } from '../../hooks/useRecipeEditForm';
import { StepEditFields } from './StepEditFields';
import { EDIT_MODE } from '@/constants';
import ImageEditField from '@/components/react-hook-form/ImageEditField';

interface Props {
    fetchRecipe?: IRecipe;
}

const RecipeEditForm = ({ fetchRecipe }: Props) => {
    const router = useRouter();
    const {
        control,
        methods,
        isDisabledSendButton,
        editMode,
        onSubmit,
        errors,
    } = useRecipeEditForm(fetchRecipe);

    return (
        <FormProvider {...methods}>
            <form
                onSubmit={onSubmit}
                className="p-5 pb-[60px] grid grid-cols-[repeat(auto-fill,_minmax(350px,_1fr))] gap-x-10 gap-y-5 md:px-10">
                <div className="flex-1 flex flex-col gap-y-5">
                    {/* サムネイル画像 */}
                    <ImageEditField control={control} name="thumbnail" />
                    {/* 料理名 */}
                    <VerticalRowField
                        control={control}
                        name="name"
                        label="料理名"
                        required={true}>
                        {({ value, onChange }) => (
                            <input
                                type="text"
                                value={(value as string) ?? ''}
                                placeholder="料理名を入力"
                                onChange={e => onChange(e)}
                                className="py-2 px-4 border rounded-lg "
                            />
                        )}
                    </VerticalRowField>
                    {/* カテゴリー */}
                    <CategoryEditFields control={control} />
                    {/* TODO:分量目安 */}
                    {/* 食材 */}
                    <IngredientEditFields control={control} />
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
                                value={(value as string) ?? ''}
                                placeholder="レシピURLを入力"
                                onChange={e => onChange(e)}
                                className="py-2 px-4 border rounded-lg "
                            />
                        )}
                    </VerticalRowField>
                    {/* 手順 */}
                    <StepEditFields control={control} errors={errors} />
                    {/* メモ */}
                    <VerticalRowField
                        control={control}
                        name="memo"
                        label="メモ">
                        {({ value, onChange }) => (
                            <textarea
                                value={(value as string) ?? ''}
                                rows={5}
                                placeholder="メモを入力"
                                onChange={e => onChange(e)}
                                className="py-2 px-4 border rounded-lg"
                            />
                        )}
                    </VerticalRowField>
                    <div className="ml-auto mr-0 max-w-[200px] w-full flex gap-x-3">
                        <Button
                            type="button"
                            colorVariant="gray"
                            variant="outlined"
                            onClick={() => {
                                router.push(`/recipe/${fetchRecipe?.id}`);
                            }}>
                            戻る
                        </Button>
                        <Button
                            type="submit"
                            disabled={isDisabledSendButton ?? false}>
                            {editMode === EDIT_MODE.UPDATE ? '保存' : '追加'}
                        </Button>
                    </div>
                </div>
            </form>
        </FormProvider>
    );
};

export default RecipeEditForm;
