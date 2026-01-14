import { useGlobalStore } from '@/stores';
import React from 'react';

/**
 * ローディングアニメーションの表示条件を制御するフック
 * @param condition 表示条件（デフォルト: true）
 * @returns void
 */
export const useLoadingAnimation = (condition: boolean = true) => {
    const { setLoadingCondition } = useGlobalStore();

    React.useEffect(() => {
        setLoadingCondition(condition);

        // クリーンアップ: コンポーネントがアンマウントされたら条件をリセット
        return () => {
            setLoadingCondition(true);
        };
    }, [condition, setLoadingCondition]);
};
