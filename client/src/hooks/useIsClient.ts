import { useSyncExternalStore } from 'react';

/**
 * SSR とクライアント初回描画の不一致を避けるため、マウント後のみ true になる。
 */
export const useIsClient = () =>
    useSyncExternalStore(() => () => { }, () => true, () => false);
