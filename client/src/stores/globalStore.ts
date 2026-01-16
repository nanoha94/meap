import { Snackbar } from '@/types';
import { create } from 'zustand';

interface GlobalState {
    // state
    isLoading: boolean; // ローディング状態
    visibleLoadingAnimation: boolean; // ローディングアニメーションの表示条件（カスタム条件）
    snackbars: Snackbar[]; // スナックバー

    // setter func
    setIsLoading: (isLoading: boolean) => void;
    setLoadingCondition: (visible: boolean) => void;
    setSnackbars: (
        snackbars: Snackbar[] | ((prev: Snackbar[]) => Snackbar[]),
    ) => void;
}

export const useGlobalStore = create<GlobalState>(set => ({
    // initial state
    isLoading: false,
    visibleLoadingAnimation: true, // デフォルトは表示可能
    snackbars: [],

    // setter func
    setIsLoading: (isLoading: boolean) => {
        set({ isLoading: isLoading });
    },
    setLoadingCondition: (visible: boolean) => {
        set({ visibleLoadingAnimation: visible });
    },
    setSnackbars: snackbarsOrUpdater => {
        if (typeof snackbarsOrUpdater === 'function') {
            set(state => ({
                snackbars: (
                    snackbarsOrUpdater as (prev: Snackbar[]) => Snackbar[]
                )(state.snackbars),
            }));
        } else {
            set({ snackbars: snackbarsOrUpdater });
        }
    },
}));
