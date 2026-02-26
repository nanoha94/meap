import { create } from 'zustand';

import { IRecipe, IRecipeCategory } from '@/types';
import { sortOptions } from '../constants';
import { RecipeFilterFormData } from '../types';

interface RecipeState {
    // state
    recipes: IRecipe[];
    categories: IRecipeCategory[];
    listSortOptions: { sort: string, order: string };
    listFilterOptions: RecipeFilterFormData;
    listPagesize: number;
    listCurrentPage: number;

    // setter func
    setRecipes: (recipes: IRecipe[], pageSize: number, currentPage: number) => void;
    setCategories: (categories: IRecipeCategory[]) => void;
    setListSortOptions: (sortOptionId: string) => void;
    setListFilterOptions: (filterFormData: RecipeFilterFormData) => void;
}

export const useRecipeStore = create<RecipeState>(set => ({
    // initial state
    recipes: [],
    categories: [],
    listSortOptions: { sort: sortOptions[0].sort, order: sortOptions[0].order },
    listFilterOptions: {
        recipeName: '',
        ingredientName: '',
        categoryId: '',
        lastPlannedDateFrom: '',
        lastPlannedDateTo: '',
    },
    listPagesize: 0,
    listCurrentPage: 1,

    // setter func
    setRecipes: (recipes: IRecipe[], pageSize: number, currentPage: number) => set({ recipes, listPagesize: pageSize, listCurrentPage: currentPage }),
    setCategories: (categories: IRecipeCategory[]) => set({ categories }),
    setListSortOptions: (sortOptionId: string) => {
        const option = sortOptions.find(o => o.id === sortOptionId);
        set({
            listSortOptions: {
                sort: option?.sort ?? sortOptions[0].sort,
                order: option?.order ?? sortOptions[0].order,
            },
        });
    },
    setListFilterOptions: (filterOptions: RecipeFilterFormData) => set({ listFilterOptions: filterOptions }),
}));
