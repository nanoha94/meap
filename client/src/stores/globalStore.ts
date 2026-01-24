import { AlertDialogData, Snackbar } from '@/types';
import { create } from 'zustand';

interface GlobalState {
    // state
    isLoading: boolean; // ローディング状態
    visibleLoadingAnimation: boolean; // ローディングアニメーションの表示条件（カスタム条件）
    snackbars: Snackbar[]; // スナックバー
    alertDialogs: AlertDialogData[]; // ダイアログのキュー（先頭が現在表示中、それ以降が待機中）

    // setter func
    setIsLoading: (isLoading: boolean) => void;
    setLoadingCondition: (visible: boolean) => void;
    setSnackbars: (
        snackbars: Snackbar[] | ((prev: Snackbar[]) => Snackbar[]),
    ) => void;
    setAlertDialogs: (
        dialogs:
            | AlertDialogData[]
            | ((prev: AlertDialogData[]) => AlertDialogData[]),
    ) => void;
}

export const useGlobalStore = create<GlobalState>(set => ({
    // initial state
    isLoading: false,
    visibleLoadingAnimation: true, // デフォルトは表示可能
    snackbars: [],
    alertDialogs: [],

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
    setAlertDialogs: dialogsOrUpdater => {
        if (typeof dialogsOrUpdater === 'function') {
            set(state => ({
                alertDialogs: (
                    dialogsOrUpdater as (
                        prev: AlertDialogData[],
                    ) => AlertDialogData[]
                )(state.alertDialogs),
            }));
        } else {
            set({ alertDialogs: dialogsOrUpdater });
        }
    },
}));
