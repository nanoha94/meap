import { create } from 'zustand';

import { IRecipe, IRecipeCategory } from '@/types';
import { sortOptions } from '../constants';
import { RecipeFilterFormData } from '../types';

interface RecipeState {
    // state
    recipes: IRecipe[];
    recipeTotal: number;
    categories: IRecipeCategory[];
    listSortOptions: { sort: string, order: string };
    listFilterOptions: RecipeFilterFormData;

    // setter func
    setRecipes: (recipes: IRecipe[], total: number) => void;
    setCategories: (categories: IRecipeCategory[]) => void;
    setListSortOptions: (sortOptionId: string) => void;
    setListFilterOptions: (filterFormData: RecipeFilterFormData) => void;
}

export const useRecipeStore = create<RecipeState>(set => ({
    // initial state
    recipes: [],
    recipeTotal: 0,
    categories: [],
    listSortOptions: { sort: sortOptions[0].sort, order: sortOptions[0].order },
    listFilterOptions: {
        recipeName: '',
        ingredientName: '',
        categoryId: '',
        lastPlannedDateFrom: '',
        lastPlannedDateTo: '',
    },

    // setter func
    setRecipes: (recipes: IRecipe[], total: number) => set({ recipes, recipeTotal: total }),
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
