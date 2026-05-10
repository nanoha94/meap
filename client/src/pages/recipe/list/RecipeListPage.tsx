'use client';

import React from 'react';
import dayjs from 'dayjs';
import Image from 'next/image';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { CirclePlus, SlidersHorizontal } from 'lucide-react';

import { Header, HeaderTextButton, RecipeFilterForm, StyledSelect } from '@/components';
import { COLOR_VARIANT, LINK_TO, colors } from '@/constants';
import { useDialog, useSnackbars } from '@/hooks';
import { sortOptions, useRecipeListStateStore } from '@/models/recipe';
import { RecipeFilterFormData } from '@/models/recipe/types';
import { IRecipe } from '@/types';
import { useGlobalStore } from '@/stores';
import { getBrowserQueryString } from '@/models/recipe/utils';
import Pagination from '@/components/Pagination';

function resolveRecipeSortOption(
    sortOptionId: string | undefined,
): (typeof sortOptions)[number] {
    return sortOptions.find(o => o.id === sortOptionId) ?? sortOptions[0];
}

const emptyRecipeListFilterOptions: RecipeFilterFormData = {
    recipeName: '',
    ingredientName: '',
    categoryIds: [],
    lastPlannedDateFrom: '',
    lastPlannedDateTo: '',
};

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
    // store — ナビ用の閲覧状態キャッシュ
    const setListPaging = useRecipeListStateStore(state => state.setListPaging);
    const setListSortOptions = useRecipeListStateStore(state => state.setListSortOptions);
    const setListFilterOptions = useRecipeListStateStore(state => state.setListFilterOptions);
    const incrementLoadingCount = useGlobalStore(state => state.incrementLoadingCount);
    const resetLoadingCount = useGlobalStore(state => state.resetLoadingCount);

    // hook
    const router = useRouter();
    const { addSnackbar } = useSnackbars();
    const { openDialog } = useDialog();

    // 絞り込み条件を取得
    const currentFilterOptions =
        React.useMemo(
            () => filterOptions ?? emptyRecipeListFilterOptions,
            [filterOptions],
        );

    // 並び替えオプションを取得
    const currentSortOption =
        React.useMemo(
            () => resolveRecipeSortOption(sortOptionId),
            [sortOptionId],
        );

    /**
     * レシピ一覧ページに遷移する
     * @param sort 並び替えオプション
     * @param filter 絞り込み条件
     * @param page ページ番号
     * @returns void
     */
    const navigateToRecipeList = React.useCallback(
        (
            sort: { sort: string; order: string },
            filter: RecipeFilterFormData,
            page: number,
        ) => {
            router.push(`/recipe?${getBrowserQueryString(sort, filter, page)}`);
            incrementLoadingCount();
        },
        [router, incrementLoadingCount],
    );

    /**
     * 並び替え処理（ストア更新・再取得・URLクエリ更新）
     */
    const handleChangeSortOptions = React.useCallback((e: React.ChangeEvent<HTMLSelectElement>) => {
        navigateToRecipeList(
            resolveRecipeSortOption(e.target.value),
            currentFilterOptions,
            currentPage,
        );
    }, [navigateToRecipeList, currentFilterOptions, currentPage]);

    /**
     * 絞り込み条件変更処理（ストア更新・再取得・URLクエリ更新）
     */
    const handleChangeFilterOptions = React.useCallback((data: RecipeFilterFormData) => {
        navigateToRecipeList(currentSortOption, data, currentPage);
    }, [navigateToRecipeList, currentSortOption, currentPage]);

    /**
     * ページ番号変更処理（ストア更新・再取得・URLクエリ更新）
     */
    const handleChangePage = React.useCallback((page: number) => {
        navigateToRecipeList(currentSortOption, currentFilterOptions, page);
    }, [navigateToRecipeList, currentSortOption, currentFilterOptions]);

    /**
     * URL（Props）に合わせてナビ用キャッシュのみ更新する（表示は Props / URL が Source of Truth）
     */
    React.useEffect(() => {
        setListSortOptions(resolveRecipeSortOption(sortOptionId).id);
        if (filterOptions) {
            setListFilterOptions(filterOptions);
        }
        setListPaging({ pageSize, currentPage });
        resetLoadingCount();
    }, [
        sortOptionId,
        filterOptions,
        pageSize,
        currentPage,
        setListSortOptions,
        setListFilterOptions,
        setListPaging,
        resetLoadingCount,
    ]);

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
                    <HeaderTextButton
                        href={LINK_TO.RECIPE.NEW}
                        colorVariant={COLOR_VARIANT.SECONDARY}>
                        <CirclePlus size={20} />
                        料理/レシピを追加
                    </HeaderTextButton>
                }
            />
            <main className='p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto flex flex-col gap-y-5'>
                <div className="flex justify-between gap-x-5 gap-y-2 flex-wrap">
                    <button type="button" onClick={() => {
                        openDialog({
                            title: '絞り込み条件',
                            children: <RecipeFilterForm search={handleChangeFilterOptions} defaultValues={currentFilterOptions} />
                        });
                    }} className="py-1 px-2 flex items-center gap-x-2 rounded hover:bg-gray-light"><SlidersHorizontal color={colors.black} strokeWidth={1.5} />絞り込み</button>
                    <StyledSelect
                        value={currentSortOption.id}
                        options={sortOptions}
                        onChange={handleChangeSortOptions}
                        isShowPlaceholder={false}
                        className="!w-auto"
                    />
                </div>
                {pageSize <= 0
                    ? <p>登録されている料理/レシピはありません。</p>
                    : <div className='flex flex-col gap-y-14'>
                        <div className="grid grid-cols-[repeat(auto-fill,_minmax(160px,_1fr))] gap-3">
                            {fetchedRecipes.map(v => (
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
                        <Pagination pageSize={pageSize} currentPage={currentPage} onPageChange={handleChangePage} />
                    </div>
                }
            </main>
        </>
    );
};

export default RecipeListPage;
