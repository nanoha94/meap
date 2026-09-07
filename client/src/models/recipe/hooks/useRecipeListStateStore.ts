import { create } from 'zustand';

import { sortOptions } from '../constants';
import { RecipeFilterFormData } from '../types';

/**
 * ナビゲーションの `/recipe` リンク生成時に最後の閲覧状態を引き継ぐためのキャッシュ。
 * 表示そのものは URL クエリを Source of Truth とする。
 */

interface RecipeListState {
    listSortOptions: { sort: string; order: string };
    listFilterOptions: RecipeFilterFormData;
    listPagesize: number;
    listCurrentPage: number;

    setListSortOptions: (sortOptionId: string) => void;
    setListFilterOptions: (filterFormData: RecipeFilterFormData) => void;
    setListPaging: (paging: { pageSize: number; currentPage: number }) => void;
}

export const useRecipeListStateStore = create<RecipeListState>(set => ({
    listSortOptions: { sort: sortOptions[0].sort, order: sortOptions[0].order },
    listFilterOptions: {
        recipeName: '',
        ingredientName: '',
        categoryIds: [],
        lastPlannedDateFrom: '',
        lastPlannedDateTo: '',
    },
    listPagesize: 0,
    listCurrentPage: 1,

    setListSortOptions: (sortOptionId: string) => {
        const option = sortOptions.find(o => o.id === sortOptionId);
        set({
            listSortOptions: {
                sort: option?.sort ?? sortOptions[0].sort,
                order: option?.order ?? sortOptions[0].order,
            },
        });
    },

    setListFilterOptions: (filterOptions: RecipeFilterFormData) =>
        set({ listFilterOptions: filterOptions }),

    setListPaging: ({ pageSize, currentPage }) =>
        set({ listPagesize: pageSize, listCurrentPage: currentPage }),
}));
