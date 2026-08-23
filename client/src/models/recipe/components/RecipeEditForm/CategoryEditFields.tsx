'use client';
import React from 'react';
import { ChevronRight } from 'lucide-react';
import { Control, Controller, useFormContext } from 'react-hook-form';

import { CheckboxField, RecipeCategoryEditForm, TextButton } from '@/components';
import { useDialog } from '@/hooks';
import { useRecipeStore } from '@/models/recipe/hooks';
import { RecipeEditFormData } from '@/models/recipe/types';
import { IRecipeCategory } from '@/types';

interface Props {
    control: Control<RecipeEditFormData>;
}

const CategoryEditFields = ({ control }: Props) => {
    // store
    const categories = useRecipeStore(state => state.categories);

    // hook
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
                                            <CheckboxField key={category.id} id={`checkbox-${category.id}`} checked={isChecked} onChange={() => handleChange(category, value)} label={category.name} />
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
                    onClick={() => {
                        openDialog({
                            title: '料理カテゴリーを設定',
                            children: <RecipeCategoryEditForm />,
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
