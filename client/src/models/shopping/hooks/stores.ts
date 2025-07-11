import { create } from 'zustand';
import {
    IGetShoppingItemsResponse,
    IShoppingCategory,
    IShoppingItem,
} from '@/types/api';

type DialogPayload = {
    itemSetting: IShoppingItem | undefined;
    categorySetting: undefined; // データが不要な場合はundefined
};

type DialogsState = {
    [K in keyof DialogPayload]: {
        isOpen: boolean;
        payload: DialogPayload[K];
    };
};

interface ShoppingState {
    // アイテムの状態
    items: IGetShoppingItemsResponse['data'];

    // カテゴリーの状態
    categories: IShoppingCategory[];

    // ローディング状態
    isLoadingCategories: boolean;
    isLoadingItems: boolean;

    // ダイアログの状態
    dialogs: DialogsState;

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
    itemSetting: { isOpen: false, payload: undefined },
    categorySetting: { isOpen: false, payload: undefined },
};

export const useShoppingStore = create<ShoppingState>(set => ({
    // 初期状態
    items: [],
    categories: [],
    isLoadingCategories: false,
    isLoadingItems: false,
    dialogs: initialDialogsState,

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
