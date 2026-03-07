'use client';

import React from 'react';
import dayjs from 'dayjs';

import { BUTTON_TYPE } from '@/constants';
import { RecipeFilterFormData, useRecipeStore } from '@/models/recipe';
import Button from '../Button';
import { CheckboxField, StyledDatePicker } from '../form-fields';
import { VerticalRowField, VerticaFromToField } from '../react-hook-form';
import { useDialog } from '@/hooks';
import { Controller, useForm } from 'react-hook-form';

interface Props {
    search: (filterOptions: RecipeFilterFormData) => void;
    defaultValues?: RecipeFilterFormData;
}

const RecipeFilterForm = ({ search, defaultValues }: Props) => {
    const { control, handleSubmit, getValues, trigger, formState: { errors }, setValue } = useForm<RecipeFilterFormData>({
        defaultValues: defaultValues,
    });
    const { categories } = useRecipeStore();
    const { closeDialog } = useDialog();

    /**
    * カテゴリーのチェック状態を変更
    * @param checkedId チェックされたカテゴリーID
    * @param currentCheckedIds 現在チェックされているカテゴリーID
    */
    const handleChange = (
        checkedId: string,
        currentCheckedIds: string[] = [],
    ) => {
        const isChecked = currentCheckedIds.find(
            id => id === checkedId,
        );

        // チェックされている場合は削除
        if (isChecked) {
            setValue(
                'categoryIds',
                currentCheckedIds.filter(
                    id => id !== checkedId,
                ),
            );
        } else {
            // チェックされていない場合は追加
            setValue('categoryIds', [
                ...currentCheckedIds,
                checkedId,
            ]);
        }
    };

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = (data: RecipeFilterFormData) => {
        search(data);
        closeDialog();
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="w-full flex flex-col gap-y-10">
            <div className="mx-auto w-full flex flex-col gap-y-4">
                <VerticalRowField
                    control={control}
                    name="recipeName"
                    label="料理名">
                    {({ value, onChange, id }) => (
                        <input type="text" id={id} value={value as string} placeholder="料理名を入力" onChange={e => onChange(e.target.value)} className="py-2 px-4 border rounded-lg outline-none border-gray-main" />
                    )}
                </VerticalRowField>
                <VerticalRowField
                    control={control}
                    name="ingredientName"
                    label="材料名">
                    {({ value, onChange, id }) => (
                        <input type="text" id={id} value={value as string} placeholder="料理名を入力" onChange={e => onChange(e.target.value)} className="py-2 px-4 border rounded-lg outline-none border-gray-main" />
                    )}
                </VerticalRowField>
                {categories.length > 0 &&
                    <div className="flex flex-col gap-y-2">
                        <div>カテゴリー</div>
                        <Controller
                            control={control}
                            name="categoryIds"
                            render={({ field: { value } }) => (
                                <div className="flex flex-wrap gap-y-2 gap-x-3">
                                    {categories.map(category => {
                                        const isChecked = value?.some(
                                            v => v === category.id.toString(),
                                        );
                                        return (
                                            <CheckboxField key={category.id} id={`checkbox-${category.id}`} checked={isChecked || false} onChange={() => handleChange(category.id.toString(), value)} label={category.name} />
                                        );
                                    })}
                                </div>
                            )}
                        />
                    </div>
                }
                <VerticaFromToField
                    control={control}
                    fromName="lastPlannedDateFrom"
                    toName="lastPlannedDateTo"
                    label="前回の献立日"
                    getValues={getValues}
                    trigger={trigger}
                    errorMessage={errors?.lastPlannedDateTo?.message ? [errors.lastPlannedDateTo.message] : undefined}
                    rangeValidate={(from, to) => {
                        if (!from || !to) return true;
                        return from <= to ? true : '終了日はより後の日付にしてください';
                    }}>
                    {({ value, onChange }) => {
                        const dateValue = value && typeof value === 'string' ? new Date(value) : undefined;
                        return (
                            <StyledDatePicker
                                value={dateValue}
                                hasClearButton={true}
                                onChange={(d) => onChange(d ? dayjs(d).format('YYYY-MM-DD') : '')}
                            />
                        );
                    }}
                </VerticaFromToField>
            </div>
            <Button type={BUTTON_TYPE.SUBMIT}>
                この条件で検索する
            </Button>
        </form>
    );
};

export default RecipeFilterForm;