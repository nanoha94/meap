'use client';
import React from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { CirclePlus, SlidersHorizontal } from 'lucide-react';

import { Header, HeaderTextButton, StyledSelect } from '@/components';
import { COLOR_VARIANT, colors } from '@/constants';
import { useSnackbars } from '@/hooks';
import { sortOptions, useRecipeApi, useRecipeStore } from '@/models/recipe';
import { IRecipe } from '@/types';
import dayjs from 'dayjs';

interface Props {
    fetchedRecipes: IRecipe[];
    total: number;
    errorMessage?: string;
}

const RecipeListPage = ({
    fetchedRecipes = [],
    total = 0,
    errorMessage,
}: Props) => {
    const setStoreRecipes = useRecipeStore(state => state.setRecipes);
    const setSortOption = useRecipeStore(state => state.setSortOption);
    const { recipes, sortOption } = useRecipeStore();
    const { fetchRecipes } = useRecipeApi();
    const { addSnackbar } = useSnackbars();

    /**
     * 並び替え処理
     * @param e SelectChangeEvent
     */
    const handleChangeSortOption = async (e: React.ChangeEvent<HTMLSelectElement>) => {
        const selected = e.target.value;
        setSortOption(selected);
        await fetchRecipes(selected);
    };

    /**
     * 料理/レシピをストアにセット
     * @returns void
     */
    React.useEffect(() => {
        if (fetchedRecipes) {
            setStoreRecipes(fetchedRecipes);
        }
    }, [fetchedRecipes, setStoreRecipes]);

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
            <Header
                title="料理/レシピ一覧"
                rightContent={
                    <div className="hidden md:flex">
                        <HeaderTextButton
                            href="/recipe/new"
                            colorVariant={COLOR_VARIANT.SECONDARY}>
                            <CirclePlus size={20} />
                            料理/レシピを追加
                        </HeaderTextButton>
                    </div>
                }
            />
            <main className='p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto flex flex-col gap-y-5'>
                <div className="flex justify-between gap-x-5">
                    {/* TODO: 絞り込み・並び替え実装 */}
                    <button type="button" onClick={() => { }} className="flex items-center gap-x-2"><SlidersHorizontal color={colors.black} strokeWidth={1.5} />絞り込み</button>
                    <StyledSelect value={sortOption} options={sortOptions} onChange={handleChangeSortOption} isShowPlaceholder={false} className="!w-auto" />
                </div>
                {total > 0 ? (
                    <div className="grid grid-cols-[repeat(auto-fill,_minmax(160px,_1fr))] gap-3">
                        {recipes.map(v => (
                            <Link
                                href={`/recipe/${v.id}`}
                                key={v.id}
                                className="relative w-full text-left flex flex-col bg-white rounded transition-transform duration-500 cursor-pointer hover:-translate-y-2"
                                style={{ boxShadow: '1px 1px 5px rgba(0, 0, 0, 15%)' }}>
                                <div className="w-full h-auto aspect-video object-cover bg-gray-background">
                                    {v.thumbnail && v.thumbnail.src && (
                                        <Image
                                            src={v.thumbnail.src}
                                            alt={v.name}
                                            width={v.thumbnail.width}
                                            height={v.thumbnail.height}
                                            className="w-full h-auto aspect-video object-cover rounded-t"
                                        />
                                    )}
                                </div>
                                <div className="p-2 flex flex-col gap-y-1">
                                    <div className="text-sm">{v.name}</div>
                                    <div className="text-xs">
                                        {v.categories
                                            .map(category => category.name)
                                            .join('/')}
                                    </div>
                                    <div className="text-xs text-black">前回の献立日：{v.lastPlannedDate ? dayjs(v.lastPlannedDate).format('YYYY/MM/DD') : '-'}</div>
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <p>まだ料理/レシピが登録されていません。</p>
                )}
            </main>
        </>
    );
};

export default RecipeListPage;
