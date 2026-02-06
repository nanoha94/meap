'use client';
import React from 'react';
import Image from 'next/image';
import { Image as ImageIcon, Pencil, Trash2 } from 'lucide-react';

import { Header, HeaderTextButton } from '@/components';
import { COLOR_VARIANT } from '@/constants';
import { useAlertDialog, useSnackbars } from '@/hooks';
import { useIngredientStore } from '@/models/ingredient';
import { useRecipeApi } from '@/models/recipe';
import { useAccountStore } from '@/models/settings';
import { ActionButton, IImage, IRecipe } from '@/types';

interface Props {
    fetchRecipe?: IRecipe;
    errorMessage?: string;
}

const lineTitleWrapperStyle =
    "relative w-full mx-auto flex justify-center after:content-[''] after:absolute after:top-1/2 after:left-0 after:translate-y-[-50%] after:block after:w-full after:h-[1px] after:bg-gray-main";

const lineTitleStyle = 'z-10 px-5 text-xl md:text-2xl bg-primary-background';

const RecipeDetailPage = ({
    fetchRecipe,
    errorMessage,
}: Props) => {
    const { loginUser } = useAccountStore();
    const { addSnackbar } = useSnackbars();
    const { deleteRecipe } = useRecipeApi();
    const { openAlertDialog } = useAlertDialog();
    const { categories: ingredientCategories } = useIngredientStore();

    /**
     * 削除確認ダイアログを開く
     */
    const openDeleteCheckDialog = () => {
        if (!fetchRecipe) {
            return;
        }
        openAlertDialog(
            {
                title: '削除',
                message: [`${fetchRecipe.name}を削除しますか？`],
                alertMessage: '',
                actionButtonText: '削除',
            },
            () => {
                deleteRecipe(fetchRecipe.id, fetchRecipe.name);
            },
        );
    };

    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtonConfigs: ActionButton[] = fetchRecipe?.ownerUserId === loginUser?.id ? [
        // 削除できるのは、編集責任者のみ
        {
            label: '削除する',
            icon: <Trash2 size={20} strokeWidth={2} />,
            onClick: openDeleteCheckDialog,
            color: COLOR_VARIANT.ALERT,
        },
    ] : [];

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
        <><Header
            maxWidth="1200px"
            hasBackButton={true}
            rightContent={
                <div className="flex items-center gap-x-4">
                    <HeaderTextButton colorVariant={COLOR_VARIANT.SECONDARY}
                        href={`/recipe/${fetchRecipe?.id}/edit`}>
                        <Pencil size={20} strokeWidth={2} />
                        編集
                    </HeaderTextButton>
                </div>
            }
            actionButtons={actionButtonConfigs}
        />
            <main className="pb-[60px] max-w-[1200px] mx-auto">
                {/* サムネイル画像 */}
                <RecipeThumbnail thumbnail={fetchRecipe?.thumbnail ?? null} className="md:hidden" />
                <div className="p-5 md:px-10 grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-14">
                    {/* サムネイル画像 */}
                    <RecipeThumbnail thumbnail={fetchRecipe?.thumbnail ?? null} className="hidden md:block" />
                    <div className="flex-1 flex flex-col gap-y-8">
                        {/* 料理名 */}
                        <div className="text-2xl font-bold">{fetchRecipe?.name}</div>
                        {/* カテゴリー */}
                        {fetchRecipe?.categories &&
                            fetchRecipe?.categories.length > 0 && (
                                <ul className="flex flex-wrap gap-x-3">
                                    {fetchRecipe?.categories.map(category => (
                                        <li
                                            key={category.id}
                                            className="py-1 px-2 text-xs leading-none text-gray-main rounded-full border border-gray-main">
                                            {category.name}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        {/* メモ */}
                        <div className="flex flex-col gap-y-2">
                            <div className="text-xl">メモ</div>
                            {fetchRecipe?.memo ? (
                                <div>{fetchRecipe?.memo}</div>
                            ) : (
                                <div>メモがありません</div>
                            )}
                        </div>
                    </div>
                    <div className="flex-1 flex flex-col gap-y-8">
                        <div className={lineTitleWrapperStyle}>
                            <span
                                className={
                                    lineTitleStyle
                                }>{`材料${fetchRecipe?.servingCount ? `【${fetchRecipe?.servingCount}人分】` : ''}`}</span>
                        </div>
                        {/* 材料 */}
                        {fetchRecipe?.ingredients &&
                            fetchRecipe?.ingredients.length > 0 ? (
                            <div className="flex flex-col gap-y-5">
                                {ingredientCategories?.map(
                                    category =>
                                        fetchRecipe.ingredients.some(
                                            ingredient =>
                                                ingredient.categoryId === category.id,
                                        ) && (
                                            <div key={category.id}>
                                                <div className="mb-2 text-lg">
                                                    {category.name}
                                                </div>
                                                <ul className="flex flex-col gap-y-1">
                                                    {fetchRecipe.ingredients
                                                        .filter(
                                                            ingredient =>
                                                                ingredient.categoryId ===
                                                                category.id,
                                                        )
                                                        .map(ingredient => (
                                                            <li
                                                                key={ingredient.id}
                                                                className="relative pl-5 pr-2 py-1 flex justify-between border-b border-gray-border before:content-[''] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:inline-block before:w-1 before:h-1 before:bg-black before:rounded-full">
                                                                <div>
                                                                    {ingredient.name}
                                                                </div>
                                                                <div>
                                                                    {ingredient.unit
                                                                        ?.position ===
                                                                        'prefix' &&
                                                                        ` ${ingredient.unit.name}`}
                                                                    {
                                                                        ingredient.quantity
                                                                    }
                                                                    {ingredient.unit
                                                                        ?.position ===
                                                                        'suffix' &&
                                                                        ` ${ingredient.unit.name}`}
                                                                </div>
                                                            </li>
                                                        ))}
                                                </ul>
                                            </div>
                                        ),
                                )}
                            </div>
                        ) : (
                            <div>材料がありません</div>
                        )}
                    </div>
                    <div className="flex-1 flex flex-col gap-y-8">
                        <div className={lineTitleWrapperStyle}>
                            <span className={lineTitleStyle}>作り方</span>
                        </div>
                        {!fetchRecipe?.url &&
                            (!fetchRecipe?.steps || fetchRecipe?.steps.length <= 0) && (
                                <div>作り方がありません</div>
                            )}
                        {/* レシピURL */}
                        {fetchRecipe?.url && (
                            <div className="flex flex-col gap-y-1">
                                <div className="text-xl font-bold">レシピ</div>
                                <a
                                    href={fetchRecipe?.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-primary-main underline transition-colors hover:text-accent-main">
                                    {fetchRecipe?.url}
                                </a>
                            </div>
                        )}
                        {/* 手順 */}
                        {fetchRecipe?.steps && (
                            <ul className="grid grid-cols-[repeat(auto-fill,_minmax(180px,_1fr))] gap-5">
                                {fetchRecipe?.steps.map((step, index) => (
                                    <li key={step.id}>
                                        <div className="flex flex-col gap-y-1">
                                            <div>{index + 1}.&nbsp;</div>
                                            {step.image && (
                                                <div className="relative w-full h-auto aspect-[5/3] bg-gray-light rounded-lg overflow-hidden">
                                                    <Image
                                                        src={step.image.src}
                                                        alt={step.instruction}
                                                        width={step.image.width}
                                                        height={step.image.height}
                                                        className="absolute top-0 left-0 w-full h-full object-cover"
                                                    />
                                                </div>
                                            )}
                                            <div className="whitespace-pre-wrap">
                                                {step.instruction}
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </main>
        </>
    );
};

export default RecipeDetailPage;


const RecipeThumbnail = ({ thumbnail, className }: { thumbnail: IImage | null, className?: string }) => {
    return (
        <div className={`relative w-full h-auto aspect-[4/3] bg-gray-light rounded-none overflow-hidden md:rounded-lg ${className}`}>
            {thumbnail ? (
                <Image
                    src={thumbnail.src}
                    alt="thumbnail"
                    width={thumbnail.width}
                    height={thumbnail.height}
                    className="absolute top-0 left-0 w-full h-full object-cover"
                />
            ) : (
                <ImageIcon
                    size={40}
                    strokeWidth={1.5}
                    className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-gray-main"
                />
            )}
        </div>
    );
};