import { IGetRecipesResponse } from '@/types/api/recipe';
import { create } from 'zustand';

interface RecipeState {
    // ローカル状態
    recipes: IGetRecipesResponse['data'];

    // レシピ一覧のアクション
    setRecipes: (recipes: IGetRecipesResponse['data']) => void;
}

export const useRecipeStore = create<RecipeState>(set => ({
    // 初期状態
    recipes: [],

    // レシピ一覧のアクション
    setRecipes: recipes => {
        set({ recipes });
    },
}));
