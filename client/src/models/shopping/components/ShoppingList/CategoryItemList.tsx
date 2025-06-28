import React from 'react';
import { EllipsisVertical } from 'lucide-react';
import { colors } from '@/constants/colors';
import { useSortable } from '@dnd-kit/sortable';
import { IShoppingCategory, IShoppingItem } from '@/types/api';
import Sortable from '@/components/dnd/Sortable';
import ShoppingItemCard from './ShoppingItemCard';

interface Props {
    category: IShoppingCategory;
    items: IShoppingItem[];
}

const CategoryItemList: React.FC<Props> = ({ category, items }) => {
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
                                <Sortable key={idx} id={item.id}>
                                    <ShoppingItemCard item={item} />
                                </Sortable>
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
