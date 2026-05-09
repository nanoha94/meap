"use client";
import { create } from 'zustand';

import { IIngredientCategory, IIngredientUnit } from '@/types';

interface IngredientState {
   // state
    units: IIngredientUnit[];
    categories: IIngredientCategory[];

    // setter func
    setUnits: (units: IIngredientUnit[]) => void;
    setCategories: (categories: IIngredientCategory[]) => void;
}

export const useIngredientStore = create<IngredientState>(set => ({
    // initial state
    units: [],
    categories: [],

    // setter func
    setUnits: units => {
        set({ units });
    },
    setCategories: (categories: IIngredientCategory[]) => {
        set({ categories });
    },
}));
