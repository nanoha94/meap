'use client';
import React from 'react';
import { colors } from '@/constants/colors';
import { Pencil } from 'lucide-react';
import { IIngredientItem } from '@/types/api/ingredient';
import { DIALOG_EDIT_MODE, DIALOG_NAME } from '@/constants';
import { useIngredientStore } from '@/models/ingredient/hooks/ingredientStores';
import GrippableEditItem from '@/components/common/GrippableEditItem';

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
    const { openDialog } = useIngredientStore();

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
        openDialog(DIALOG_NAME.INGREDIENT_ADD_EDIT, {
            item,
            editMode:
                item.name === ''
                    ? DIALOG_EDIT_MODE.CREATE
                    : DIALOG_EDIT_MODE.UPDATE,
            onAction: (value: IIngredientItem) => onChange?.(value),
        });
    };

    return (
        <>
            <GrippableEditItem
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
            </GrippableEditItem>
        </>
    );
}

export default IngredientEditDialogButton;
