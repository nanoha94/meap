import { create } from 'zustand';

import { IMealCategory } from '@/types';

interface MealState {
    // state
    mealCategories: IMealCategory[];

    // setter func
    setMealCategories: (mealCategories: IMealCategory[]) => void;
}

export const useMealStore = create<MealState>(set => ({
    // initial state
    mealCategories: [],

    // setter func
    setMealCategories: mealCategories => set({ mealCategories }),
}));
