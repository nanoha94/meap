'use client';

import React from 'react';
import dayjs from 'dayjs';

import { BUTTON_TYPE } from '@/constants';
import { RecipeFilterFormData, useRecipeStore } from '@/models/recipe';
import Button from '../Button';
import { CheckboxField, StyledDatePicker } from '../form-fields';
import { VerticalRowField, VerticaFromToField } from '../react-hook-form';
import { useDialog, useNavigationGuard } from '@/hooks';
import { Controller, useForm, useWatch } from 'react-hook-form';

interface Props {
    search: (filterOptions: RecipeFilterFormData) => void;
    defaultValues?: RecipeFilterFormData;
    /** 親（例: PlanEditPage）が既に useNavigationGuard を掛けている子ダイアログで true。二重の pushState / back で誤検知しないため */
    suppressNavigationGuard?: boolean;
}

const RecipeFilterForm = ({ search, defaultValues, suppressNavigationGuard = false }: Props) => {
    // store
    const categories = useRecipeStore(state => state.categories);

    // hook
    const { closeDialog, updateCurrentDialogConfig } = useDialog();

    const { control, handleSubmit, getValues, trigger, formState: { errors }, setValue } = useForm<RecipeFilterFormData>({
        defaultValues: defaultValues,
    });
    /** フォーム値の変更を購読し、入力変更時に再レンダーさせる */
    const currentValues = useWatch<RecipeFilterFormData>({ control, defaultValue: defaultValues });

    /**
     * 送信ボタンの無効化判定
     * フォームのデータが変更されていない場合は送信ボタンを無効化
     */
    const isDisabledSendButton = JSON.stringify(currentValues) === JSON.stringify(defaultValues);
    useNavigationGuard(suppressNavigationGuard ? false : !isDisabledSendButton);

    /**
     * 閉じる前確認の要否をフォーム状態に合わせて更新
     */
    React.useEffect(() => {
        updateCurrentDialogConfig({ isCheckBeforeClose: !isDisabledSendButton });
    }, [isDisabledSendButton, updateCurrentDialogConfig]);

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
        closeDialog(false);
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="w-full flex flex-col gap-y-10">
            <div className="mx-auto w-full flex flex-col gap-y-4">
                <VerticalRowField
                    control={control}
                    name="recipeName"
                    label="料理名">
                    {({ value, onChange, id }) => (
                        <input
                            autoFocus
                            type="text"
                            id={id}
                            value={value as string}
                            placeholder="料理名を入力"
                            onChange={e => onChange(e.target.value)}
                            className="py-2 px-4 border rounded-lg border-gray-main"
                        />
                    )}
                </VerticalRowField>
                <VerticalRowField
                    control={control}
                    name="ingredientName"
                    label="材料名">
                    {({ value, onChange, id }) => (
                        <input
                            type="text"
                            id={id}
                            value={value as string}
                            placeholder="材料名を入力"
                            onChange={e => onChange(e.target.value)}
                            className="py-2 px-4 border rounded-lg border-gray-main" />
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
                    {({ value, onChange, hasError, isFrom, pairedFieldValue }) => {
                        const dateValue = value && typeof value === 'string' ? new Date(value) : undefined;
                        const pairedFieldData = pairedFieldValue && typeof pairedFieldValue === 'string'
                            ? new Date(pairedFieldValue)
                            : undefined;
                        return (
                            <StyledDatePicker
                                value={dateValue}
                                minDate={!isFrom ? pairedFieldData : undefined}
                                maxDate={isFrom ? pairedFieldData : undefined}
                                hasClearButton={true}
                                hasError={hasError}
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
