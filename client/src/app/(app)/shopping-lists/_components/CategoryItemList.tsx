import React from 'react';
import {
    IGetShoppingCategory,
    IGetShoppingItem,
    IPostShoppingItem,
} from '@/types/api';
import ShoppingItem from './ShoppingItem';
import { Settings, Trash } from 'lucide-react';
import { colors } from '@/constants/colors';
import { useRouter } from 'next/navigation';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

interface Props {
    category: IGetShoppingCategory;
    items: IGetShoppingItem[];
    deleteItem: (id: string) => void;
    updateItem: (item: IPostShoppingItem) => void;
}

const CategoryItemList: React.FC<Props> = ({
    category,
    items,
    deleteItem,
    updateItem,
}) => {
    // console.log('CategoryItemList', items);
    const router = useRouter();
    const { setNodeRef } = useSortable({ id: category.id });

    return (
        <div className="flex flex-col gap-y-4">
            <div className="flex gap-x-4 items-center text-gray-main">
                {category.name}
                <div className="flex gap-x-2 items-center">
                    {!category.isDefault && (
                        <button
                            onClick={() => {
                                router.push('/shopping-lists/categories');
                            }}>
                            <Settings size={20} color={colors.primary.main} />
                        </button>
                    )}
                    {/* TODO: クリック処理 */}
                    <button onClick={() => {}}>
                        <Trash size={20} color={colors.primary.main} />
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
    item: IGetShoppingItem;
    categoryId: string;
    onDelete: (id: string) => void;
    onUpdate: (item: IPostShoppingItem) => void;
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
                    })
                }
            />
        </div>
    );
};
