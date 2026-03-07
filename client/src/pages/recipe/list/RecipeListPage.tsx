'use client';

import React from 'react';
import dayjs from 'dayjs';
import Image from 'next/image';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { CirclePlus, SlidersHorizontal } from 'lucide-react';

import { Header, HeaderTextButton, RecipeFilterForm, StyledSelect } from '@/components';
import { COLOR_VARIANT, colors } from '@/constants';
import { useDialog, useSnackbars } from '@/hooks';
import { sortOptions, useRecipeStore } from '@/models/recipe';
import { RecipeFilterFormData } from '@/models/recipe/types';
import { IRecipe } from '@/types';
import { useGlobalStore } from '@/stores';
import { getBrowserQueryString } from '@/models/recipe/utils';
import Pagination from '@/components/Pagination';

interface Props {
    fetchedRecipes: IRecipe[];
    pageSize: number;
    currentPage: number;
    errorMessage?: string;
    sortOptionId?: string;
    filterOptions?: RecipeFilterFormData;
}

const RecipeListPage = ({
    fetchedRecipes = [],
    pageSize,
    currentPage,
    errorMessage,
    sortOptionId,
    filterOptions,
}: Props) => {
    const router = useRouter();
    const setStoreRecipes = useRecipeStore(state => state.setRecipes);
    const setListSortOptions = useRecipeStore(state => state.setListSortOptions);
    const setListFilterOptions = useRecipeStore(state => state.setListFilterOptions);
    const listFilterOptions = useRecipeStore(state => state.listFilterOptions);
    const listSortOptions = useRecipeStore(state => state.listSortOptions);
    const listCurrentPage = useRecipeStore(state => state.listCurrentPage);
    const recipes = useRecipeStore(state => state.recipes);
    const { addSnackbar } = useSnackbars();
    const { openDialog } = useDialog();
    const { incrementLoadingCount, resetLoadingCount } = useGlobalStore();

    /**
     * 並び替え処理（ストア更新・再取得・URLクエリ更新）
     */
    const handleChangeSortOptions = React.useCallback((e: React.ChangeEvent<HTMLSelectElement>) => {
        const option = sortOptions.find(o => o.id === e.target.value) ?? sortOptions[0];
        router.push(`/recipe?${getBrowserQueryString(option, listFilterOptions, listCurrentPage)}`);
        incrementLoadingCount();
    }, [listFilterOptions, listCurrentPage, router, incrementLoadingCount]);

    /**
     * 絞り込み条件変更処理（ストア更新・再取得・URLクエリ更新）
     */
    const handleChangeFilterOptions = React.useCallback((data: RecipeFilterFormData) => {
        router.push(`/recipe?${getBrowserQueryString(listSortOptions, data, listCurrentPage)}`);
        incrementLoadingCount();
    }, [listSortOptions, listCurrentPage, router, incrementLoadingCount]);

    /**
     * ページ番号変更処理（ストア更新・再取得・URLクエリ更新）
     */
    const handleChangePage = React.useCallback((page: number) => {
        router.push(`/recipe?${getBrowserQueryString(listSortOptions, listFilterOptions, page)}`);
        incrementLoadingCount();
    }, [listSortOptions, listFilterOptions, router, incrementLoadingCount]);

    /**
     * 初期表示時: ストアにレシピと並び順をセット
     */
    React.useEffect(() => {
        if (fetchedRecipes) {
            setStoreRecipes(fetchedRecipes, pageSize, currentPage);
        }
    }, [fetchedRecipes, pageSize, currentPage, setStoreRecipes]);

    /**
     * sortOptionId をストアに反映
     */
    React.useEffect(() => {
        if (!sortOptionId) return;
        setListSortOptions(sortOptionId);
        resetLoadingCount();
    }, [sortOptionId, setListSortOptions, resetLoadingCount]);

    /**
     * filterOptions をストアに反映
     */
    React.useEffect(() => {
        if (!filterOptions) return;
        setListFilterOptions(filterOptions);
        resetLoadingCount();
    }, [filterOptions, setListFilterOptions]);

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
                <div className="flex justify-between gap-x-5 gap-y-2 flex-wrap">
                    <button type="button" onClick={() => {
                        openDialog({
                            title: '絞り込み条件',
                            children: <RecipeFilterForm search={handleChangeFilterOptions} defaultValues={listFilterOptions} />
                        });
                    }} className="py-1 px-2 flex items-center gap-x-2 rounded hover:bg-gray-light"><SlidersHorizontal color={colors.black} strokeWidth={1.5} />絞り込み</button>
                    <StyledSelect
                        value={
                            sortOptionId ??
                            sortOptions.find(o => o.sort === listSortOptions.sort && o.order === listSortOptions.order)?.id ??
                            sortOptions[0].id
                        }
                        options={sortOptions}
                        onChange={handleChangeSortOptions}
                        isShowPlaceholder={false}
                        className="!w-auto"
                    />
                </div>
                <div className='flex flex-col gap-y-14'>
                    {pageSize > 0 ? (
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
                    <Pagination pageSize={pageSize} currentPage={currentPage} onPageChange={handleChangePage} />
                </div>
            </main>
        </>
    );
};

export default RecipeListPage;
