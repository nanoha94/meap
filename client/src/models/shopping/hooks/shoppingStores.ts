import { create } from 'zustand';
import { IShoppingCategory, IShoppingItem } from '@/types/api';

interface ShoppingState {
    // state
    serverItems: IShoppingItem[]; // DBに保存されているアイテム一覧
    items: IShoppingItem[]; // ローカルのアイテム一覧
    categories: IShoppingCategory[]; // ローカルのカテゴリー一覧
    isLoadingCategories: boolean; // カテゴリーのローディング状態
    isLoadingItems: boolean; // アイテムのローディング状態

    // setter func
    setServerItems: (items: IShoppingItem[]) => void;
    setItems: (items: IShoppingItem[]) => void;
    setCategories: (categories: IShoppingCategory[]) => void;
    setIsLoadingCategories: (isLoading: boolean) => void;
    setIsLoadingItems: (isLoading: boolean) => void;
}

export const useShoppingStore = create<ShoppingState>(set => ({
    // initial state
    serverItems: [],
    items: [],
    categories: [],
    isLoadingCategories: false,
    isLoadingItems: false,

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
}));
