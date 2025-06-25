import { create } from 'zustand';
import { IGetShoppingItemsResponse, IShoppingCategory } from '@/types/api';

interface ShoppingState {
    // アイテムの状態
    items: IGetShoppingItemsResponse['data'];

    // カテゴリーの状態
    categories: IShoppingCategory[];

    // ローディング状態
    isShowLoading: boolean;

    // ドラッグ&ドロップの状態
    activeId: string | null;

    // アイテムのアクション
    setItems: (items: IGetShoppingItemsResponse['data']) => void;

    // カテゴリーのアクション
    setCategories: (categories: IShoppingCategory[]) => void;

    // ローディングアニメーションを表示するか
    // ページリロード時、カテゴリ―更新時に表示する
    setIsShowLoading: (show: boolean) => void;

    // ドラッグ&ドロップのアクション
    setActiveId: (id: string | null) => void;
}

export const useShoppingStore = create<ShoppingState>(set => ({
    // 初期状態
    items: [],
    categories: [],
    isShowLoading: true,
    activeId: null,

    // アイテムのアクション
    setItems: items => {
        set({ items });
    },

    // カテゴリーのアクション
    setCategories: categories => {
        set({ categories });
    },

    // ローディングのアクション
    setIsShowLoading: show => set({ isShowLoading: show }),

    // ドラッグ&ドロップのアクション
    setActiveId: id => set({ activeId: id }),
}));
