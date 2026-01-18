'use client';
import React from 'react';
import { GrippableVerticalItem } from '@/components/common';
import { IRecipeStep } from '@/types/api';
import { Control, Controller } from 'react-hook-form';
import ImageEditField from '@/components/react-hook-form/ImageEditField';
import { STYLE_SIZE } from '@/constants';
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
    const textareaRef = React.useRef<HTMLTextAreaElement>(null);

    const adjustTextareaHeight = (textarea: HTMLTextAreaElement) => {
        textarea.style.height = 'auto';
        textarea.style.height = `${textarea.scrollHeight}px`;
    };

    /**
     * テキストエリアの高さを調整（初回のみ）
     */
    React.useEffect(() => {
        if (textareaRef.current) {
            adjustTextareaHeight(textareaRef.current);
        }
    }, []);

    return (
        <div className="flex flex-col gap-y-1">
            <GrippableVerticalItem
                order={index + 1}
                hasDeleteButton={true}
                isDisabledDeleteButton={isDisabledDeleteButton}
                onDelete={onDelete}
                className="flex-1">
                <div className="flex flex-col gap-y-2">
                    <ImageEditField
                        control={control}
                        name={`steps.${index}.image`}
                        size={STYLE_SIZE.SM}
                    />

                    <Controller
                        control={control}
                        name={`steps.${index}.instruction`}
                        render={({ field: { onChange, value } }) => (
                            <textarea
                                ref={textareaRef}
                                data-item-id={item.id}
                                value={(value as string) ?? ''}
                                rows={4}
                                placeholder="説明文を入力"
                                onChange={e => {
                                    onChange(e);
                                    adjustTextareaHeight(e.target);
                                }}
                                className="flex-1 py-2 px-4  border rounded-lg resize-none overflow-hidden"
                            />
                        )}
                    />
                </div>
            </GrippableVerticalItem>
            {errorMessage && (
                <p className="text-alert-main text-sm">{errorMessage}</p>
            )}
        </div>
    );
};

export default StepEditItem;
