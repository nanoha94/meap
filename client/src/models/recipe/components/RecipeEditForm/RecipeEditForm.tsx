'use client';
import { useForm, FormProvider } from 'react-hook-form';
import IngredientEditFields from './IngredientEditFields';
import {
    IIngredient,
    IPostRecipeRequest,
    IRecipe,
    ISeasoning,
} from '@/types/api/recipe';
import { Button } from '@/components/common';
import { defaultPostData, TMP_ID_PREFIX } from '../../constants';
import SeasoningEditFields from './SeasoningEditFields';
import React from 'react';
import CategoryEditFields from './CategoryEditFields';
import { VerticalRowField } from '@/components/react-hook-form';
import ThumbnailEditField from './ThumbnailEditField';
import { useRecipes } from '../../hooks/useRecipe';
import { MAX_IMAGE_SIZE } from '@/constants';

type FormData = IPostRecipeRequest;

interface Props {
    recipe?: IRecipe | null;
}

type EditMode = 'create' | 'update';

const RecipeEditForm = ({ recipe = null }: Props) => {
    console.log(recipe);
    const { storeRecipe, updateRecipe } = useRecipes();
    const methods = useForm<FormData>({
        defaultValues: {
            ...defaultPostData,
            ...recipe,
            thumbnail: new File([], ''),
        },
    });
    const values = methods.watch();
    const editMode: EditMode = recipe ? 'update' : 'create';

    /**
     * 送信ボタンの無効化判定
     * 料理名が空の場合、サムネイル画像が5MBを超える場合は送信ボタンを無効化
     */
    const isDisabledSendButton: boolean = React.useMemo(() => {
        if (values.name.length === 0) {
            return true;
        }
        if (values.thumbnail && values.thumbnail.size > MAX_IMAGE_SIZE) {
            return true;
        }
        return false;
    }, [values]);

    const formatItems = (
        items: IIngredient[] | ISeasoning[],
        prefix: string,
    ) => {
        return items
            .filter(v => v.name && v.name.length > 0)
            .map(v =>
                v.id?.startsWith(prefix)
                    ? {
                          name: v.name,
                          quantity: v.quantity,
                          unitId: v.unitId,
                      }
                    : v,
            );
    };

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: FormData) => {
        const formData = new FormData();
        const sendData = {
            id: recipe?.id ?? '',
            ...data,
            ingredients: formatItems(
                data.ingredients as IIngredient[],
                TMP_ID_PREFIX.RECIPE_INGREDIENT,
            ),
            seasonings: formatItems(
                data.seasonings as ISeasoning[],
                TMP_ID_PREFIX.RECIPE_SEASONING,
            ),
        };

        // サムネイル画像
        if (sendData.thumbnail && sendData.thumbnail instanceof File) {
            formData.append('thumbnail', sendData.thumbnail);
        } else {
            formData.append('thumbnailDelete', 'true');
        }

        // 他のフィールド
        formData.append('id', sendData.id);
        formData.append('name', sendData.name);
        formData.append('url', sendData.url ?? '');
        formData.append('instructions', sendData.instructions ?? '');
        formData.append('memo', sendData.memo ?? '');
        formData.append('categoryIds', JSON.stringify(sendData.categoryIds));
        formData.append('ingredients', JSON.stringify(sendData.ingredients));
        formData.append('seasonings', JSON.stringify(sendData.seasonings));

        if (editMode === 'create') {
            storeRecipe(formData);
        } else {
            console.log(formData.get('categoryIds'));
            updateRecipe(formData);
        }
    };

    return (
        <FormProvider {...methods}>
            <form
                onSubmit={methods.handleSubmit(onSubmit)}
                className="p-5 pb-[60px] grid grid-cols-[repeat(auto-fill,_minmax(350px,_1fr))] gap-x-10 gap-y-5 md:px-10">
                <div className="flex-1 flex flex-col gap-y-5">
                    {/* サムネイル画像 */}
                    <ThumbnailEditField
                        control={methods.control}
                        thumbnail={recipe?.thumbnail}
                    />
                    {/* 料理名 */}
                    <VerticalRowField
                        control={methods.control}
                        name="name"
                        label="料理名"
                        required={true}>
                        {({ value, onChange }) => (
                            <input
                                type="text"
                                value={(value as string) ?? ''}
                                placeholder="料理名を入力"
                                onChange={e => onChange(e)}
                                className="py-2 px-4 text-base border rounded-lg "
                            />
                        )}
                    </VerticalRowField>
                    {/* カテゴリー */}
                    <CategoryEditFields control={methods.control} />
                    {/* 食材 */}
                    <IngredientEditFields control={methods.control} />
                    {/* 調味料 */}
                    <SeasoningEditFields control={methods.control} />
                </div>
                <div className="flex-1 flex flex-col gap-y-5">
                    {/* レシピURL */}
                    <VerticalRowField
                        control={methods.control}
                        name="url"
                        label="レシピURL">
                        {({ value, onChange }) => (
                            <input
                                type="text"
                                value={(value as string) ?? ''}
                                placeholder="レシピURLを入力"
                                onChange={e => onChange(e)}
                                className="py-2 px-4 text-base border rounded-lg "
                            />
                        )}
                    </VerticalRowField>
                    {/* レシピ（テキスト入力） */}
                    <VerticalRowField
                        control={methods.control}
                        name="instructions"
                        label="レシピ（テキスト入力）">
                        {({ value, onChange }) => (
                            <textarea
                                value={(value as string) ?? ''}
                                rows={5}
                                placeholder="レシピを入力"
                                onChange={e => onChange(e)}
                                className="py-2 px-4 text-base border rounded-lg"
                            />
                        )}
                    </VerticalRowField>
                    {/* メモ */}
                    <VerticalRowField
                        control={methods.control}
                        name="memo"
                        label="メモ">
                        {({ value, onChange }) => (
                            <textarea
                                value={(value as string) ?? ''}
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
