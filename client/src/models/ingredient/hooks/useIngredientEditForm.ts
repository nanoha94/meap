'use client';

import React from 'react';
import { useForm, useWatch } from 'react-hook-form';

import { QUANTITY_INVALID_MESSAGE } from '@/constants';
import { defaultIngredientItem, useIngredientStore } from '@/models/ingredient';
import { IIngredientItem, IIngredientUnit } from '@/types';
import {
    formatQuantityDisplay,
    isPartialQuantityInput,
    normalizeQuantityDisplay,
    normalizeQuantityFromDisplay,
    parseQuantityDisplayToNumber,
} from '@/utils';

type FormData = IIngredientItem;

/** 
 * reset 時に quantityDisplay が空なら quantity から表示用文字列を補完する
 */
const toFormValues = (item: IIngredientItem): IIngredientItem => {
    const requiresQuantity = item.unit?.requiresQuantity ?? true;

    if (!requiresQuantity) {
        return { ...item, quantityDisplay: null };
    }

    const formatted = formatQuantityDisplay(item.quantity, item.quantityDisplay);

    return { ...item, quantityDisplay: formatted !== '' ? formatted : null };
};

/**
 * 材料編集フォームの「変更なし」比較用（単位マスタの requiresQuantity に合わせて数量を揃える）
 */
const stringifyIngredientForCompare = (
    name: string | undefined,
    quantityDisplay: string | null | undefined,
    unitId: string | undefined,
    categoryId: string | undefined,
    unitDef: IIngredientUnit | null | undefined,
) => {
    const requiresQuantity = unitDef?.requiresQuantity ?? true;
    const { quantity, quantityDisplay: display } = normalizeQuantityFromDisplay(
        quantityDisplay,
        requiresQuantity,
    );

    return JSON.stringify({
        name: name ?? '',
        quantity,
        quantityDisplay: display,
        unitId: unitId ?? '',
        categoryId: categoryId ?? '',
    });
};

/**
 * 数量表示のバリデーションルール
 */
const quantityDisplayRules = {
    validate: (value: string | null | undefined, formValues: FormData): true | string => {
        if (!(formValues.unit?.requiresQuantity ?? true)) {
            return true;
        }

        // 空文字の場合は true
        if (value == null || value === '') {
            return true;
        }

        // 入力途中状態の場合は true
        if (isPartialQuantityInput(value)) {
            return true;
        }

        // パースできない場合はエラーメッセージを返す
        if (parseQuantityDisplayToNumber(value) === null) {
            return QUANTITY_INVALID_MESSAGE;
        }

        return true;
    },
};

// ------------------------------------------------------------

interface UseIngredientEditFormParams {
    editingItem: IIngredientItem | undefined;
    onAction: (value: IIngredientItem) => void;
}

export const useIngredientEditForm = ({
    editingItem,
    onAction,
}: UseIngredientEditFormParams) => {
    const units = useIngredientStore(state => state.units);

    const {
        control,
        handleSubmit,
        reset,
        setValue,
        trigger,
        clearErrors,
        formState: { errors },
    } = useForm<FormData>({
        defaultValues: defaultIngredientItem,
    });
    const watchedName = useWatch({ control, name: 'name' });
    const watchedQuantityDisplay = useWatch({ control, name: 'quantityDisplay' });
    const watchedUnitId = useWatch({ control, name: 'unit.id' });
    const watchedUnit = useWatch({ control, name: 'unit' });
    const watchedCategoryId = useWatch({ control, name: 'categoryId' });

    /**
     * 数量の入力可/不可を判定
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
        const parsedQuantity =
            watchedQuantityDisplay != null && watchedQuantityDisplay !== ''
                ? parseQuantityDisplayToNumber(watchedQuantityDisplay.trim())
                : null;

        const invalid =
            watchedName === '' ||
            watchedUnitId === '' ||
            !!errors.quantityDisplay ||
            (!isDisabledQuantity && parsedQuantity === null);

        if (!editingItem) {
            return invalid;
        }

        // 編集開始時点のフォーム値を取得
        const initialItem = toFormValues(editingItem);
        const initialUnit =
            units.find(u => u.id === initialItem.unit?.id) ??
            initialItem.unit ??
            undefined;

        // 編集開始時点と現在のフォーム値が同一かどうかを判定
        const unchanged =
            stringifyIngredientForCompare(
                watchedName,
                watchedQuantityDisplay,
                watchedUnitId,
                watchedCategoryId,
                watchedUnit,
            ) ===
            stringifyIngredientForCompare(
                initialItem.name,
                initialItem.quantityDisplay,
                initialItem.unit?.id,
                initialItem.categoryId,
                initialUnit,
            );

        return invalid || unchanged;
    }, [
        watchedName,
        watchedUnitId,
        watchedQuantityDisplay,
        watchedCategoryId,
        watchedUnit,
        isDisabledQuantity,
        errors.quantityDisplay,
        editingItem,
        units,
    ]);

    /**
     * 編集対象の食材を設定
     */
    React.useEffect(() => {
        if (editingItem) {
            reset(toFormValues(editingItem));
        }
    }, [editingItem, reset]);

    /**
     * 単位が変更された場合は数量表示をクリア
     */
    React.useEffect(() => {
        const unit = units.find(v => v.id === watchedUnitId);
        if (unit) {
            setValue('unit', unit);
            if (!unit.requiresQuantity) {
                setValue('quantityDisplay', null);
                clearErrors('quantityDisplay');
            }
        }
    }, [watchedUnitId, units, setValue, clearErrors]);

    /**
    * フォームの送信
    */
    const onSubmit = (data: FormData) => {
        const requiresQuantity = data.unit?.requiresQuantity ?? true;
        const { quantity, quantityDisplay } = normalizeQuantityFromDisplay(
            data.quantityDisplay,
            requiresQuantity,
        );

        onAction({ ...data, quantity, quantityDisplay });
    };

    /**
     * 数量表示の入力変更時の処理
     */
    const handleQuantityChange =
        (onChange: (value: string | null) => void) =>
            (e: React.ChangeEvent<HTMLInputElement>) => {
                const val = e.target.value;
                onChange(val === '' ? null : val);
                clearErrors('quantityDisplay');
            };

    /**
     * 数量表示の入力フォーカスを外した時の処理
     */
    const handleQuantityBlur =
        (onChange: (value: string | null) => void) =>
            async (e: React.FocusEvent<HTMLInputElement>) => {
                const text = e.target.value;
                const trimmed = text.trim();

                // 空文字の場合は何もしない
                if (trimmed === '') {
                    onChange(null);
                    clearErrors('quantityDisplay');
                    return;
                }

                const parsed = parseQuantityDisplayToNumber(trimmed);
                if (parsed === null) {
                    // 入力途中状態の場合は何もしない
                    if (isPartialQuantityInput(trimmed)) {
                        return;
                    }
                }
                // パースできた場合は表示を正規化
                else {
                    onChange(normalizeQuantityDisplay(trimmed, parsed));
                }

                // バリデーションを実行
                await trigger('quantityDisplay');
            };

    return {
        control,
        errors,
        units,
        isDisabledQuantity,
        isDisabledSendButton,
        clearErrors,
        trigger,
        onSubmit: handleSubmit(onSubmit),
        handleQuantityChange,
        handleQuantityBlur,
        quantityDisplayRules,
    };
};
