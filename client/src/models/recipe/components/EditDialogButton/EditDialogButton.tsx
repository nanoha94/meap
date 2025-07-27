'use client';

import { colors } from '@/constants/colors';
import { GripVertical, Pencil, Trash } from 'lucide-react';
import { Control, Controller, FieldValues, Path } from 'react-hook-form';
import React from 'react';
import { useRecipeStore } from '../../hooks/recipeStores';
import {
    RECIPE_SETTING_DIALOG_EDIT_MODE,
    RECIPE_SETTING_DIALOG_NAME,
} from '../../constants';
import { IIngredient, ISeasoning } from '@/types/api/recipe';

interface Props<T extends FieldValues> {
    dialogName: (typeof RECIPE_SETTING_DIALOG_NAME)[keyof typeof RECIPE_SETTING_DIALOG_NAME];
    editMode: (typeof RECIPE_SETTING_DIALOG_EDIT_MODE)[keyof typeof RECIPE_SETTING_DIALOG_EDIT_MODE];
    isDisabled?: boolean;
    name: Path<T>;
    placeholder: string;
    control: Control<T>;
    onDelete: () => void;
    formatValue?: (value: IIngredient | ISeasoning) => string;
}

function EditDialogButton<T extends FieldValues>({
    dialogName,
    editMode,
    isDisabled = false,
    name,
    placeholder,
    control,
    onDelete,
    formatValue,
}: Props<T>) {
    const { openDialog } = useRecipeStore();

    const handleOpenDialog = (
        value: IIngredient,
        onChange: (value: IIngredient) => void,
    ) => {
        openDialog(dialogName, {
            item: value,
            editMode,
            onAction: (value: IIngredient) => onChange(value),
        });
    };

    return (
        <>
            <div className="flex items-center gap-x-2">
                <GripVertical color={colors.gray.main} />
                <Controller
                    control={control}
                    name={name}
                    render={({ field }) => (
                        <div
                            className="relative w-full cursor-pointer rounded-lg transition-colors group hover:bg-gray-light"
                            onClick={() => {
                                handleOpenDialog(field.value, field.onChange);
                            }}
                            role="button"
                            tabIndex={0}>
                            <input
                                {...field}
                                value={
                                    formatValue
                                        ? formatValue(field.value)
                                        : field.value
                                }
                                type="text"
                                readOnly
                                placeholder={placeholder}
                                className="py-2 px-4 w-full flex-1 outline-none bg-white rounded-lg border border-gray-main pointer-events-none"
                            />
                            <button
                                type="button"
                                className="absolute p-1 right-2 top-1/2 -translate-y-1/2 cursor-pointer rounded-full transition-colors group-hover:bg-gray-light"
                                onClick={() => {
                                    handleOpenDialog(
                                        field.value,
                                        field.onChange,
                                    );
                                }}
                                tabIndex={0}
                                aria-label="編集">
                                <Pencil
                                    color={colors.gray.main}
                                    size={24}
                                    strokeWidth={1.5}
                                />
                            </button>
                        </div>
                    )}
                />
                <button
                    type="button"
                    onClick={onDelete}
                    disabled={isDisabled}
                    className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0 disabled:cursor-default">
                    <Trash color={colors.primary.main} size={28} />
                </button>
            </div>
        </>
    );
}

export default EditDialogButton;
