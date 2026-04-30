'use client';
import React from 'react';
import { useForm, useWatch } from 'react-hook-form';

import { Button, HorizontalRowField, StyledSelect } from '@/components';
import { BUTTON_TYPE, BUTTON_VARIANT, COLOR_VARIANT, TMP_ID_PREFIX } from '@/constants';
import { useDialog } from '@/hooks';
import { defaultIngredientItem, useIngredientStore } from '@/models/ingredient';
import { IIngredientItem, IIngredientUnit } from '@/types';

/**
 * 材料編集フォームの「変更なし」比較用（単位マスタの requiresQuantity に合わせて数量を揃える）
 */
const stringifyIngredientForCompare = (
    name: string | undefined,
    quantity: number | null | undefined,
    unitId: string | undefined,
    categoryId: string | undefined,
    unitDef: IIngredientUnit | null | undefined,
) => {
    const requiresQuantity = unitDef?.requiresQuantity ?? true;
    return JSON.stringify({
        name: name ?? '',
        quantity: requiresQuantity ? (quantity ?? null) : null,
        unitId: unitId ?? '',
        categoryId: categoryId ?? '',
    });
};

interface Props {
    editingItem: IIngredientItem | undefined;
    actionButtonText: string;
    onAction: (value: IIngredientItem) => void;
}
type FormData = IIngredientItem;

const IngredientEditForm = ({
    editingItem,
    actionButtonText,
    onAction,
}: Props) => {
    // constant value
    const prefix: string = TMP_ID_PREFIX.INGREDIENT_ITEM;

    // store
    const units = useIngredientStore(state => state.units);

    // hook
    const { closeDialog, updateCurrentDialogConfig } = useDialog();
    const { control, handleSubmit, reset, setValue } = useForm<FormData>(
        {
            defaultValues: {
                ...defaultIngredientItem,
                id: `${prefix}${Date.now()}`,
            },
        },
    );
    const nameInputRef = React.useRef<HTMLInputElement>(null);
    const watchedName = useWatch({ control, name: 'name' });
    const watchedQuantity = useWatch({ control, name: 'quantity' });
    const watchedUnitId = useWatch({ control, name: 'unit.id' });
    const watchedUnit = useWatch({ control, name: 'unit' });
    const watchedCategoryId = useWatch({ control, name: 'categoryId' });

    /**
     * 数量の入力可/不可
     */
    const isDisabledQuantity: boolean = React.useMemo(() => {
        return !(watchedUnit?.requiresQuantity ?? true);
    }, [watchedUnit]);

    /**
     * 送信ボタンの無効化判定
     * 必須項目が欠ける、または編集開始時点（editingItem）と同一内容の場合は true（無効）
     * ※IngredientCategoryEditForm と同様に JSON.stringify で比較
     */
    const isDisabledSendButton = React.useMemo(() => {
        const invalid =
            watchedName === '' ||
            watchedUnitId === '' ||
            (!isDisabledQuantity && !watchedQuantity);

        if (!editingItem) {
            return invalid;
        }

        const baselineUnit =
            units.find(u => u.id === editingItem.unit?.id) ??
            editingItem.unit ??
            undefined;

        const unchanged =
            stringifyIngredientForCompare(
                watchedName,
                watchedQuantity,
                watchedUnitId,
                watchedCategoryId,
                watchedUnit,
            ) ===
            stringifyIngredientForCompare(
                editingItem.name,
                editingItem.quantity,
                editingItem.unit?.id,
                editingItem.categoryId,
                baselineUnit,
            );

        return invalid || unchanged;
    }, [
        watchedName,
        watchedUnitId,
        watchedQuantity,
        watchedCategoryId,
        watchedUnit,
        isDisabledQuantity,
        editingItem,
        units,
    ]);

    /**
     * 閉じる前確認の要否をフォーム状態に合わせて更新
     */
    React.useEffect(() => {
        updateCurrentDialogConfig({ isCheckBeforeClose: !isDisabledSendButton });
    }, [isDisabledSendButton, updateCurrentDialogConfig]);

    /**
     * フォーカスを当てる
     */
    React.useEffect(() => {
        nameInputRef.current?.focus();
    }, []);

    /**
     * 編集対象の食材を設定
     */
    React.useEffect(() => {
        if (editingItem) {
            reset(editingItem);
        }
    }, [editingItem, reset]);

    // 単位の監視
    React.useEffect(() => {
        const unit = units.find(v => v.id === watchedUnitId);
        if (unit) {
            setValue('unit', unit);
            if (!unit.requiresQuantity) {
                setValue('quantity', null);
            }
        }
    }, [watchedUnitId, units, setValue]);

    /**
     * フォームの送信
     */
    const onSubmit = (data: FormData) => {
        onAction(data);
    };

    return (
        <form
            onSubmit={handleSubmit(onSubmit)}
            className="w-full flex flex-col gap-y-10">
            <div className="mx-auto w-full flex flex-col gap-y-2">
                <HorizontalRowField
                    control={control}
                    name="name"
                    label="材料名">
                    {({ value, onChange, id }) => (
                        <input
                            ref={nameInputRef}
                            type="text"
                            id={id}
                            value={value as string}
                            placeholder="材料名を入力"
                            onChange={e => onChange(e.target.value)}
                            className="py-2 px-4 border rounded-lg outline-none border-gray-main"
                        />
                    )}
                </HorizontalRowField>
                <HorizontalRowField
                    control={control}
                    name="quantity"
                    label="数量">
                    {({ value, onChange, id }) => (
                        <input
                            type="number"
                            id={id}
                            value={
                                value === undefined || value === null
                                    ? ''
                                    : (value as number)
                            }
                            placeholder="数量を入力"
                            min={0}
                            disabled={isDisabledQuantity}
                            onChange={e => {
                                const val = e.target.value;
                                // 空欄ならnull、整数なら数値、小数や不正値は無視
                                if (val === '') {
                                    onChange(null);
                                } else if (/^-?\d+$/.test(val)) {
                                    onChange(Number(val));
                                }
                                // それ以外（小数や不正値）は何もしない
                            }}
                            className="py-2 px-4 border rounded-lg outline-none border-gray-main"
                            inputMode="numeric"
                            pattern="\d*"
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
