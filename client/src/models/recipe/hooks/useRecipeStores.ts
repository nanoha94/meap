import { create } from 'zustand';

import { IRecipe, IRecipeCategory } from '@/types';

interface RecipeState {
    // state
    recipes: IRecipe[];
    categories: IRecipeCategory[];

    // setter func
    setRecipes: (recipes: IRecipe[]) => void;
    setCategories: (categories: IRecipeCategory[]) => void;
}

export const useRecipeStore = create<RecipeState>(set => ({
    // initial state
    recipes: [],
    categories: [],

    // setter func
    setRecipes: recipes => set({ recipes }),
    setCategories: categories => set({ categories }),
}));
