'use client';

import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CirclePlus } from 'lucide-react';

import {
    DialogField,
    GrippableHorizontalItem,
    IngredientEditForm,
    Sortable,
    TextButton,
} from '@/components';
import { BUTTON_TYPE, EDIT_MODE } from '@/constants';
import { useDialog } from '@/hooks';
import { useIngredientStore } from '@/models/ingredient';
import { IIngredientCategory, IIngredientItem } from '@/types';
import { formatIngredient } from '@/utils';

interface Props {
    category: IIngredientCategory;
    items: IIngredientItem[];
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
    const { categories } = useIngredientStore();

    /**
     * 食材の編集ダイアログを開く
     */
    const openEditDialog = React.useCallback((item?: IIngredientItem) => {
        let index = items.length ?? 0;
        let editItem = items[index];

        // 新規追加の場合
        if (!item) {
            const emptyItems = items.filter(item => item.name === '');

            // 空の食材がある場合は、その食材のインデックスを取得
            if (emptyItems && emptyItems.length > 0) {
                index = items.indexOf(emptyItems[0]) ?? 0;
                editItem = items[index];
            }
            // 空の食材がない場合は、新しい入力項目を追加
            else {
                const newItems = addEmptyItem();
                index =
                    newItems.filter(v => v.categoryId === category.id).length - 1;
                editItem = newItems[index];
            }
        }
        // 既存アイテム編集の場合
        else {
            index = items.indexOf(item);
            editItem = item;
        }

        // 食材の編集ダイアログを開く
        if (editItem) {
            const editMode = editItem.name === '' ? EDIT_MODE.CREATE : EDIT_MODE.UPDATE;
            const category = categories.find(
                category => category.id === editItem?.categoryId,
            );
            const title =
                editMode === EDIT_MODE.CREATE
                    ? `${category?.name ?? '材料'}を追加`
                    : `${category?.name ?? '材料'}を編集`;
            const actionButtonText = editMode === EDIT_MODE.CREATE ? '追加' : '保存';

            openDialog({
                title,
                children: () => (
                    <IngredientEditForm
                        editingItem={editItem}
                        actionButtonText={actionButtonText}
                        onAction={(value: IIngredientItem) => {
                            updateItem(index, value);
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
                            <GrippableHorizontalItem
                                hasDeleteButton={true}
                                isDisabledDeleteButton={index === 0 &&
                                    items.length === 1 &&
                                    field.name === ''}
                                onDelete={() => removeItem(index)}>
                                <DialogField
                                    value={formatIngredient(field)}
                                    placeholder={`${category.name}を設定`}
                                    onOpenDialog={() => openEditDialog(field)}
                                />
                            </GrippableHorizontalItem>
                        </Sortable>
                    ))}
                </div>
            </SortableContext>
            <TextButton
                type={BUTTON_TYPE.BUTTON}
                onClick={() => openEditDialog()}
                className="!border-none !bg-transparent hover:!bg-gray-light">
                <CirclePlus size={20} />
                {category.name}を追加
            </TextButton>
        </>
    );
};

export default IngredientItemList;
