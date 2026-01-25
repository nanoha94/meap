'use client';
import React from 'react';
import { useGlobalStore } from '@/stores';
import { DialogConfig, DialogData } from '@/types';

/**
 * Dialogを管理するカスタムフック
 * 複数のダイアログが順番に表示される
 * @returns { openDialog, closeDialog }
 */
export const useDialog = () => {
    const setDialogs = useGlobalStore(state => state.setDialogs);

    /**
     * 現在のダイアログを閉じる
     * キューから次のダイアログを自動的に表示
     */
    const closeDialog = React.useCallback(() => {
        setDialogs(prev => {
            if (prev.length > 1) {
                return prev.slice(1);
            } else {
                return [];
            }
        });
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
                    // ダイアログを閉じる
                    closeDialog();
                },
            };

            // 既にダイアログが表示中の場合は、新しいダイアログを先頭に追加（前面に表示）
            // dialogsの最新の状態を直接参照するため、currentDialogの依存を削除
            setDialogs(prev => {
                if (prev.length > 0) {
                    return [newDialog, ...prev];
                } else {
                    return [newDialog];
                }
            });
        },
        [closeDialog, setDialogs],
    );

    return {
        openDialog,
        closeDialog,
    };
};
