import { IRecipe, IRecipeCategory } from '@/types/api/recipe';
import { create } from 'zustand';
import { IGetMasterResponse } from '@/types/api/master';
import { DIALOG_NAME } from '@/constants';

type DialogPayload = {
    [DIALOG_NAME.RECIPE_CATEGORY_SETTING]: {
        onAction: (value: IRecipeCategory) => void;
    };
};

type DialogsState = {
    [K in keyof DialogPayload]: {
        isOpen: boolean;
        payload: DialogPayload[K];
    };
};

type LoadingState = {
    recipe: boolean;
    recipeCategory: boolean;
};

const initialDialogsState: DialogsState = {
    [DIALOG_NAME.RECIPE_CATEGORY_SETTING]: {
        isOpen: false,
        payload: {
            onAction: () => {},
        },
    },
};

interface RecipeState {
    // ローカル状態
    recipes: IRecipe[];

    // ローディング状態
    isLoadings: LoadingState;

    // マスターデータ
    categories: IRecipeCategory[];

    // ダイアログの状態
    dialogs: DialogsState;

    // レシピ一覧のアクション
    setRecipes: (recipes: IRecipe[]) => void;

    // ローディング状態のアクション
    setIsLoadings: (name: keyof LoadingState, isLoading: boolean) => void;

    // マスターデータのアクション
    setCategories: (
        categories: IGetMasterResponse['data']['recipeCategories'],
    ) => void;

    // ダイアログのアクション
    openDialog: <K extends keyof DialogPayload>(
        dialogName: K,
        payload: DialogPayload[K],
    ) => void;
    closeDialog: (dialogName: keyof DialogPayload) => void;
}

export const useRecipeStore = create<RecipeState>(set => ({
    // 初期状態
    recipes: [],
    isLoadings: {
        recipeCategory: false,
        recipe: false,
    },
    categories: [],
    dialogs: initialDialogsState,

    // レシピ一覧のアクション
    setRecipes: recipes => {
        set({ recipes });
    },

    // ローディング状態のアクション
    setIsLoadings: (name: keyof LoadingState, isLoading: boolean) => {
        set(state => ({
            isLoadings: { ...state.isLoadings, [name]: isLoading },
        }));
    },

    // マスターデータのアクション
    setCategories: categories => {
        set({ categories });
    },

    // ダイアログのアクション
    openDialog: (dialogName, payload) =>
        set(state => ({
            dialogs: {
                ...state.dialogs,
                [dialogName]: { isOpen: true, payload },
            },
        })),
    closeDialog: dialogName =>
        set(state => ({
            dialogs: {
                ...state.dialogs,
                [dialogName]: {
                    ...state.dialogs[dialogName],
                    isOpen: false,
                },
            },
        })),
}));
