import React from 'react';

/**
 * `value` の更新を `delay` ms まとめて反映するデバウンス。
 *
 * - 返り値は `[反映済みの値, flush]`。`flush` は待ちを捨てて即時に最新 `value` を反映する。
 */
export const useDebounce = <T>(value: T, delay: number): [T, () => void] => {
    const [debouncedValue, setDebouncedValue] = React.useState<T>(value);
    const timerRef = React.useRef<ReturnType<typeof setTimeout> | null>(null);
    /** `flush` が即時反映するときの参照用（常に最新の `value`） */
    const latestValueRef = React.useRef<T>(value);

    React.useLayoutEffect(() => {
        latestValueRef.current = value;
    }, [value]);

    /**
     * 待機中のタイマーを破棄し、`value` の最新値をそのまま `debouncedValue` に反映する。
     */
    const flush = React.useCallback(() => {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
        setDebouncedValue(latestValueRef.current);
    }, []);

    /**
     * `value` か `delay` が変わるたびに、`delay` ms 後に `debouncedValue` を合わせるタイマーを張り直す。
     */
    React.useEffect(() => {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }

        timerRef.current = setTimeout(() => {
            setDebouncedValue(value);
            timerRef.current = null;
        }, delay);

        return () => {
            if (timerRef.current) {
                clearTimeout(timerRef.current);
                timerRef.current = null;
            }
        };
    }, [value, delay]);

    return [debouncedValue, flush];
};
