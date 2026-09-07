import { create } from 'zustand';

import { IMealCategory, IMealPlan } from '@/types';

interface MealState {
    // state
    mealCategories: IMealCategory[];
    mealPlans: IMealPlan[];

    // setter func
    setMealCategories: (mealCategories: IMealCategory[]) => void;
    setMealPlans: (mealPlans: IMealPlan[]) => void;
}

export const useMealStore = create<MealState>(set => ({
    // initial state
    mealCategories: [],
    mealPlans: [],
    // setter func
    setMealCategories: mealCategories => set({ mealCategories }),
    setMealPlans: mealPlans => set({ mealPlans }),
}));
