import { IRecipe, IRecipeCategory, IGetMasterResponse } from '@/types/api';
import { create } from 'zustand';

type LoadingState = {
    recipe: boolean;
    recipeCategory: boolean;
};

interface RecipeState {
    // state
    recipes: IRecipe[];
    isLoadings: LoadingState;
    categories: IRecipeCategory[];

    // setter func
    setRecipes: (recipes: IRecipe[]) => void;
    setIsLoadings: (name: keyof LoadingState, isLoading: boolean) => void;
    setCategories: (
        categories: IGetMasterResponse['data']['recipeCategories'],
    ) => void;
}

export const useRecipeStore = create<RecipeState>(set => ({
    // initial state
    recipes: [],
    isLoadings: {
        recipeCategory: false,
        recipe: false,
    },
    categories: [],

    // setter func
    setRecipes: recipes => {
        set({ recipes });
    },
    setIsLoadings: (name: keyof LoadingState, isLoading: boolean) => {
        set(state => ({
            isLoadings: { ...state.isLoadings, [name]: isLoading },
        }));
    },
    setCategories: categories => {
        set({ categories });
    },
}));
