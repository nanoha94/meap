import { create } from 'zustand';
import { IGetMasterResponse } from '@/types/api/master';
import { IIngredient } from '@/types/api/ingredient';
import { DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';

export type DialogPayload = {
    [DIALOG_NAME.INGREDIENT_ADD_EDIT]: {
        item: IIngredient | undefined;
        editMode: (typeof DIALOG_EDIT_MODE)[keyof typeof DIALOG_EDIT_MODE];
        onAction: (value: IIngredient) => void;
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
    // マスターデータ
    ingredientUnits: IGetMasterResponse['data']['ingredientUnits'];

    // ダイアログの状態
    dialogs: DialogsState;

    // マスターデータのアクション
    setIngredientUnits: (
        ingredientUnits: IGetMasterResponse['data']['ingredientUnits'],
    ) => void;

    // ダイアログのアクション
    openDialog: <K extends keyof DialogPayload>(
        dialogName: K,
        payload: DialogPayload[K],
    ) => void;
    closeDialog: (dialogName: keyof DialogPayload) => void;
}

export const useIngredientStore = create<IngredientState>(set => ({
    // 初期状態
    ingredientUnits: [],
    dialogs: initialDialogsState,

    // マスターデータのアクション
    setIngredientUnits: ingredientUnits => {
        set({ ingredientUnits });
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
