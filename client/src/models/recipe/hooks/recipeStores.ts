import { IRecipe, IRecipeCategory, IGetMasterResponse } from '@/types/api';
import { create } from 'zustand';

interface RecipeState {
    // state
    recipes: IRecipe[];
    categories: IRecipeCategory[];

    // setter func
    setRecipes: (recipes: IRecipe[]) => void;
    setCategories: (
        categories: IGetMasterResponse['data']['recipeCategories'],
    ) => void;
}

export const useRecipeStore = create<RecipeState>(set => ({
    // initial state
    recipes: [],
    categories: [],

    // setter func
    setRecipes: recipes => {
        set({ recipes });
    },
    setCategories: categories => {
        set({ categories });
    },
}));
