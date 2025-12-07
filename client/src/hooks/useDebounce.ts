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
): T => {
    const [debouncedValue, setDebouncedValue] = React.useState<T>(value);
    const [isSkipping, setIsSkipping] = React.useState<boolean>(false);

    // skipConditionがtrueになったら、delay分の間デバウンスをスキップ
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

    React.useEffect(() => {
        // スキップ中はタイマーを設定せずに前の値を保持
        if (isSkipping) {
            return;
        }

        const timer = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(timer);
        };
    }, [value, delay, isSkipping]);

    return debouncedValue;
};
