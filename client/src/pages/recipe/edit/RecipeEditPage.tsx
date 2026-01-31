"use client";
import React from 'react';
import { FormProvider } from 'react-hook-form';
import { Copy } from 'lucide-react';

import {
    ImageEditField,
    VerticalRowField,
} from '@/components';
import { colors } from '@/constants';
import { useSnackbars, useTextCopy } from '@/hooks';
import {
    CategoryEditFields,
    IngredientEditFields,
    StepEditFields,
    useRecipeEditForm,
} from '@/models/recipe';
import { useAccountStore } from '@/models/settings';
import { IRecipe } from '@/types';
import RecipeEditPageHeader from './RecipeEditPageHeader';


interface Props {
    fetchRecipe?: IRecipe;
    errorMessage?: string;
}

const lineTitleWrapperStyle =
    "relative w-full mx-auto flex justify-center after:content-[''] after:absolute after:top-1/2 after:left-0 after:translate-y-[-50%] after:block after:w-full after:h-[1px] after:bg-gray-main";

const lineTitleStyle = 'z-10 px-5 text-xl md:text-2xl bg-primary-background';

const RecipeEditPage = ({
    fetchRecipe,
    errorMessage,
}: Props) => {
    const { addSnackbar } = useSnackbars();
    const { loginUser } = useAccountStore();
    const [ownerUserId, setOwnerUserId] = React.useState<string>(
        (fetchRecipe as IRecipe)?.ownerUserId || loginUser.id,
    );
    const {
        control,
        methods,
        onSubmit,
        errors,
    } = useRecipeEditForm(fetchRecipe, ownerUserId);
    const { isTextCopied, copyToClipboard } = useTextCopy();


    /**
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage]);

    return (
        <>
            <RecipeEditPageHeader
                ownerUserId={ownerUserId}
                fetchRecipe={fetchRecipe}
                onChangeOwnerUserId={(userId) => {
                    setOwnerUserId(userId);
                }}
                onClickSaveButton={onSubmit}
            />
            <main className="pb-[60px] max-w-[1200px] mx-auto">
                <FormProvider {...methods}>
                    <form
                        onSubmit={onSubmit}
                    >
                        {/* サムネイル画像 */}
                        <ImageEditField control={control} name="thumbnail" className=' md:hidden' />
                        <div className="pt-5 px-5 md:px-10 grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-14">
                            {/* サムネイル画像 */}
                            <ImageEditField control={control} name="thumbnail" className='hidden md:block' />
                            <div className="flex-1 flex flex-col gap-y-8">
                                {/* 料理名 */}
                                <VerticalRowField
                                    control={control}
                                    name="name"
                                    label="料理名">
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
                                {/* メモ */}
                                <VerticalRowField
                                    control={control}
                                    name="memo"
                                    label="メモ"
                                    memo="※外部には公開されません"
                                >
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
                            </div>
                            <div className="flex-1 flex flex-col gap-y-8">
                                <div className={lineTitleWrapperStyle}>
                                    <span className={lineTitleStyle}>材料</span>
                                </div>
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
                                {/* 材料 */}
                                <IngredientEditFields control={control} />
                            </div>
                            <div className="flex-1 flex flex-col gap-y-8">
                                <div className={lineTitleWrapperStyle}>
                                    <span className={lineTitleStyle}>作り方</span>
                                </div>
                                {/* レシピURL */}
                                <VerticalRowField
                                    control={control}
                                    name="url"
                                    label="レシピURL"
                                    memo="※外部に公開する際には空にしてください"
                                >
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
                            </div>
                        </div>
                    </form>
                </FormProvider>
            </main>
        </>
    );
};

export default RecipeEditPage;
