import React from 'react';

import ShoppingItem from './ShoppingItem';
import { EllipsisVertical } from 'lucide-react';
import { colors } from '@/constants/colors';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { IShoppingCategory, IShoppingItem } from '@/types/api';

interface Props {
    category: IShoppingCategory;
    items: IShoppingItem[];
    deleteItem: (id: string) => void;
    updateItem: (item: IShoppingItem) => void;
}

const CategoryItemList: React.FC<Props> = ({
    category,
    items,
    deleteItem,
    updateItem,
}) => {
    const { setNodeRef } = useSortable({ id: category.id });

    return (
        <div className="flex flex-col gap-y-4">
            <div className="flex gap-x-4 items-center text-gray-main">
                {category.name}
                <div className="flex gap-x-2 items-center">
                    {/* TODO: メニュー表示 */}
                    <button onClick={() => {}}>
                        <EllipsisVertical size={20} color={colors.gray.main} />
                    </button>
                </div>
            </div>
            <div className="flex flex-col gap-y-2">
                {items.length > 0 ? (
                    items.map(
                        (item, idx) =>
                            !!item && (
                                <SortableItem
                                    key={idx}
                                    item={item}
                                    categoryId={category.id}
                                    onDelete={deleteItem}
                                    onUpdate={updateItem}
                                />
                            ),
                    )
                ) : (
                    <div className="text-base">
                        登録されているアイテムはありません
                    </div>
                )}
                <div ref={setNodeRef} />
            </div>
        </div>
    );
};
export default CategoryItemList;

interface SortableItemProps {
    item: IShoppingItem;
    categoryId: string;
    onDelete: (id: string) => void;
    onUpdate: (item: IShoppingItem) => void;
}
const SortableItem: React.FC<SortableItemProps> = ({
    item,
    categoryId,
    onDelete,
    onUpdate,
}) => {
    // console.log('SortableItem', item);
    const { setNodeRef, attributes, listeners, transform, transition } =
        useSortable({
            id: item.id,
        });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
            className="relative">
            <ShoppingItem
                item={item}
                onDelete={() => onDelete(item.id)}
                onUpdate={(name, isPinned, isChecked) =>
                    onUpdate({
                        id: item.id,
                        name,
                        isPinned,
                        isChecked,
                        categoryId,
                        order: item.order,
                        tags: item.tags,
                    })
                }
            />
        </div>
    );
};
