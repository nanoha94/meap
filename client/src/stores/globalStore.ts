import { create } from 'zustand';

interface GlobalState {
    // ローディング状態
    isLoading: boolean;
    // ローディングアニメーションの表示条件（カスタム条件）
    visibleLoadingAnimation: boolean;

    // アクション
    setIsLoading: (isLoading: boolean) => void;
    setLoadingCondition: (visible: boolean) => void;
}

export const useGlobalStore = create<GlobalState>(set => ({
    // 初期状態
    isLoading: false,
    visibleLoadingAnimation: true, // デフォルトは表示可能

    // アクション
    setIsLoading: (isLoading: boolean) => {
        set({ isLoading: isLoading });
    },
    setLoadingCondition: (visible: boolean) => {
        set({ visibleLoadingAnimation: visible });
    },
}));
