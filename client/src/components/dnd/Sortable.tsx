'use client';
import { UniqueIdentifier } from '@dnd-kit/core';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

interface Props {
    id: UniqueIdentifier;
    children: React.ReactNode;
}

const Sortable: React.FC<Props> = ({ id, children }) => {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id: id });
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        touchAction: 'none',
    };

    return (
        <div ref={setNodeRef} style={style} {...attributes} {...listeners}>
            {children}
        </div>
    );
};

export default Sortable;
