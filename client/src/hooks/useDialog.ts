'use client';
import React from 'react';

import { useGlobalStore } from '@/stores';
import { DialogConfig, DialogData } from '@/types';
import { ALERT_DIALOG_CONFIGS } from '@/constants';
import { useAlertDialog } from './useAlertDialog';

/**
 * Dialogを管理するカスタムフック
 * 複数のダイアログが順番に表示される
 * @returns { openDialog, closeDialog }
 */
export const useDialog = () => {
    // store
    const setDialogs = useGlobalStore(state => state.setDialogs);

    // hook
    const { openAlertDialog } = useAlertDialog();

    /**
     * 現在のダイアログを閉じる
     * キューから次のダイアログを自動的に表示
     */
    const closeDialog = React.useCallback((isCheck?: boolean) => {
        const isCheckBeforeClose = isCheck ?? useGlobalStore.getState().dialogs[0]?.config?.isCheckBeforeClose ?? false;
        if (isCheckBeforeClose) {
            openAlertDialog(ALERT_DIALOG_CONFIGS.unsavedChanges(), () => {
                closeDialog(false);
            });
        } else {
            setDialogs(prev => {
                if (prev.length > 1) {
                    return prev.slice(0, -1);
                } else {
                    return [];
                }
            });
        }
    }, [setDialogs]);

    /**
     * ダイアログを開く
     * 既にダイアログが表示中の場合は、新しいダイアログを前面に表示
     * @param config ダイアログの設定
     * @param onClose ダイアログが閉じられたときのコールバック（オプション）
     */
    const openDialog = React.useCallback(
        (config: DialogConfig, onClose?: () => void) => {
            const newDialog: DialogData = {
                isOpen: true,
                config,
                onClose: () => {
                    // 引数で渡されたonCloseを実行
                    if (onClose) {
                        onClose();
                    }
                    closeDialog();
                },
            };

            // 既にダイアログが表示中の場合は、新しいダイアログを先頭に追加（前面に表示）
            // dialogsの最新の状態を直接参照するため、currentDialogの依存を削除
            setDialogs(prev => {
                if (prev.length > 0) {
                    return [...prev, newDialog];
                } else {
                    return [newDialog];
                }
            });
        },
        [closeDialog, setDialogs],
    );

    /**
     * 現在表示中のダイアログの config を部分的に更新する
     * customButton など、開いた後に状態で変えたい項目の更新に使用する
     */
    const updateCurrentDialogConfig = React.useCallback(
        (configPatch: Partial<DialogConfig>) => {
            setDialogs(prev => {
                if (prev.length === 0) return prev;
                return [
                    {
                        ...prev[0],
                        config: { ...prev[0].config, ...configPatch },
                    },
                    ...prev.slice(1),
                ];
            });
        },
        [setDialogs],
    );

    return {
        openDialog,
        closeDialog,
        updateCurrentDialogConfig,
    };
};
