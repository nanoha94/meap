'use client';

import React from 'react';

import { Button, HorizontalRowField, StyledSelect } from '@/components';
import { BUTTON_TYPE, BUTTON_VARIANT, COLOR_VARIANT } from '@/constants';
import { useDialog } from '@/hooks';
import { useIngredientEditForm } from '@/models/ingredient';
import { IIngredientItem } from '@/types';

interface Props {
    editingItem: IIngredientItem | undefined;
    actionButtonText: string;
    onAction: (value: IIngredientItem) => void;
}

const IngredientEditForm = ({
    editingItem,
    actionButtonText,
    onAction,
}: Props) => {
    const { closeDialog, updateCurrentDialogConfig } = useDialog();
    const {
        control,
        errors,
        units,
        isDisabledQuantity,
        isDisabledSendButton,
        onSubmit,
        handleQuantityChange,
        handleQuantityBlur,
        quantityDisplayRules,
    } = useIngredientEditForm({ editingItem, onAction });

    /**
     * 閉じる前確認の要否をフォーム状態に合わせて更新
     */
    React.useEffect(() => {
        updateCurrentDialogConfig({ isCheckBeforeClose: !isDisabledSendButton });
    }, [isDisabledSendButton, updateCurrentDialogConfig]);

    return (
        <form
            onSubmit={onSubmit}
            className="w-full flex flex-col gap-y-10">
            <div className="mx-auto w-full flex flex-col gap-y-2">
                <HorizontalRowField
                    control={control}
                    name="name"
                    label="材料名">
                    {({ value, onChange, id }) => (
                        <input
                            autoFocus
                            type="text"
                            id={id}
                            value={value as string}
                            placeholder="材料名を入力"
                            onChange={e => onChange(e.target.value)}
                            className="py-2 px-4 border rounded-lg outline-black border-gray-main"
                        />
                    )}
                </HorizontalRowField>
                <HorizontalRowField
                    control={control}
                    name="quantityDisplay"
                    label="数量"
                    rules={quantityDisplayRules}
                    errorMessage={
                        errors.quantityDisplay?.message
                            ? [errors.quantityDisplay.message]
                            : undefined
                    }>
                    {({ value, onChange, id }) => (
                        <input
                            type="text"
                            id={id}
                            value={
                                isDisabledQuantity
                                    ? ''
                                    : ((value as string | null) ?? '')
                            }
                            placeholder="数量を入力"
                            disabled={isDisabledQuantity}
                            onChange={handleQuantityChange(onChange)}
                            onBlur={handleQuantityBlur(onChange)}
                            className={`py-2 px-4 border rounded-lg outline-black ${errors.quantityDisplay ? 'border-alert-main border-2' : 'border-gray-main'}`}
                            inputMode="decimal"
                        />
                    )}
                </HorizontalRowField>
                <HorizontalRowField
                    control={control}
                    name="unit.id"
                    label="単位">
                    {({ value, onChange, id }) => (
                        <StyledSelect
                            value={value as string}
                            name={id}
                            onChange={e => onChange(e.target.value)}
                            options={units}
                        />
                    )}
                </HorizontalRowField>
            </div>
            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                <Button
                    type={BUTTON_TYPE.BUTTON}
                    colorVariant={COLOR_VARIANT.GRAY}
                    variant={BUTTON_VARIANT.OUTLINED}
                    onClick={() => closeDialog()}>
                    戻る
                </Button>
                <Button type={BUTTON_TYPE.SUBMIT} disabled={isDisabledSendButton}>
                    {actionButtonText}
                </Button>
            </div>
        </form>
    );
};

export default IngredientEditForm;
