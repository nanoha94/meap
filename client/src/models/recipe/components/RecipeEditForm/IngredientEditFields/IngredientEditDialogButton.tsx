'use client';
import React from 'react';
import { colors } from '@/constants/colors';
import { Pencil } from 'lucide-react';
import { IIngredientItem } from '@/types/api';
import { EDIT_MODE } from '@/constants';
import { useDialog } from '@/hooks/useDialog';
import { useIngredientStore } from '@/models/ingredient/hooks';
import { GrippableHorizontalItem } from '@/components/common';
import { IngredientEditForm } from '@/components/dialog-contents';

interface Props {
    item: IIngredientItem;
    isDisabled?: boolean;
    placeholder: string;
    onDelete: () => void;
    onChange?: (item: IIngredientItem) => void;
}

function IngredientEditDialogButton({
    item,
    isDisabled = false,
    placeholder,
    onDelete,
    onChange,
}: Props) {
    const { openDialog, closeDialog } = useDialog();
    const { categories } = useIngredientStore();

    /**
     * 材料のフォーマット
     * @param value 材料のデータ
     * @returns 材料の値
     */
    const formatValue = () => {
        if (!item) {
            return '';
        }

        let result: string = item.name;
        if (result && item.unit) {
            result += ` / ${item.quantity || ''}${item.unit.name}`;
        }
        return result;
    };

    const handleOpenDialog = () => {
        const editMode = item.name === '' ? EDIT_MODE.CREATE : EDIT_MODE.UPDATE;
        const category = categories.find(
            category => category.id === item?.categoryId,
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
                    editingItem={item}
                    actionButtonText={actionButtonText}
                    onAction={(value: IIngredientItem) => {
                        onChange?.(value);
                        closeDialog();
                    }}
                />
            ),
        });
    };

    return (
        <>
            <GrippableHorizontalItem
                hasDeleteButton={true}
                isDisabledDeleteButton={isDisabled}
                onDelete={onDelete}>
                <div
                    className="relative w-full cursor-pointer rounded-lg transition-colors group hover:bg-gray-light"
                    onClick={() => {
                        handleOpenDialog();
                    }}
                    role="button">
                    <input
                        value={formatValue()}
                        type="text"
                        readOnly
                        placeholder={placeholder}
                        className="py-2 px-4 w-full flex-1 outline-none bg-white rounded-lg border border-gray-main pointer-events-none"
                    />
                    <button
                        type="button"
                        className="absolute p-1 right-2 top-1/2 -translate-y-1/2 cursor-pointer rounded-full transition-colors group-hover:bg-gray-light"
                        onClick={() => {
                            handleOpenDialog();
                        }}
                        aria-label="編集">
                        <Pencil
                            color={colors.gray.main}
                            size={24}
                            strokeWidth={1.5}
                        />
                    </button>
                </div>
            </GrippableHorizontalItem>
        </>
    );
}

export default IngredientEditDialogButton;
