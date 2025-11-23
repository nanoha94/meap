import { create } from 'zustand';
import {
    IIngredientCategory,
    IIngredientItem,
    IIngredientUnit,
} from '@/types/api/ingredient';
import { DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';
import { LOADING_STATE_KEYS } from '../constants';

export type DialogPayload = {
    [DIALOG_NAME.INGREDIENT_ADD_EDIT]: {
        item: IIngredientItem | undefined;
        editMode: (typeof DIALOG_EDIT_MODE)[keyof typeof DIALOG_EDIT_MODE];
        onAction: (value: IIngredientItem) => void;
    };
    [DIALOG_NAME.INGREDIENT_CATEGORY_SETTING]: {
        onAction: () => void;
    };
};

type DialogsState = {
    [K in keyof DialogPayload]: {
        isOpen: boolean;
        payload: DialogPayload[K];
    };
};

type LoadingState = {
    [LOADING_STATE_KEYS.INGREDIENT]: boolean;
    [LOADING_STATE_KEYS.INGREDIENT_CATEGORY]: boolean;
};

const initialDialogsState: DialogsState = {
    [DIALOG_NAME.INGREDIENT_ADD_EDIT]: {
        isOpen: false,
        payload: {
            item: undefined,
            editMode: DIALOG_EDIT_MODE.CREATE,
            onAction: () => {},
        },
    },
    [DIALOG_NAME.INGREDIENT_CATEGORY_SETTING]: {
        isOpen: false,
        payload: {
            onAction: () => {},
        },
    },
};

interface IngredientState {
    // 単位（マスターデータ）
    units: IIngredientUnit[];

    // カテゴリー
    categories: IIngredientCategory[];

    // ローディング状態
    isLoadings: LoadingState;

    // ダイアログの状態
    dialogs: DialogsState;

    // マスターデータのアクション
    setUnits: (units: IIngredientUnit[]) => void;

    // カテゴリーのアクション
    setCategories: (categories: IIngredientCategory[]) => void;

    // ローディング状態のアクション
    setIsLoadings: (name: keyof LoadingState, isLoading: boolean) => void;

    // ダイアログのアクション
    openDialog: <K extends keyof DialogPayload>(
        dialogName: K,
        payload: DialogPayload[K],
    ) => void;
    closeDialog: (dialogName: keyof DialogPayload) => void;
}

export const useIngredientStore = create<IngredientState>(set => ({
    // 初期状態
    units: [],
    categories: [],
    isLoadings: {
        [LOADING_STATE_KEYS.INGREDIENT]: false,
        [LOADING_STATE_KEYS.INGREDIENT_CATEGORY]: false,
    },
    dialogs: initialDialogsState,

    // マスターデータのアクション
    setUnits: units => {
        set({ units });
    },

    // カテゴリーのアクション
    setCategories: (categories: IIngredientCategory[]) => {
        set({ categories });
    },

    // ローディング状態のアクション
    setIsLoadings: (name: keyof LoadingState, isLoading: boolean) => {
        set(state => ({
            isLoadings: { ...state.isLoadings, [name]: isLoading },
        }));
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
