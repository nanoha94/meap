'use client';

import React, { useContext } from 'react';

import { SortableHandleContext } from './Sortable';

interface Props {
    children: React.ReactNode;
    className?: string;
}

const SortableHandle: React.FC<Props> = ({ children, className }) => {
    const ctx = useContext(SortableHandleContext);

    if (!ctx) {
        return <>{children}</>;
    }

    const { attributes, listeners, setActivatorNodeRef } = ctx;

    return (
        <div
            ref={setActivatorNodeRef}
            className={`cursor-move ${className ?? ''}`}
            {...attributes}
            {...listeners}
            style={{ touchAction: 'none' }}
        >
            {children}
        </div>
    );
};

export default SortableHandle;
