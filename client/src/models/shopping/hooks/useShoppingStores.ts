import { create } from 'zustand';

import { IShoppingCategory, IShoppingItem } from '@/types';

interface ShoppingState {
    // state
    serverItems: IShoppingItem[]; // DBに保存されているアイテム一覧
    items: IShoppingItem[]; // ローカルのアイテム一覧
    categories: IShoppingCategory[]; // ローカルのカテゴリー一覧

    // setter func
    setServerItems: (items: IShoppingItem[]) => void;
    setItems: (items: IShoppingItem[]) => void;
    setCategories: (categories: IShoppingCategory[]) => void;
}

export const useShoppingStore = create<ShoppingState>(set => ({
    // initial state
    serverItems: [],
    items: [],
    categories: [],

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
}));
