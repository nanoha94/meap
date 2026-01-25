import React from 'react';
import { IIngredientCategory, IIngredientItem } from '@/types/api';
import { Control } from 'react-hook-form';
import { BUTTON_TYPE } from '@/constants';
import Sortable from '@/components/dnd/Sortable';
import { TextButton } from '@/components/common';
import { CirclePlus } from 'lucide-react';
import { useDialog } from '@/hooks/useDialog';
import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import IngredientEditDialogButton from './IngredientEditDialogButton';
import { useDroppable } from '@dnd-kit/core';
import { RecipeEditFormData } from '@/models/recipe/types';
import { IngredientEditForm } from '@/components/dialog-contents';

interface Props {
    control: Control<RecipeEditFormData>;
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
    const { openDialog, closeDialog } = useDialog();
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
            const title = `${category.name}を追加`;
            const actionButtonText = '追加';

            openDialog({
                title,
                children: () => (
                    <IngredientEditForm
                        editingItem={item}
                        actionButtonText={actionButtonText}
                        onAction={(value: IIngredientItem) => {
                            updateItem(lastIndex, value);
                            closeDialog();
                        }}
                    />
                ),
            });
        }
    }, [items, category, addEmptyItem, updateItem, openDialog, closeDialog]);

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
                type={BUTTON_TYPE.BUTTON}
                onClick={openEditDialog}
                className="!border-none !bg-transparent hover:!bg-gray-light">
                <CirclePlus size={20} />
                {category.name}を追加
            </TextButton>
        </>
    );
};

export default IngredientItemList;
