import { create } from 'zustand';
import { IShoppingCategory, IShoppingItem } from '@/types/api';
import { EDIT_MODE, DIALOG_NAME } from '@/constants';

type DialogPayload = {
    [DIALOG_NAME.SHOPPING_ITEM_ADD_EDIT]: {
        item: IShoppingItem | undefined;
        editMode: (typeof EDIT_MODE)[keyof typeof EDIT_MODE];
    };
};

type DialogsState = {
    [K in keyof DialogPayload]: {
        isOpen: boolean;
        payload: DialogPayload[K];
    };
};

interface ShoppingState {
    // state
    serverItems: IShoppingItem[]; // DBに保存されているアイテム一覧
    items: IShoppingItem[]; // ローカルのアイテム一覧
    categories: IShoppingCategory[]; // ローカルのカテゴリー一覧
    isLoadingCategories: boolean; // カテゴリーのローディング状態
    isLoadingItems: boolean; // アイテムのローディング状態
    dialogs: DialogsState; // ダイアログの状態

    // setter func
    setServerItems: (items: IShoppingItem[]) => void;
    setItems: (items: IShoppingItem[]) => void;
    setCategories: (categories: IShoppingCategory[]) => void;
    setIsLoadingCategories: (isLoading: boolean) => void;
    setIsLoadingItems: (isLoading: boolean) => void;

    // action func
    openDialog: <K extends keyof DialogPayload>(
        dialogName: K,
        payload: DialogPayload[K],
    ) => void;
    closeDialog: (dialogName: keyof DialogPayload) => void;
}

const initialDialogsState: DialogsState = {
    [DIALOG_NAME.SHOPPING_ITEM_ADD_EDIT]: {
        isOpen: false,
        payload: { item: undefined, editMode: EDIT_MODE.CREATE },
    },
};

export const useShoppingStore = create<ShoppingState>(set => ({
    // initial state
    serverItems: [],
    items: [],
    categories: [],
    isLoadingCategories: false,
    isLoadingItems: false,
    dialogs: initialDialogsState,

    // setter func
    setServerItems: (items: IShoppingItem[]) => {
        set({ serverItems: items });
    },

    setItems: (items: IShoppingItem[]) => {
        set({ items });
    },

    setCategories: (categories: IShoppingCategory[]) => {
        set({ categories });
    },

    setIsLoadingCategories: (isLoading: boolean) => {
        set({ isLoadingCategories: isLoading });
    },

    setIsLoadingItems: (isLoading: boolean) => {
        set({ isLoadingItems: isLoading });
    },

    // action func
    openDialog: <K extends keyof DialogPayload>(
        dialogName: K,
        payload: DialogPayload[K],
    ) =>
        set(state => ({
            dialogs: {
                ...state.dialogs,
                [dialogName]: { isOpen: true, payload },
            },
        })),
    closeDialog: (dialogName: keyof DialogPayload) =>
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
