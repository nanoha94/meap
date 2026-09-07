'use client';

import React from 'react';
import { usePathname, useRouter } from 'next/navigation';

import { useAlertDialog } from '@/hooks/useAlertDialog';
import { ALERT_DIALOG_CONFIGS } from '@/constants';

/**
 * 未保存の編集がある場合に、ページ離脱をブロックして確認するナビゲーションガード
 *
 * @param shouldBlock ブロックするかどうか（例: !isDisabledSendButton）
 */
export const useNavigationGuard = (shouldBlock: boolean) => {
    const pathname = usePathname();
    const router = useRouter();
    const { openAlertDialog } = useAlertDialog();

    // openAlertDialog は表示中ダイアログに依存して参照が変わる。popstate 用 effect の依存を [shouldBlock] のみに
    // 保つため ref に逃がし、クリーンアップで誤って history.back しないようにする。
    const openAlertDialogRef = React.useRef(openAlertDialog);
    React.useEffect(() => {
        openAlertDialogRef.current = openAlertDialog;
    }, [openAlertDialog]);

    /**
     * タブ閉じ・リロード時のブロック処理
     */
    React.useEffect(() => {
        if (!shouldBlock) return;

        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            e.preventDefault();
        };

        window.addEventListener('beforeunload', handleBeforeUnload);
        return () => window.removeEventListener('beforeunload', handleBeforeUnload);
    }, [shouldBlock]);

    /**
     * ブラウザ戻る/進む（popstate）時のブロック処理
     *
     * 同一URLのダミー履歴を1つ積み、戻る/進むで popstate が飛んだら確認ダイアログを出す。
     * 破棄時は go(-2) でダミーとその手前の実エントリをまとめて戻す。
     */
    React.useEffect(() => {
        if (!shouldBlock) return;
        if (typeof window === 'undefined') return;

        // 自分が積んだダミーを history.state で識別し、アンマウント時に back でだけ取り除く
        const guardKey = `nav-guard-${Date.now()}-${Math.random()}`;
        window.history.pushState(
            { __navGuardKey: guardKey },
            '',
            window.location.href,
        );

        const handlePopState = () => {
            // 履歴スタック上は1つ戻った状態なので、同じURLのダミーを積み直して「編集画面に留まる」
            window.history.pushState(
                { __navGuardKey: guardKey },
                '',
                window.location.href,
            );
            openAlertDialogRef.current(ALERT_DIALOG_CONFIGS.unsavedChanges(), () => {
                window.removeEventListener('popstate', handlePopState);
                // ダミー1つ + ユーザーが戻ろうとした先1つ分をまとめて戻す
                window.history.go(-2);
            });
        };

        window.addEventListener('popstate', handlePopState);
        return () => {
            window.removeEventListener('popstate', handlePopState);
            const state = window.history.state as { __navGuardKey?: string } | null;
            // まだダミー上にいるときだけ back（router 遷移後は state が別物のため誤 back しない）
            if (state?.__navGuardKey === guardKey) {
                window.history.back();
            }
        };
    }, [shouldBlock]);

    /**
     * ナビゲーション遷移時のブロック処理
     */
    React.useEffect(() => {
        if (!shouldBlock) return;

        const handleClick = (e: MouseEvent) => {
            const target = e.target as HTMLElement;
            const anchor = target.closest('a');
            if (!anchor || !anchor.href) return;

            try {
                const url = new URL(anchor.href);
                const origin = typeof window !== 'undefined' ? window.location.origin : '';
                if (url.origin !== origin) return; // 外部リンクはそのまま
            } catch {
                return;
            }

            const href = anchor.getAttribute('href');
            if (!href || href.startsWith('#') || anchor.target === '_blank' || anchor.hasAttribute('download')) {
                return;
            }

            const currentPath = pathname ?? window.location.pathname;
            const linkPath = href.startsWith('/') ? href : new URL(anchor.href, origin).pathname;
            if (linkPath === currentPath) return; // 同一ページはそのまま

            e.preventDefault();
            e.stopPropagation();

            openAlertDialog(ALERT_DIALOG_CONFIGS.unsavedChanges(), () => {
                router.push(href);
            });
        };

        document.addEventListener('click', handleClick, true);
        return () => document.removeEventListener('click', handleClick, true);
    }, [shouldBlock, pathname, router, openAlertDialog]);
};
