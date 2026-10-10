import {
    SQUARE_APPLICATION_ID,
    SQUARE_LOCATION_ID,
} from '@/constants/square';
import { Payments } from '@/types/vendor/square';

let squarePayments: Payments | null = null;
let squareScriptLoaded = false;
const squareScriptListeners = new Set<() => void>();

/**
 * Square.js の SDK が利用可能か確認する
 */
const isSquareAvailable = (): boolean =>
    typeof window !== 'undefined' &&
    window.Square != null &&
    typeof window.Square.payments === 'function';

/**
 * Square.js の資格情報が設定されているか確認する
 */
export const isSquareCredentialsConfigured = (): boolean =>
    SQUARE_APPLICATION_ID.length > 0 && SQUARE_LOCATION_ID.length > 0;

/**
 * Square.js が読み込まれたことを通知する
 */
export const notifySquareScriptLoaded = (): void => {
    if (squareScriptLoaded) {
        return;
    }

    squareScriptLoaded = true;
    squareScriptListeners.forEach(listener => listener());
    squareScriptListeners.clear();
};

/**
 * Square.js が利用可能になったことを購読する
 * @param listener 購読する関数
 * @returns 購読を解除する関数
 */
export const subscribeSquareScriptLoaded = (listener: () => void): (() => void) => {
    // すでに Square.js が読み込まれていたら直ちに通知
    if (squareScriptLoaded) {
        listener();
        return () => { };
    }

    squareScriptListeners.add(listener);

    return () => {
        squareScriptListeners.delete(listener);
    };
};

/**
 * Square.js の Payments インスタンスを取得する
 * @returns Square.js の Payments インスタンス
 */
export const getSquarePayments = async (): Promise<Payments | null> => {
    if (!isSquareAvailable() || !isSquareCredentialsConfigured()) {
        return null;
    }

    if (!squarePayments) {
        squarePayments = await window.Square!.payments(
            SQUARE_APPLICATION_ID,
            SQUARE_LOCATION_ID,
        );
    }

    return squarePayments;
};
