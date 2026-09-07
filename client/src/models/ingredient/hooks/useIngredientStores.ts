"use client";
import { create } from 'zustand';

import { IIngredientUnit } from '@/types';

interface IngredientState {
   // state
    units: IIngredientUnit[];

    // setter func
    setUnits: (units: IIngredientUnit[]) => void;
}

export const useIngredientStore = create<IngredientState>(set => ({
    // initial state
    units: [],

    // setter func
    setUnits: units => {
        set({ units });
    },
}));
