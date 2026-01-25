import { create } from 'zustand';
import {
    IIngredientCategory,
    IIngredientUnit,
} from '@/types/api';
import { LOADING_STATE_KEYS } from '../constants';

type LoadingState = {
    [LOADING_STATE_KEYS.INGREDIENT]: boolean;
    [LOADING_STATE_KEYS.INGREDIENT_CATEGORY]: boolean;
};

interface IngredientState {
   // state
    units: IIngredientUnit[];
    categories: IIngredientCategory[];
    isLoadings: LoadingState;

    // setter func
    setUnits: (units: IIngredientUnit[]) => void;
    setCategories: (categories: IIngredientCategory[]) => void;
    setIsLoadings: (name: keyof LoadingState, isLoading: boolean) => void;
}

export const useIngredientStore = create<IngredientState>(set => ({
    // initial state
    units: [],
    categories: [],
    isLoadings: {
        [LOADING_STATE_KEYS.INGREDIENT]: false,
        [LOADING_STATE_KEYS.INGREDIENT_CATEGORY]: false,
    },

    // setter func
    setUnits: units => {
        set({ units });
    },
    setCategories: (categories: IIngredientCategory[]) => {
        set({ categories });
    },
    setIsLoadings: (name: keyof LoadingState, isLoading: boolean) => {
        set(state => ({
            isLoadings: { ...state.isLoadings, [name]: isLoading },
        }));
    },
}));
