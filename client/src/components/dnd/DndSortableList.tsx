'use client';
import { DRAG_ACTIVATION_DISTANCE } from '@/constants';
import {
    DndContext,
    DragEndEvent,
    KeyboardSensor,
    MouseSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { SortableContext } from '@dnd-kit/sortable';
import Sortable from '../dnd/Sortable';
import React from 'react';

interface Props<T extends { id: string }> {
    items: T[];
    prefix: string;
    onDragEnd: (oldIndex: number, newIndex: number) => void;
    renderItem: (item: T, index: number) => React.ReactNode;
}

const DndSortableList = <T extends { id: string }>({
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
            activationConstraint: {
                distance: DRAG_ACTIVATION_DISTANCE,
            },
        }),
        useSensor(KeyboardSensor),
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
                <SortableContext items={itemIds}>
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
