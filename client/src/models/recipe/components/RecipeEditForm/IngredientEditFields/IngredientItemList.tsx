import React from 'react';
import { IRecipe } from '@/types/api/recipe';
import { Control } from 'react-hook-form';
import { DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';
import { IIngredientCategory, IIngredientItem } from '@/types/api/ingredient';
import Sortable from '@/components/dnd/Sortable';
import { TextButton } from '@/components/common';
import { CirclePlus } from 'lucide-react';
import { useIngredientStore } from '@/models/ingredient/hooks';
import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import IngredientEditDialogButton from './IngredientEditDialogButton';

import { useDroppable } from '@dnd-kit/core';

interface Props {
    control: Control<IRecipe>;
    category: IIngredientCategory;
    items: IIngredientItem[];
    offsetIndex: number;
    addEmptyItem: () => IIngredientItem[];
    updateItem: (index: number, item: IIngredientItem) => void;
    removeItem: (index: number) => void;
}

const IngredientItemList = ({
    category,
    items,
    addEmptyItem,
    updateItem,
    removeItem,
}: Props) => {
    const { openDialog } = useIngredientStore();
    const { setNodeRef: setDroppableNodeRef } = useDroppable({
        id: category.id,
    });

    /**
     * 食材の編集ダイアログを開く
     */
    const openEditDialog = React.useCallback(() => {
        let lastIndex = items.length ?? 0;
        let item = items[lastIndex];
        const emptyItems = items.filter(item => item.name === '');

        // 空の食材がある場合は、その食材のインデックスを取得
        if (emptyItems && emptyItems.length > 0) {
            lastIndex = items.indexOf(emptyItems[0]) ?? 0;
            item = items[lastIndex];
        }
        // 空の食材がない場合は、新しい入力項目を追加
        else {
            const newItems = addEmptyItem();
            lastIndex =
                newItems.filter(v => v.categoryId === category.id).length - 1;
            item = newItems[lastIndex];
        }

        // 食材の編集ダイアログを開く
        if (item) {
            openDialog(DIALOG_NAME.INGREDIENT_ADD_EDIT, {
                item: item,
                editMode: DIALOG_EDIT_MODE.CREATE,
                onAction: (value: IIngredientItem) => {
                    updateItem(lastIndex, value);
                },
            });
        }
    }, [items]);

    return (
        <>
            <div>{category.name}</div>
            <SortableContext
                items={items.map(field => field.id)}
                id={category.id}
                strategy={verticalListSortingStrategy}>
                <div
                    ref={setDroppableNodeRef}
                    className="flex flex-col gap-y-2">
                    {items.map((field, index) => (
                        <Sortable key={field.id} id={field.id}>
                            <IngredientEditDialogButton
                                key={field.id}
                                item={field}
                                isDisabled={
                                    index === 0 &&
                                    items.length === 1 &&
                                    field.name === ''
                                }
                                placeholder={`${category.name}を設定`}
                                onDelete={() => removeItem(index)}
                                onChange={(item: IIngredientItem) =>
                                    updateItem(index, item)
                                }
                            />
                        </Sortable>
                    ))}
                </div>
            </SortableContext>
            <TextButton
                type="button"
                onClick={openEditDialog}
                className="!border-none !bg-transparent hover:!bg-gray-light">
                <CirclePlus size={20} />
                追加
            </TextButton>
        </>
    );
};

export default IngredientItemList;
