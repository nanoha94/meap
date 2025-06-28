import { create } from 'zustand';
import { IGetShoppingItemsResponse, IShoppingCategory } from '@/types/api';

interface ShoppingState {
    // アイテムの状態
    items: IGetShoppingItemsResponse['data'];

    // カテゴリーの状態
    categories: IShoppingCategory[];

    // アイテムのアクション
    setItems: (items: IGetShoppingItemsResponse['data']) => void;

    // カテゴリーのアクション
    setCategories: (categories: IShoppingCategory[]) => void;
}

export const useShoppingStore = create<ShoppingState>(set => ({
    // 初期状態
    items: [],
    categories: [],

    // アイテムのアクション
    setItems: items => {
        set({ items });
    },

    // カテゴリーのアクション
    setCategories: categories => {
        set({ categories });
    },
}));
