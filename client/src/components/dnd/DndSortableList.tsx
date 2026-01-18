'use client';
import {
    DND_SORTABLE_LIST_TYPE,
    DRAG_ACTIVATION_DISTANCE,
    TOUCH_ACTIVATION_DELAY,
    TOUCH_ACTIVATION_TOLERANCE,
} from '@/constants';
import {
    DndContext,
    DragEndEvent,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    rectSortingStrategy,
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import Sortable from '../dnd/Sortable';
import React from 'react';

interface Props<T extends { id: string }> {
    type: DND_SORTABLE_LIST_TYPE;
    items: T[];
    prefix: string;
    onDragEnd: (oldIndex: number, newIndex: number) => void;
    renderItem: (item: T, index: number) => React.ReactNode;
}

const DndSortableList = <T extends { id: string }>({
    type,
    items,
    prefix,
    onDragEnd,
    renderItem,
}: Props<T>) => {
    const id = React.useId();

    const itemIds: string[] = React.useMemo(
        () => items.map((_, index) => `${prefix}${index}`),
        [items, prefix],
    );

    /**
     * センサーの設定
     */
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
     * ドラッグ終了時の処理
     */
    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (over && active.id !== over.id) {
            const oldIndex = itemIds.indexOf(active.id.toString());
            const newIndex = itemIds.indexOf(over.id.toString());
            onDragEnd(oldIndex, newIndex);
        }
    };

    return (
        <DndContext id={id} onDragEnd={handleDragEnd} sensors={sensors}>
            {!!items && items.length > 0 && (
                <SortableContext
                    items={itemIds}
                    strategy={
                        type === DND_SORTABLE_LIST_TYPE.GRID
                            ? rectSortingStrategy
                            : verticalListSortingStrategy
                    }>
                    {items.map((item, index) => (
                        <Sortable key={item.id} id={itemIds[index]}>
                            {renderItem(item, index)}
                        </Sortable>
                    ))}
                </SortableContext>
            )}
        </DndContext>
    );
};

export default DndSortableList;
