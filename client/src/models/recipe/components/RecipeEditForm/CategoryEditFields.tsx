'use client';
import { TextButton } from '@/components/common';
import { Control, Controller } from 'react-hook-form';
import { IPostRecipeRequest, IRecipeCategory } from '@/types/api/recipe';
import { Check, ChevronRight } from 'lucide-react';
import React from 'react';
import { useRecipeStore } from '../../hooks/recipeStores';
import { colors } from '@/constants';
import { RECIPE_SETTING_DIALOG_NAME } from '../../constants';

interface Props {
    control: Control<IPostRecipeRequest>;
}

const CategoryEditFields = ({ control }: Props) => {
    const { categories, openDialog } = useRecipeStore();

    /**
     * カテゴリーのラベルの背景色を返す
     * @param isChecked チェックされているかどうか
     * @returns カテゴリーのラベルの背景色
     */
    const wrapperColorClass = (isChecked: boolean) => {
        return isChecked
            ? 'border-primary-main bg-primary-light'
            : 'border-gray-main bg-gray-light';
    };

    /**
     * カテゴリーのチェックボックスの背景色を返す
     * @param isChecked チェックされているかどうか
     * @returns カテゴリーのチェックボックスの背景色
     */
    const boxColorClass = (isChecked: boolean) => {
        return isChecked
            ? 'bg-primary-main border-[transparent]'
            : 'bg-white border-gray-main';
    };

    return (
        <>
            <div className="flex flex-col gap-y-3">
                <div className="flex flex-col gap-y-2">
                    <div className="text-base">カテゴリー</div>
                    <Controller
                        control={control}
                        name="categories"
                        render={({ field: { onChange, value } }) => {
                            const handleChange = (
                                checkedValue: IRecipeCategory,
                            ) => {
                                if (
                                    value?.find(v => v.id === checkedValue.id)
                                ) {
                                    onChange(
                                        value?.filter(
                                            v => v.id !== checkedValue.id,
                                        ),
                                    );
                                } else {
                                    onChange([...(value || []), checkedValue]);
                                }
                            };

                            return (
                                <div className="flex flex-wrap gap-y-2 gap-x-3">
                                    {categories.map(category => {
                                        const isChecked = value?.find(
                                            v => v.id === category.id,
                                        )
                                            ? true
                                            : false;
                                        return (
                                            <div key={category.id}>
                                                <input
                                                    key={category.id}
                                                    type="checkbox"
                                                    id={`checkbox-${category.id}`}
                                                    checked={isChecked}
                                                    onChange={() =>
                                                        handleChange(category)
                                                    }
                                                    className="hidden"
                                                />
                                                <label
                                                    htmlFor={`checkbox-${category.id}`}
                                                    className={`py-1 px-2 w-fit h-full flex items-center gap-x-2 whitespace-nowrap cursor-pointer text-base border rounded ${wrapperColorClass(isChecked)} transition-opacity hover:opacity-70`}>
                                                    <div
                                                        className={`relative w-4 h-4 rounded border-[1.5px] transition-colors ${boxColorClass(
                                                            isChecked,
                                                        )}`}>
                                                        {isChecked && (
                                                            <Check
                                                                strokeWidth={
                                                                    3.5
                                                                }
                                                                color={
                                                                    colors.white
                                                                }
                                                                size={16}
                                                                className="absolute top-1/2 -translate-y-1/2 left-0"
                                                            />
                                                        )}
                                                    </div>
                                                    {category.name}
                                                </label>
                                            </div>
                                        );
                                    })}
                                </div>
                            );
                        }}
                    />
                </div>
                <TextButton
                    colorVariant="secondary"
                    onClick={() =>
                        openDialog(RECIPE_SETTING_DIALOG_NAME.CATEGORY, {
                            onAction: () => {},
                        })
                    }>
                    カテゴリーの追加・編集
                    <ChevronRight size={20} />
                </TextButton>
            </div>
        </>
    );
};

export default CategoryEditFields;
