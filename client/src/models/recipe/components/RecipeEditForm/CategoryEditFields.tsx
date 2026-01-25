'use client';
import { TextButton } from '@/components/common';
import { Control, Controller, useFormContext } from 'react-hook-form';
import { IRecipeCategory } from '@/types/api';
import { Check, ChevronRight } from 'lucide-react';
import React from 'react';
import { useRecipeStore } from '@/models/recipe/hooks';
import { COLOR_VARIANT, colors, DIALOG_NAME } from '@/constants';
import { RecipeEditFormData } from '@/models/recipe/types';
import { useDialog } from '@/hooks/useDialog';
import { RECIPE_SETTING_DIALOG_CONFIGS } from '@/models/recipe/constants';
import RecipeCategoryEditForm from '@/components/dialog-contents/RecipeCategoryEditForm';

interface Props {
    control: Control<RecipeEditFormData>;
}

const CategoryEditFields = ({ control }: Props) => {
    const { categories } = useRecipeStore();
    const { setValue } = useFormContext<RecipeEditFormData>();
    const { openDialog } = useDialog();

    /**
     * カテゴリーのチェック状態を変更
     * @param checkedCategory チェックされたカテゴリー
     * @param currentCheckedCategories 現在チェックされているカテゴリー
     */
    const handleChange = (
        checkedCategory: IRecipeCategory,
        currentCheckedCategories: IRecipeCategory[] = [],
    ) => {
        const isChecked = currentCheckedCategories.find(
            cat => cat.id === checkedCategory.id,
        );

        // チェックされている場合は削除
        if (isChecked) {
            setValue(
                'categories',
                currentCheckedCategories.filter(
                    cat => cat.id !== checkedCategory.id,
                ),
            );
        } else {
            // チェックされていない場合は追加
            setValue('categories', [
                ...currentCheckedCategories,
                checkedCategory,
            ]);
        }
    };

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
                    <div>カテゴリー</div>
                    {categories && categories.length > 0 ? (
                        <Controller
                            control={control}
                            name="categories"
                            render={({ field: { value } }) => (
                                <div className="flex flex-wrap gap-y-2 gap-x-3">
                                    {categories.map(category => {
                                        const isChecked = value?.some(
                                            v => v.id === category.id,
                                        );
                                        return (
                                            <div key={category.id}>
                                                <input
                                                    type="checkbox"
                                                    id={`checkbox-${category.id}`}
                                                    checked={isChecked}
                                                    onChange={() =>
                                                        handleChange(
                                                            category,
                                                            value,
                                                        )
                                                    }
                                                    className="hidden"
                                                />
                                                <label
                                                    htmlFor={`checkbox-${category.id}`}
                                                    className={`py-1 px-2 w-fit h-full flex items-center gap-x-2 whitespace-nowrap cursor-pointer border rounded ${wrapperColorClass(isChecked)} transition-opacity hover:opacity-70`}>
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
                            )}
                        />
                    ) : (
                        <div className="py-2 px-4 flex flex-col gap-y-2 bg-gray-light rounded">
                            <p>
                                登録されているカテゴリーはありません。
                                <br />
                                以下からカテゴリーを登録してください。
                            </p>
                        </div>
                    )}
                </div>
                <TextButton
                    colorVariant={COLOR_VARIANT.SECONDARY}
                    onClick={() => {
                        const dialogName = DIALOG_NAME.RECIPE_CATEGORY_SETTING;
                        const dialogConfig = RECIPE_SETTING_DIALOG_CONFIGS[dialogName];
                        openDialog({
                            title: dialogConfig.title,
                            children: () => <RecipeCategoryEditForm />
                        });
                    }}>
                    カテゴリーの追加・編集
                    <ChevronRight size={20} />
                </TextButton>
            </div>
        </>
    );
};

export default CategoryEditFields;
