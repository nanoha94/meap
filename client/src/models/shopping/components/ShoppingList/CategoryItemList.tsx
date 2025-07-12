import React from 'react';
import { Trash, X } from 'lucide-react';
import { useSortable } from '@dnd-kit/sortable';
import { IShoppingCategory, IShoppingItem } from '@/types/api';
import Sortable from '@/components/dnd/Sortable';
import ShoppingItemCard from './ShoppingItemCard';
import { ActionMenu, AlertDialog } from '@/components/common';
import { useShoppingHandlers, useShoppingItems } from '../../hooks';

interface Props {
    category: IShoppingCategory;
    items: IShoppingItem[];
}

const CategoryItemList: React.FC<Props> = ({ category, items }) => {
    const { handleUpdateItems } = useShoppingHandlers();
    const { deleteShoppingItems } = useShoppingItems();
    const { setNodeRef } = useSortable({ id: category.id });
    const [isOpenDeleteDialog, setIsOpenDeleteDialog] = React.useState(false);

    return (
        <>
            <div className="flex flex-col gap-y-4">
                <div className="flex gap-x-4 items-center text-gray-main">
                    {category.name}
                    <ActionMenu
                        actionButtons={[
                            {
                                label: 'チェック解除',
                                icon: <X />,
                                onClick: () => {
                                    handleUpdateItems(
                                        items
                                            .filter(v => v.isChecked)
                                            .map(v => ({
                                                ...v,
                                                isChecked: false,
                                            })),
                                    );
                                },
                            },
                            {
                                label: 'チェック済みを削除',
                                icon: <Trash />,
                                onClick: () => {
                                    setIsOpenDeleteDialog(true);
                                },
                            },
                        ]}
                        placement="top-left"
                        className="w-5 h-5"
                    />
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
            <AlertDialog
                title={`${category.name}から買い物アイテムを削除する`}
                description={
                    <div className="flex flex-col gap-y-4">
                        <p className="text-center whitespace-pre-wrap">
                            {category.name}
                            に登録されているすべての買い物アイテムを削除しますか？
                        </p>
                        <span className="text-center">
                            ※固定化アイテムは削除されません
                        </span>
                    </div>
                }
                isOpen={isOpenDeleteDialog}
                onClose={() => setIsOpenDeleteDialog(false)}
                actionButton={{
                    text: '削除',
                    onClick: () => {
                        deleteShoppingItems(
                            items.filter(v => !v.isPinned).map(v => v.id),
                        );
                        setIsOpenDeleteDialog(false);
                    },
                }}
            />
        </>
    );
};
export default CategoryItemList;
