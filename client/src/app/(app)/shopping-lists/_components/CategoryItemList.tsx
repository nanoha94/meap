import React from 'react';
import {
    IGetShoppingCategory,
    IGetShoppingItem,
    IPostShoppingItem,
} from '@/types/api';
import { EmptyButton } from '../../_components';
import ShoppingItem from './ShoppingItem';
import { Settings, Trash } from 'lucide-react';
import { colors } from '@/constants/colors';
import { useRouter } from 'next/navigation';

interface Props {
    category: IGetShoppingCategory;
    items: IGetShoppingItem[];
    addEmptyItem: (categoryId: string) => void;
    deleteItem: (id: string) => void;
    updateItem: (item: IPostShoppingItem) => void;
}

const CategoryItemList: React.FC<Props> = ({
    category,
    items,
    addEmptyItem,
    deleteItem,
    updateItem,
}) => {
    const router = useRouter();

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
                {items.map(item => (
                    <ShoppingItem
                        key={item.id}
                        item={item}
                        onDelete={() => deleteItem(item.id)}
                        onUpdate={(name, isPinned, isChecked) =>
                            updateItem({
                                id: item.id,
                                name,
                                isPinned,
                                isChecked,
                                categoryId: category.id,
                                order: item.order,
                            })
                        }
                    />
                ))}
                <EmptyButton onClick={() => addEmptyItem(category.id)}>
                    テキストで追加
                </EmptyButton>
                {/* TODO: クリック処理 */}
                <EmptyButton onClick={() => {}}>献立から追加</EmptyButton>
            </div>
        </div>
    );
};

export default CategoryItemList;
