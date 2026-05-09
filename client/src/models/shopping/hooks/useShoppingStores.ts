import { create } from 'zustand';

import { IShoppingCategory, IShoppingItem } from '@/types';

interface ShoppingState {
    // state
    serverItems: IShoppingItem[]; // DBに保存されているアイテム一覧
    items: IShoppingItem[]; // ローカルのアイテム一覧
    categories: IShoppingCategory[]; // ローカルのカテゴリー一覧
    /** 削除直後に続く一括更新の成功スナックバーを1回だけ出さない */
    isSkipNextBulkSnackbar: boolean;

    // setter func
    setServerItems: (items: IShoppingItem[]) => void;
    setItems: (items: IShoppingItem[]) => void;
    setCategories: (categories: IShoppingCategory[]) => void;
    setIsSkipNextBulkSnackbar: (value: boolean) => void;
}

export const useShoppingStore = create<ShoppingState>(set => ({
    // initial state
    serverItems: [],
    items: [],
    categories: [],
    isSkipNextBulkSnackbar: false,

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

    setIsSkipNextBulkSnackbar: (value: boolean) => {
        set({ isSkipNextBulkSnackbar: value });
    },
}));
