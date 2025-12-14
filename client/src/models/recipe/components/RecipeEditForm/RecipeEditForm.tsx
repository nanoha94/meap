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
import { colors, EDIT_MODE } from '@/constants';
import ImageEditField from '@/components/react-hook-form/ImageEditField';
import { Copy } from 'lucide-react';
import { useTextCopy } from '@/hooks/useTextCopy';

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
    const { isTextCopied, copyToClipboard } = useTextCopy();

    return (
        <FormProvider {...methods}>
            <form
                onSubmit={onSubmit}
                className="p-5 pb-[60px] max-w-[1000px] mx-auto grid grid-cols-1 gap-x-10 gap-y-5 md:px-10 md:grid-cols-2">
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
                    {/* 分量目安 */}
                    <VerticalRowField
                        control={control}
                        name="servingCount"
                        label="分量目安">
                        {({ value, onChange }) => (
                            <div className="flex items-center gap-x-2">
                                <input
                                    type="number"
                                    value={(value as string) ?? ''}
                                    min={1}
                                    placeholder="分量目安を入力"
                                    onChange={e => onChange(e)}
                                    className="py-2 px-4 flex-1 border rounded-lg"
                                />
                                人分
                            </div>
                        )}
                    </VerticalRowField>
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
                            <div className="flex flex-col gap-y-2">
                                <div className="flex items-center gap-x-2">
                                    <input
                                        type="text"
                                        value={(value as string) ?? ''}
                                        placeholder="レシピURLを入力"
                                        onChange={e => onChange(e)}
                                        className="py-2 px-4 flex-1 border rounded-lg "
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            copyToClipboard(value as string)
                                        }
                                        className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0  disabled:cursor-default"
                                        disabled={
                                            !value ||
                                            value === '' ||
                                            value?.toString()?.length <= 0
                                        }>
                                        <Copy
                                            size={28}
                                            color={colors.primary.main}
                                        />
                                    </button>
                                </div>
                                {isTextCopied && (
                                    <div className="min-h-[1.5rem]">
                                        <p className="text-alert-main">
                                            レシピURLをコピーしました
                                        </p>
                                    </div>
                                )}
                            </div>
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
