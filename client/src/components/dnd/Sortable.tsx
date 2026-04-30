'use client';

import React, { createContext, useMemo } from 'react';

import { UniqueIdentifier } from '@dnd-kit/core';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

export type SortableHandleContextValue = Pick<
    ReturnType<typeof useSortable>,
    'attributes' | 'listeners' | 'setActivatorNodeRef'
>;

export const SortableHandleContext =
    createContext<SortableHandleContextValue | null>(null);

interface Props {
    id: UniqueIdentifier;
    children: React.ReactNode;
}

const Sortable: React.FC<Props> = ({ id, children }) => {
    const {
        attributes,
        listeners,
        setNodeRef,
        setActivatorNodeRef,
        transform,
        transition,
    } = useSortable({ id: id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    const handleCtx = useMemo<SortableHandleContextValue>(
        () => ({ attributes, listeners, setActivatorNodeRef }),
        [attributes, listeners, setActivatorNodeRef],
    );

    return (
        <SortableHandleContext.Provider value={handleCtx}>
            <div ref={setNodeRef} style={style}>
                {children}
            </div>
        </SortableHandleContext.Provider>
    );
};

export default Sortable;
