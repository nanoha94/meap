import { create } from 'zustand';
import { IShoppingCategory, IShoppingItem } from '@/types/api';
import { DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';

type DialogPayload = {
    [DIALOG_NAME.SHOPPING_ITEM_ADD_EDIT]: {
        item: IShoppingItem | undefined;
        editMode: (typeof DIALOG_EDIT_MODE)[keyof typeof DIALOG_EDIT_MODE];
    };
};

type DialogsState = {
    [K in keyof DialogPayload]: {
        isOpen: boolean;
        payload: DialogPayload[K];
    };
};

interface ShoppingState {
    // サーバー状態
    serverItems: IShoppingItem[];

    // ローカル状態
    items: IShoppingItem[];
    categories: IShoppingCategory[];

    // ローディング状態
    isLoadingCategories: boolean;
    isLoadingItems: boolean;

    // ダイアログの状態
    dialogs: DialogsState;

    // サーバー状態のアクション
    setServerItems: (items: IShoppingItem[]) => void;

    // アイテムのアクション
    setItems: (items: IShoppingItem[]) => void;

    // カテゴリーのアクション
    setCategories: (categories: IShoppingCategory[]) => void;

    // ローディング状態のアクション
    setIsLoadingCategories: (isLoading: boolean) => void;
    setIsLoadingItems: (isLoading: boolean) => void;

    // ダイアログのアクション
    openDialog: <K extends keyof DialogPayload>(
        dialogName: K,
        payload: DialogPayload[K],
    ) => void;
    closeDialog: (dialogName: keyof DialogPayload) => void;
}

const initialDialogsState: DialogsState = {
    [DIALOG_NAME.SHOPPING_ITEM_ADD_EDIT]: {
        isOpen: false,
        payload: { item: undefined, editMode: DIALOG_EDIT_MODE.CREATE },
    },
};

export const useShoppingStore = create<ShoppingState>(set => ({
    // 初期状態
    serverItems: [],
    items: [],
    categories: [],
    isLoadingCategories: false,
    isLoadingItems: false,
    dialogs: initialDialogsState,

    // サーバー状態のアクション
    setServerItems: items => {
        set({ serverItems: items });
    },

    // アイテムのアクション
    setItems: items => {
        set({ items });
    },

    // カテゴリーのアクション
    setCategories: categories => {
        set({ categories });
    },

    // ローディング状態のアクション
    setIsLoadingCategories: isLoading => {
        set({ isLoadingCategories: isLoading });
    },
    setIsLoadingItems: isLoading => {
        set({ isLoadingItems: isLoading });
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
