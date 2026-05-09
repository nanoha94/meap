'use client';
import React from 'react';

import { useGlobalStore } from '@/stores';
import { AlertDialogConfig, AlertDialogData } from '@/types';

/**
 * AlertDialogを管理するカスタムフック
 * 複数のダイアログが順番に表示される
 * @returns { openAlertDialog, closeAlertDialog }
 */
export const useAlertDialog = () => {
    // store
    const alertDialogs = useGlobalStore(state => state.alertDialogs);
    const setAlertDialogs = useGlobalStore(state => state.setAlertDialogs);

    // state
    const currentDialog = alertDialogs[0] || null;

    /**
     * 現在のダイアログを閉じる
     * キューから次のダイアログを自動的に表示
     */
    const closeAlertDialog = React.useCallback(() => {
        setAlertDialogs(prev => {
            if (prev.length > 1) {
                return prev.slice(1);
            } else {
                return [];
            }
        });
    }, [setAlertDialogs]);

    /**
     * ダイアログを開く
     * 既にダイアログが表示中の場合は、新しいダイアログを前面に表示
     * @param config ダイアログの設定
     * @param onAction アクションボタンが押されたときのコールバック
     */
    const openAlertDialog = React.useCallback(
        (config: AlertDialogConfig, onAction: () => void) => {
            const newDialog: AlertDialogData = {
                isOpen: true,
                config,
                onCancel: () => {
                    closeAlertDialog();
                },
                onAction: () => {
                    onAction();
                    closeAlertDialog();
                },
            };

            // 既にダイアログが表示中の場合は、新しいダイアログを先頭に追加（前面に表示）
            if (currentDialog) {
                setAlertDialogs(prev => [newDialog, ...prev]);
            } else {
                // 表示中でない場合はすぐに表示
                setAlertDialogs([newDialog]);
            }
        },
        [currentDialog, closeAlertDialog, setAlertDialogs],
    );

    return {
        openAlertDialog,
        closeAlertDialog,
    };
};
