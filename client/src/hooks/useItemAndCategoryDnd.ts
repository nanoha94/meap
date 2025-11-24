import React from 'react';
import {
    DragStartEvent,
    DragOverEvent,
    DragEndEvent,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    DRAG_ACTIVATION_DISTANCE,
    TOUCH_ACTIVATION_DELAY,
    TOUCH_ACTIVATION_TOLERANCE,
} from '@/constants';
import {
    getDragActiveItem,
    getDragOverItem,
    getCategoryByItemId,
    getItemById,
} from '@/utils/dnd';

interface IItemAndCategoryDndOptions<
    TItem extends { id: string; categoryId: string },
    TCategory extends { id: string },
> {
    currentItems: TItem[];
    categories: TCategory[];
    onDragOver?: (
        activeId: string,
        activeItem: TItem,
        overCategoryId: string,
    ) => void;
    onDragEnd?: (
        activeIndex: number | undefined,
        overIndex: number | undefined,
    ) => void;
}

export const useItemAndCategoryDnd = <
    TItem extends { id: string; categoryId: string },
    TCategory extends { id: string },
>({
    currentItems,
    categories,
    onDragOver: customOnDragOver,
    onDragEnd: customOnDragEnd,
}: IItemAndCategoryDndOptions<TItem, TCategory>) => {
    const [activeId, setActiveId] = React.useState<string | null>(null);

    /**
     * ドラッグ中アイテム
     */
    const activeItem = React.useMemo(
        () => getItemById(currentItems, activeId as string),
        [activeId, currentItems],
    );

    /**
     * ドラッグ中アイテムが属するカテゴリー
     */
    const activeCategory = React.useMemo(
        () => getCategoryByItemId(currentItems, activeId as string, categories),
        [activeId, currentItems, categories],
    );

    // ドラッグ&ドロップ設定
    const sensors = useSensors(
        useSensor(MouseSensor, {
            // マウス操作の誤クリックを防ぐため、一定距離移動するまでドラッグを開始しない
            activationConstraint: {
                distance: DRAG_ACTIVATION_DISTANCE,
            },
        }),
        useSensor(TouchSensor, {
            // タッチ操作の誤操作を防ぐため、250msの遅延と5pxの許容範囲を設定
            activationConstraint: {
                delay: TOUCH_ACTIVATION_DELAY,
                tolerance: TOUCH_ACTIVATION_TOLERANCE,
            },
        }),
    );

    /**
     * ドラッグ開始
     */
    const handleDragStart = React.useCallback((event: DragStartEvent) => {
        setActiveId(event.active.id as string);
    }, []);

    /**
     * ドラッグオーバー
     */
    const handleDragOver = React.useCallback(
        (event: DragOverEvent) => {
            const { active, over } = event;
            if (!over || active.id === over.id) return;

            // ドラッグ中のアイテムのインデックスを取得
            const { activeIndex, activeItem } = getDragActiveItem(
                currentItems,
                active.id as string,
            );

            // ドロップ先のアイテムのインデックスとカテゴリーIDを取得
            const { overIndex, overCategoryId } = getDragOverItem(
                currentItems,
                categories,
                over.id as string,
            );

            // ドラッグ中のアイテムのインデックスまたはドロップ先のアイテムのインデックスが見つからない場合、処理を終了
            if (activeIndex === -1 || !activeItem || overIndex === -1) return;

            // カスタムハンドラーが指定されている場合は、それを呼び出す
            if (customOnDragOver) {
                customOnDragOver(
                    active.id as string,
                    activeItem,
                    overCategoryId,
                );
            }
        },
        [currentItems, categories, customOnDragOver],
    );

    /**
     * ドラッグ終了
     */
    const handleDragEnd = React.useCallback(
        (event: DragEndEvent) => {
            const { active, over } = event;

            if (active && over && active.id !== over.id) {
                // ドラッグ中のアイテムのインデックスを取得
                const activeIndex = currentItems.findIndex(
                    item => item.id === active.id,
                );

                // ドロップ先のアイテムのインデックスを取得
                const { overIndex } = getDragOverItem(
                    currentItems,
                    categories,
                    over.id as string,
                );

                if (activeIndex !== -1 && overIndex !== -1) {
                    // カスタムハンドラーが指定されている場合は、それを呼び出す
                    if (customOnDragEnd) {
                        customOnDragEnd(activeIndex, overIndex);
                    }
                }
            } else {
                // カスタムハンドラーが指定されている場合は、それを呼び出す（キャンセル時）
                if (customOnDragEnd) {
                    customOnDragEnd(undefined, undefined);
                }
            }
            setActiveId(null);
        },
        [currentItems, categories, customOnDragEnd],
    );

    return {
        sensors,
        activeId,
        activeItem,
        activeCategory,
        handleDragStart,
        handleDragOver,
        handleDragEnd,
    };
};
