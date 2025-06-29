import { create } from 'zustand';
import {
    IGetShoppingItemsResponse,
    IShoppingCategory,
    IShoppingItem,
} from '@/types/api';

type DialogPayload = {
    itemSetting: IShoppingItem | undefined;
    categorySetting: undefined; // データが不要な場合はundefined
    // 他のダイアログのペイロードをここに追加
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

    // ダイアログの状態
    dialogs: DialogsState;

    // アイテムのアクション
    setItems: (items: IGetShoppingItemsResponse['data']) => void;

    // カテゴリーのアクション
    setCategories: (categories: IShoppingCategory[]) => void;

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
    dialogs: initialDialogsState,

    // アイテムのアクション
    setItems: items => {
        set({ items });
    },

    // カテゴリーのアクション
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
