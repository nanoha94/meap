'use client';
import React from 'react';
import { v4 as uuidv4 } from 'uuid';

import { useGlobalStore } from '@/stores';

export const useSnackbars = () => {
    // store
    const snackbars = useGlobalStore(state => state.snackbars);
    const setSnackbars = useGlobalStore(state => state.setSnackbars);

    // hook
    const previousMessageRef = React.useRef<string | null>(null);

    // スナックバー削除の共通処理（アニメーション付き）
    const removeSnackbar = React.useCallback(
        (id: string) => {
            // まずは非表示にする
            setSnackbars(prev =>
                prev.map(v => (v.id === id ? { ...v, isOpen: false } : v)),
            );

            // 100ms後に削除（アニメーション完了を待って削除）
            setTimeout(() => {
                setSnackbars(prev => prev.filter(v => v.id !== id));

                // スナックバーが空の場合、前回のメッセージをクリア
                if (snackbars.length === 0) {
                    previousMessageRef.current = null;
                }
            }, 100);
        },
        [snackbars],
    );

    const addSnackbar = React.useCallback(
        (type: 'success' | 'error', message: string) => {
            // メッセージが存在し、前回のメッセージと異なる場合のみ追加
            if (message && message !== previousMessageRef.current) {
                previousMessageRef.current = message;

                const id = uuidv4();

                // 非表示状態で追加
                setSnackbars(prev => [
                    ...prev,
                    { id, message, type, isOpen: false },
                ]);

                // 追加後、100ms後に表示（ふわっとアニメーションのため）
                setTimeout(() => {
                    setSnackbars(prev =>
                        prev.map(v => (v.id === id ? { ...v, isOpen: true } : v)),
                    );
                }, 100);

                // 自動削除のタイマー設定
                const timeout = type === 'success' ? 10000 : 60000;
                setTimeout(() => {
                    removeSnackbar(id);
                }, timeout);
            }
        },
        [snackbars],
    );

    const clearAllSnackbars = React.useCallback(() => {
        // すべて非表示にする
        setSnackbars(prev => prev.map(v => ({ ...v, isOpen: false })));

        // 100ms後に削除（アニメーション完了を待って削除）
        setTimeout(() => {
            setSnackbars([]);
        }, 100);
    }, [snackbars]);

    return {
        snackbars,
        addSnackbar,
        removeSnackbar,
        clearAllSnackbars,
    };
};
