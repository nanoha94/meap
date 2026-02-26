'use client';

import React from 'react';
import dayjs from 'dayjs';

import { BUTTON_TYPE } from '@/constants';
import { RecipeFilterFormData, useRecipeStore } from '@/models/recipe';
import Button from '../Button';
import { StyledDatePicker, StyledSelect } from '../form-fields';
import { VerticalRowField, VerticaFromToField } from '../react-hook-form';
import { useDialog } from '@/hooks';
import { useForm } from 'react-hook-form';

interface Props {
    search: (filterOptions: RecipeFilterFormData) => void;
}

const RecipeFilterForm = ({ search }: Props) => {
    const listFilterOptions = useRecipeStore(state => state.listFilterOptions);
    const { control, handleSubmit, getValues, trigger, formState: { errors } } = useForm<RecipeFilterFormData>({
        defaultValues: listFilterOptions,
    });
    const { categories } = useRecipeStore();
    const { closeDialog } = useDialog();

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
                {categories.length > 0 && <VerticalRowField
                    control={control}
                    name="categoryId"
                    label="カテゴリ―">
                    {({ value, onChange }) => (
                        <StyledSelect
                            value={value as string}
                            name="categoryIds"
                            options={categories}
                            isShowPlaceholder={true}
                            onChange={onChange}
                        />
                    )}
                </VerticalRowField>}
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