'use client';
import React from 'react';
import { Control, Controller } from 'react-hook-form';

import { GrippableVerticalItem, ImageEditField } from '@/components';
import { RecipeEditFormData } from '@/models/recipe/types';
import { IRecipeStep } from '@/types';

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
                        styleConfig={{
                            iconSmSize: 20,
                            iconMdSize: 32,
                            imageRounded: 'rounded-lg',
                            containerClass: 'aspect-[4/3] bg-gray-light',
                            labelClass: 'gap-y-1 text-gray-main',
                            overlayIconContainerClass: 'gap-x-2.5',
                            overlayIconClass: 'p-1.5',
                        }}
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
                                placeholder="手順を入力"
                                onChange={e => {
                                    onChange(e);
                                    adjustTextareaHeight(e.target);
                                }}
                                className="flex-1 py-2 px-4  border rounded-lg resize-none overflow-hidden outline-black"
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
