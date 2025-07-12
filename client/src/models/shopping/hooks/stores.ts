import { create } from 'zustand';
import {
    IGetShoppingItemsResponse,
    IShoppingCategory,
    IShoppingItem,
} from '@/types/api';
import { SHOPPING_ITEM_EDIT_MODE } from '../constants';

type DialogPayload = {
    itemSetting: {
        item: IShoppingItem | undefined;
        editMode: (typeof SHOPPING_ITEM_EDIT_MODE)[keyof typeof SHOPPING_ITEM_EDIT_MODE];
    };
    categorySetting: undefined; // データが不要な場合はundefined
};

type DialogsState = {
    [K in keyof DialogPayload]: {
        isOpen: boolean;
        payload: DialogPayload[K];
    };
};

interface ShoppingState {
    // サーバー状態
    serverItems: IGetShoppingItemsResponse['data'];

    // ローカル状態
    items: IGetShoppingItemsResponse['data'];
    categories: IShoppingCategory[];

    // ローディング状態
    isLoadingCategories: boolean;
    isLoadingItems: boolean;

    // ダイアログの状態
    dialogs: DialogsState;

    // サーバー状態のアクション
    setServerItems: (items: IGetShoppingItemsResponse['data']) => void;

    // アイテムのアクション
    setItems: (items: IGetShoppingItemsResponse['data']) => void;

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
    itemSetting: {
        isOpen: false,
        payload: { item: undefined, editMode: SHOPPING_ITEM_EDIT_MODE.CREATE },
    },
    categorySetting: { isOpen: false, payload: undefined },
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
