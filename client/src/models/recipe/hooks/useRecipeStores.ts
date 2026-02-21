import { create } from 'zustand';

import { IRecipe, IRecipeCategory } from '@/types';
import { sortOptions } from '../constants';

interface RecipeState {
    // state
    recipes: IRecipe[];
    categories: IRecipeCategory[];
    sortOption: string;

    // setter func
    setRecipes: (recipes: IRecipe[]) => void;
    setCategories: (categories: IRecipeCategory[]) => void;
    setSortOption: (sortOption: string) => void;
}

export const useRecipeStore = create<RecipeState>(set => ({
    // initial state
    recipes: [],
    categories: [],
    sortOption: sortOptions[0].id,

    // setter func
    setRecipes: recipes => set({ recipes }),
    setCategories: categories => set({ categories }),
    setSortOption: sortOption => set({ sortOption }),
}));
