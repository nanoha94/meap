'use client';
import React from 'react';
import { useForm, FormProvider } from 'react-hook-form';
import { IPostPutRecipeRequest, IRecipe } from '@/types/api/recipe';
import { Button } from '@/components/common';
import CategoryEditFields from './CategoryEditFields';
import { VerticalRowField } from '@/components/react-hook-form';
import ThumbnailEditField from './ThumbnailEditField';
import { useRouter } from 'next/navigation';
import { defaultPostData } from '../../constants';
import { IngredientEditFields } from './IngredientEditFields';
import { IIngredientItem } from '@/types/api/ingredient';
import { TMP_ID_PREFIX } from '@/constants/tmpIdPrefix';
import { useRecipes } from '../../hooks';
import { useRecipeEditForm } from '../../hooks/useRecipeEditForm';

type FormData = IRecipe;

interface Props {
    fetchRecipe?: IRecipe;
}

type EditMode = 'create' | 'update';

const RecipeEditForm = ({ fetchRecipe }: Props) => {
    const router = useRouter();
    const { storeRecipe, updateRecipe } = useRecipes();
    const {
        thumbnailState,
        errorMessage: thumbnailErrorMessage,
        onChangeThumbnail,
        onDeleteThumbnail,
        formatIngredientItems,
    } = useRecipeEditForm(fetchRecipe);
    const methods = useForm<FormData>({
        defaultValues: { ...defaultPostData, ...fetchRecipe },
    });

    const { control, handleSubmit, watch } = methods;
    const watchedName = watch('name');
    const watchedThumbnail = watch('thumbnail');
    const editMode: EditMode = fetchRecipe ? 'update' : 'create';

    /**
     * 送信ボタンの無効化判定
     * 料理名が空の場合、サムネイル画像が5MBを超える場合は送信ボタンを無効化
     */
    const isDisabledSendButton: boolean = React.useMemo(() => {
        if (watchedName?.length <= 0) {
            return true;
        }
        if (thumbnailErrorMessage && thumbnailErrorMessage.length > 0) {
            return true;
        }
        return false;
    }, [watchedName, watchedThumbnail]);

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: FormData) => {
        console.log({ data });
        const sendData: IPostPutRecipeRequest = {
            ...data,
            categoryIds: data.categories.map(v => v.id),
            ingredients: formatIngredientItems(
                data.ingredients as IIngredientItem[],
                TMP_ID_PREFIX.INGREDIENT_ITEM,
            ),
        };

        console.log({ sendData });

        if (editMode === 'create') {
            storeRecipe(sendData, thumbnailState.file);
        } else {
            updateRecipe(sendData, thumbnailState.file);
        }
    };

    return (
        <FormProvider {...methods}>
            <form
                onSubmit={handleSubmit(onSubmit)}
                className="p-5 pb-[60px] grid grid-cols-[repeat(auto-fill,_minmax(350px,_1fr))] gap-x-10 gap-y-5 md:px-10">
                <div className="flex-1 flex flex-col gap-y-5">
                    {/* サムネイル画像 */}
                    <ThumbnailEditField
                        control={control}
                        errorMessage={thumbnailErrorMessage}
                        thumbnail={thumbnailState}
                        onChangeThumbnail={onChangeThumbnail}
                        onDelete={onDeleteThumbnail}
                    />
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
                    {/* レシピ（テキスト入力） */}
                    <VerticalRowField
                        control={control}
                        name="steps"
                        label="レシピ（テキスト入力）">
                        {({ value, onChange }) => (
                            <textarea
                                value={(value as string) ?? ''}
                                rows={5}
                                placeholder="レシピを入力"
                                onChange={e => onChange(e)}
                                className="py-2 px-4 border rounded-lg"
                            />
                        )}
                    </VerticalRowField>
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
                            {editMode === 'update' ? '保存' : '追加'}
                        </Button>
                    </div>
                </div>
            </form>
        </FormProvider>
    );
};

export default RecipeEditForm;
