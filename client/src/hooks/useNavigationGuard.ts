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

    // TODO: ブラウザ戻る時のブロック処理

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
