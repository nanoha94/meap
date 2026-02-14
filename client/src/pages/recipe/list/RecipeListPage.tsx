'use client';
import React from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { CirclePlus } from 'lucide-react';

import { Header, HeaderTextButton } from '@/components';
import { COLOR_VARIANT } from '@/constants';
import { useSnackbars } from '@/hooks';
import { useRecipeStore } from '@/models/recipe';
import { IRecipe } from '@/types';

interface Props {
    fetchRecipes: IRecipe[];
    total: number;
    errorMessage?: string;
}

const RecipeListPage = ({
    fetchRecipes = [],
    total = 0,
    errorMessage,
}: Props) => {
    const { setRecipes: setStoreRecipes } = useRecipeStore();
    const { addSnackbar } = useSnackbars();

    /**
     * 料理/レシピをストアにセット
     * @returns void
     */
    React.useEffect(() => {
        if (fetchRecipes) {
            setStoreRecipes(fetchRecipes);
        }
    }, [fetchRecipes]);

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
            <main className='p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto'>
                {total > 0 ? (
                    <div className="grid grid-cols-[repeat(auto-fill,_minmax(150px,_1fr))] gap-3">
                        {fetchRecipes.map(v => (
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
                                    <div className="text-xs text-black">前回の献立日：</div>
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
