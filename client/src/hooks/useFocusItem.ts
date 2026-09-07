import React from 'react';

import { focusItemById } from '@/utils';

/**
 * アイテム追加後にフォーカスを当てるためのフック
 * @param watchItems 監視するアイテム配列（watchStepsなど）
 * @returns setFocusTargetId: フォーカス対象のIDを設定する関数
 */
export const useFocusItem = <T extends { id?: string }>(
    watchItems: T[] | undefined,
) => {
    const focusTargetIdRef = React.useRef<string | null>(null);

    /**
     * 追加したアイテムにフォーカスを当てる
     */
    React.useEffect(() => {
        if (focusTargetIdRef.current) {
            // DOMが更新されるまで待つ
            if (focusItemById(focusTargetIdRef.current)) {
                focusTargetIdRef.current = null;
            }
        }
    }, [watchItems]);

    /**
     * フォーカス対象のIDを設定する関数
     */
    const setFocusTargetId = React.useCallback((id: string | null) => {
        focusTargetIdRef.current = id;
    }, []);

    return {
        setFocusTargetId,
    };
};
