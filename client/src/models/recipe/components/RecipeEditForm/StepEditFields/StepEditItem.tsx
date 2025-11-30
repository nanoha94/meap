'use client';
import React from 'react';
import GrippableEditItem from '@/components/common/GrippableEditItem';
import { IRecipeStep } from '@/types/api';
import { Control, Controller } from 'react-hook-form';
import ImageEditField from '@/components/react-hook-form/ImageEditField';
import { FLEX_ALIGN_ITEMS, STYLE_SIZE } from '@/constants';
import { RecipeEditFormData } from '@/models/recipe/types';

interface Props {
    control: Control<RecipeEditFormData>;
    index: number;
    item: IRecipeStep;
    onDelete: () => void;
    isDisabledDeleteButton?: boolean;
    errorMessage: string;
}

const StepEditItem = ({
    control,
    index,
    item,
    onDelete,
    isDisabledDeleteButton,
    errorMessage,
}: Props) => {
    return (
        <div className="flex flex-col gap-y-1">
            <div className="flex gap-x-2">
                <div>{index + 1}.</div>
                <GrippableEditItem
                    hasDeleteButton={true}
                    isDisabledDeleteButton={isDisabledDeleteButton}
                    onDelete={onDelete}
                    className="flex-1"
                    alignItems={FLEX_ALIGN_ITEMS.START}>
                    <div className="py-2 px-4 flex gap-x-2 bg-white border rounded-lg has-[:focus-visible]:outline has-[:focus-visible]:outline-1 has-[:focus-visible]:outline-offset-0">
                        <Controller
                            control={control}
                            name={`steps.${index}.instruction`}
                            render={({ field: { onChange, value } }) => (
                                <textarea
                                    data-item-id={item.id}
                                    value={(value as string) ?? ''}
                                    rows={4}
                                    placeholder="説明文を入力"
                                    onChange={e => onChange(e)}
                                    className="outline-none"
                                />
                            )}
                        />
                        <ImageEditField
                            control={control}
                            name={`steps.${index}.image`}
                            size={STYLE_SIZE.SM}
                        />
                    </div>
                </GrippableEditItem>
            </div>
            {errorMessage && (
                <p className="text-alert-main text-sm">{errorMessage}</p>
            )}
        </div>
    );
};

export default StepEditItem;
