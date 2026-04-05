import React from 'react';

interface IDebounceOptions {
    /**
     * デバウンスをスキップする条件
     * true になったら、delay分の間デバウンスをスキップします
     */
    skipCondition?: boolean;
}

export const useDebounce = <T>(
    value: T,
    delay: number,
    options?: IDebounceOptions,
): [T, () => void] => {
    const [debouncedValue, setDebouncedValue] = React.useState<T>(value);
    const [isSkipping, setIsSkipping] = React.useState<boolean>(false);
    const timerRef = React.useRef<ReturnType<typeof setTimeout> | null>(null);
    const latestValueRef = React.useRef<T>(value);

    latestValueRef.current = value;

    /**
     * デバウンスをクリアして最新の値をセットする
     */
    const flush = React.useCallback(() => {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
        setDebouncedValue(latestValueRef.current);
    }, []);

    /**
     * skipConditionがtrueになったら、delay分の間デバウンスをスキップ
     */
    React.useEffect(() => {
        if (options?.skipCondition) {
            setIsSkipping(true);
            // delay分後にスキップを解除
            const timer = setTimeout(() => {
                setIsSkipping(false);
            }, delay);

            return () => {
                clearTimeout(timer);
            };
        }
    }, [options?.skipCondition, delay]);

    /**
     * デバウンスを設定する
     */
    React.useEffect(() => {
        // スキップ中はタイマーを設定せずに前の値を保持
        if (isSkipping) {
            return;
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
    }, [value, delay, isSkipping]);

    return [debouncedValue, flush];
};
