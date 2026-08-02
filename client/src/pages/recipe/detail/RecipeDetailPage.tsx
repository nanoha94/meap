'use client';

import React from 'react';
import { Image as ImageIcon, Pencil, Trash2 } from 'lucide-react';
import Image from 'next/image';
import { useRouter } from 'next/navigation';

import { Header, HeaderTextButton } from '@/components';
import { COLOR_VARIANT, LINK_TO } from '@/constants';
import { useAlertDialog, useSnackbars } from '@/hooks';
import { formatIngredientQuantity } from '@/models/ingredient';
import { RECIPE_ALERT_DIALOG_CONFIGS, useRecipeApi } from '@/models/recipe';
import { useUserStore } from '@/models/user';
import { ActionButton, IImage, IRecipe } from '@/types';

interface Props {
    fetchedRecipe?: IRecipe;
    errorMessage?: string;
}

const lineTitleWrapperStyle =
    "relative w-full mx-auto flex justify-center after:content-[''] after:absolute after:top-1/2 after:left-0 after:translate-y-[-50%] after:block after:w-full after:h-[1px] after:bg-gray-main";

const lineTitleStyle = 'z-10 px-5 text-xl md:text-2xl bg-primary-background';

const RecipeDetailPage = ({
    fetchedRecipe,
    errorMessage,
}: Props) => {
    // store
    const loginUser = useUserStore(state => state.loginUser);

    // hook
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { deleteRecipe } = useRecipeApi();
    const { openAlertDialog } = useAlertDialog();

    /**
     * 削除確認ダイアログを開く
     */
    const openDeleteCheckDialog = () => {
        if (!fetchedRecipe) {
            return;
        }
        openAlertDialog(
            RECIPE_ALERT_DIALOG_CONFIGS.deleteItem(fetchedRecipe.name),
            async () => {
                const success = await deleteRecipe(fetchedRecipe.id);
                if (success) {
                    router.push(LINK_TO.RECIPE.TOP);
                }
            },
        );
    };

    /**
     * メニューボタン押下時に開くアクションボタン設定
     */
    const actionButtonConfigs: ActionButton[] = fetchedRecipe?.ownerUserId === loginUser?.id ? [
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
    }, [errorMessage, addSnackbar]);

    return (
        <><Header
            maxWidth="1200px"
            hasBackButton={true}
            rightContent={
                <div className="flex items-center gap-x-4">
                    <HeaderTextButton colorVariant={COLOR_VARIANT.SECONDARY}
                        href={`/recipe/${fetchedRecipe?.id}/edit`}>
                        <Pencil size={20} strokeWidth={2} />
                        編集
                    </HeaderTextButton>
                </div>
            }
            actionButtons={actionButtonConfigs}
        />
            <main className="pb-[60px] max-w-[1200px] mx-auto">
                {/* サムネイル画像 */}
                <RecipeThumbnail thumbnail={fetchedRecipe?.thumbnail ?? null} className="md:hidden" />
                <div className="p-5 md:px-10 grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-14">
                    {/* サムネイル画像 */}
                    <RecipeThumbnail thumbnail={fetchedRecipe?.thumbnail ?? null} className="hidden md:block" />
                    <div className="flex-1 flex flex-col gap-y-8">
                        {/* 料理名 */}
                        <div className="text-2xl font-bold">{fetchedRecipe?.name}</div>
                        {/* カテゴリー */}
                        {fetchedRecipe?.categories &&
                            fetchedRecipe?.categories.length > 0 && (
                                <ul className="flex flex-wrap gap-x-3">
                                    {fetchedRecipe?.categories.map(category => (
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
                            {fetchedRecipe?.memo ? (
                                <div>{fetchedRecipe?.memo}</div>
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
                                }>{`材料${fetchedRecipe?.servingCount ? `【${fetchedRecipe?.servingCount}人分】` : ''}`}</span>
                        </div>
                        {/* 材料 */}
                        {fetchedRecipe?.ingredients &&
                            fetchedRecipe?.ingredients.length > 0 ? (
                            <div className="flex flex-col gap-y-5">
                                {fetchedRecipe?.ingredientCategories?.map(
                                    category =>
                                        fetchedRecipe.ingredients.some(
                                            ingredient =>
                                                ingredient.categoryId === category.id,
                                        ) && (
                                            <div key={category.id}>
                                                <div className="mb-2 text-lg">
                                                    {category.name}
                                                </div>
                                                <ul className="flex flex-col gap-y-1">
                                                    {fetchedRecipe.ingredients
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
                                                                    {formatIngredientQuantity(ingredient)}
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
                        {!fetchedRecipe?.url &&
                            (!fetchedRecipe?.steps || fetchedRecipe?.steps.length <= 0) && (
                                <div>作り方がありません</div>
                            )}
                        {/* レシピURL */}
                        {fetchedRecipe?.url && (
                            <div className="flex flex-col gap-y-1">
                                <div className="text-xl font-bold">レシピ</div>
                                <a
                                    href={fetchedRecipe?.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="break-all text-primary-main underline transition-colors hover:text-accent-main">
                                    {fetchedRecipe?.url}
                                </a>
                            </div>
                        )}
                        {/* 手順 */}
                        {fetchedRecipe?.steps && (
                            <ul className="grid grid-cols-[repeat(auto-fill,_minmax(180px,_1fr))] gap-5">
                                {fetchedRecipe?.steps.map((step, index) => (
                                    <li key={step.id}>
                                        <div className="flex flex-col gap-y-1">
                                            <div>{index + 1}.&nbsp;</div>
                                            {step.image && (
                                                <div className="relative w-full h-auto aspect-[4/3] bg-gray-light rounded-lg overflow-hidden">
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
        <div className={`relative w-full h-auto aspect-[4/3] bg-gray-light rounded-none overflow-hidden md:rounded-lg ${className ?? ''}`}>
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