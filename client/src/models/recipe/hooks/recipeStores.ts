import {
    IGetRecipesResponse,
    IIngredient,
    IRecipeCategory,
    ISeasoning,
} from '@/types/api/recipe';
import { create } from 'zustand';
import { RECIPE_SETTING_DIALOG_EDIT_MODE } from '../constants';
import { IGetMasterResponse } from '@/types/api/master';

type DialogPayload = {
    categorySetting: {
        editMode: (typeof RECIPE_SETTING_DIALOG_EDIT_MODE)[keyof typeof RECIPE_SETTING_DIALOG_EDIT_MODE];
        onAction: (value: IRecipeCategory) => void;
    };
    ingredientSetting: {
        item: IIngredient | undefined;
        editMode: (typeof RECIPE_SETTING_DIALOG_EDIT_MODE)[keyof typeof RECIPE_SETTING_DIALOG_EDIT_MODE];
        onAction: (value: IIngredient) => void;
    };
    seasoningSetting: {
        item: ISeasoning | undefined;
        editMode: (typeof RECIPE_SETTING_DIALOG_EDIT_MODE)[keyof typeof RECIPE_SETTING_DIALOG_EDIT_MODE];
        onAction: (value: ISeasoning) => void;
    };
};

type DialogsState = {
    [K in keyof DialogPayload]: {
        isOpen: boolean;
        payload: DialogPayload[K];
    };
};

const initialDialogsState: DialogsState = {
    categorySetting: {
        isOpen: false,
        payload: {
            editMode: RECIPE_SETTING_DIALOG_EDIT_MODE.CREATE,
            onAction: () => {},
        },
    },
    ingredientSetting: {
        isOpen: false,
        payload: {
            item: undefined,
            editMode: RECIPE_SETTING_DIALOG_EDIT_MODE.CREATE,
            onAction: () => {},
        },
    },
    seasoningSetting: {
        isOpen: false,
        payload: {
            item: undefined,
            editMode: RECIPE_SETTING_DIALOG_EDIT_MODE.CREATE,
            onAction: () => {},
        },
    },
};

interface RecipeState {
    // ローカル状態
    recipes: IGetRecipesResponse['data'];

    // マスターデータ
    categories: IGetMasterResponse['recipeCategories'];
    ingredientUnits: IGetMasterResponse['ingredientUnits'];
    seasoningUnits: IGetMasterResponse['seasoningUnits'];

    // ダイアログの状態
    dialogs: DialogsState;

    // レシピ一覧のアクション
    setRecipes: (recipes: IGetRecipesResponse['data']) => void;

    // マスターデータのアクション
    setCategories: (categories: IGetMasterResponse['recipeCategories']) => void;
    setIngredientUnits: (
        ingredientUnits: IGetMasterResponse['ingredientUnits'],
    ) => void;
    setSeasoningUnits: (
        seasoningUnits: IGetMasterResponse['seasoningUnits'],
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
    categories: [],
    ingredientUnits: [],
    seasoningUnits: [],
    dialogs: initialDialogsState,

    // レシピ一覧のアクション
    setRecipes: recipes => {
        set({ recipes });
    },

    // マスターデータのアクション
    setCategories: categories => {
        set({ categories });
    },
    setIngredientUnits: ingredientUnits => {
        set({ ingredientUnits });
    },
    setSeasoningUnits: seasoningUnits => {
        set({ seasoningUnits });
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
