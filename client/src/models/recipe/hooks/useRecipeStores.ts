import { create } from 'zustand';

import { IRecipeCategory } from '@/types';

/**
 * レシピカテゴリマスタ用 Zustand ストア。
 * 一覧のソート・フィルタ・ページなど UI 状態は useRecipeListStateStore を使う。
 */
interface RecipeStoreState {
    categories: IRecipeCategory[];

    setCategories: (categories: IRecipeCategory[]) => void;
}

export const useRecipeStore = create<RecipeStoreState>(set => ({
    categories: [],

    setCategories: (categories: IRecipeCategory[]) => set({ categories }),
}));
