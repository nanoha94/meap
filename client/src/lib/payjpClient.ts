import { PAYJP_PUBLIC_KEY } from '@/constants/payjp';
import { PayjpClient } from '@/types';

let payjpClient: PayjpClient | null = null;
let payjpScriptLoaded = false;
const payjpScriptListeners = new Set<() => void>();

/**
 * Pay.jp ファクトリが利用可能かどうかを確認する
 * @returns Pay.jp ファクトリが利用可能かどうか
 */
const isPayjpFactoryAvailable = (): boolean =>
    typeof window !== 'undefined' && typeof window.Payjp === 'function';

/**
 * PAY.JP の公開鍵が設定されているかどうかを確認する
 */
export const isPayjpPublicKeyConfigured = (): boolean =>
    PAYJP_PUBLIC_KEY.length > 0;

/**
 * Pay.jp スクリプトが読み込まれたことを通知する
 */
export const notifyPayjpScriptLoaded = (): void => {
    if (payjpScriptLoaded) {
        return;
    }

    payjpScriptLoaded = true;
    payjpScriptListeners.forEach(listener => listener());
};


/**
 * Pay.jp スクリプトが読み込まれたことを購読する
 * @param listener スクリプトが読み込まれたときに呼び出されるコールバック関数
 * @returns 購読を解除する関数
 */
export const subscribePayjpScriptLoaded = (listener: () => void): (() => void) => {
    // スクリプトがすでに読み込まれている場合はすぐにコールバックを呼び出す
    if (payjpScriptLoaded && isPayjpFactoryAvailable()) {
        listener();
    } else {
        payjpScriptListeners.add(listener);
    }

    return () => {
        payjpScriptListeners.delete(listener);
    };
};

/**
 * Pay.jp クライアントを取得する
 */
export const getPayjpClient = (): PayjpClient | null => {
    if (!isPayjpFactoryAvailable()) {
        return null;
    }

    if (!isPayjpPublicKeyConfigured()) {
        return null;
    }

    if (!payjpClient) {
        const Payjp = window.Payjp;
        if (typeof Payjp !== 'function') {
            return null;
        }
        payjpClient = Payjp(PAYJP_PUBLIC_KEY, { locale: 'ja' });
    }

    return payjpClient;
};
