import { create } from 'zustand';

import { AlertDialogData, DialogData, Snackbar } from '@/types';

interface GlobalState {
    // state
    loadingCount: number; // ローディング中のリクエスト数
    visibleLoadingAnimation: boolean; // ローディングアニメーションの表示条件（カスタム条件）
    snackbars: Snackbar[]; // スナックバー
    alertDialogs: AlertDialogData[]; // ダイアログのキュー（先頭が現在表示中、それ以降が待機中）
    dialogs: DialogData[]; // ダイアログのキュー（先頭が現在表示中、それ以降が待機中）

    // setter func
    incrementLoadingCount: () => void; // ローディングカウンターを増やす
    decrementLoadingCount: () => void; // ローディングカウンターを減らす
    resetLoadingCount: () => void; // ローディングカウンターをリセット（ページ遷移時など）
    setLoadingCondition: (visible: boolean) => void;
    setSnackbars: (
        snackbars: Snackbar[] | ((prev: Snackbar[]) => Snackbar[]),
    ) => void;
    setAlertDialogs: (
        dialogs:
            | AlertDialogData[]
            | ((prev: AlertDialogData[]) => AlertDialogData[]),
    ) => void;
    setDialogs: (
        dialogs: DialogData[] | ((prev: DialogData[]) => DialogData[]),
    ) => void;
}

export const useGlobalStore = create<GlobalState>(set => ({
    // initial state
    loadingCount: 0,
    visibleLoadingAnimation: true, // デフォルトは表示可能
    snackbars: [],
    alertDialogs: [],
    dialogs: [],

    // setter func
    incrementLoadingCount: () => {
        set(state => ({
            loadingCount: state.loadingCount + 1,
        }));
    },
    decrementLoadingCount: () => {
        set(state => ({
            loadingCount: Math.max(0, state.loadingCount - 1),
        }));
    },
    resetLoadingCount: () => {
        set({
            loadingCount: 0,
        });
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
                    dialogsOrUpdater as (prev: AlertDialogData[]) => AlertDialogData[]
                )(state.alertDialogs),
            }));
        } else {
            set({ alertDialogs: dialogsOrUpdater });
        }
    },
    setDialogs: dialogsOrUpdater => {
        if (typeof dialogsOrUpdater === 'function') {
            set(state => ({
                dialogs: (
                    dialogsOrUpdater as (prev: DialogData[]) => DialogData[]
                )(state.dialogs),
            }));
        } else {
            set({ dialogs: dialogsOrUpdater });
        }
    },
}));
