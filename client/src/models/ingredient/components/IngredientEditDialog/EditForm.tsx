'use client';
import React from 'react';
import { Button } from '@/components/common';
import { HorizontalRowField } from '@/components/react-hook-form';
import { useForm } from 'react-hook-form';
import StyledSelect from '@/components/common/StyledSelect';
import { defaultIngredientItem } from '@/models/ingredient/constants';
import { IIngredientItem } from '@/types/api/ingredient';
import { useIngredientStore } from '@/models/ingredient/hooks';
import { TMP_ID_PREFIX } from '@/constants/tmpIdPrefix';

interface Props {
    editingItem: IIngredientItem | undefined;
    actionButtonText: string;
    onClose: () => void;
    onAction: (value: IIngredientItem) => void;
}
type FormData = IIngredientItem;

const EditForm = ({
    editingItem,
    actionButtonText,
    onClose,
    onAction,
}: Props) => {
    const prefix: string = TMP_ID_PREFIX.INGREDIENT_ITEM;
    const { units } = useIngredientStore();
    const { control, handleSubmit, reset, watch, setValue } = useForm<FormData>(
        {
            defaultValues: {
                ...defaultIngredientItem,
                id: `${prefix}${Date.now()}`,
            },
        },
    );
    const nameInputRef = React.useRef<HTMLInputElement>(null);

    const watchedName = watch('name');
    const watchedQuantity = watch('quantity');
    const watchedUnitId = watch('unit.id');
    const watchedUnit = watch('unit');

    /**
     * 数量の入力可/不可
     */
    const isDisabledQuantity: boolean = React.useMemo(() => {
        return !(watchedUnit?.requiresQuantity ?? false);
    }, [watchedUnit]);

    /**
     * 送信ボタンの無効化判定
     */
    const isDisabledSendButton = React.useMemo(() => {
        // 食材名が空の場合、単位が選択されていない場合は送信ボタンを無効化
        // 数量が必要の場合は数量が入力されていない場合は送信ボタンを無効化
        return (
            watchedName === '' ||
            watchedUnitId === '' ||
            (!isDisabledQuantity && !watchedQuantity)
        );
    }, [watchedName, watchedUnitId, watchedQuantity, isDisabledQuantity]);

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
    }, [editingItem]);

    // 単位の監視
    React.useEffect(() => {
        const unit = units.find(v => v.id === watchedUnitId);
        if (unit) {
            setValue('unit', unit);
            if (!unit.requiresQuantity) {
                setValue('quantity', null);
            }
        }
    }, [watchedUnitId]);

    /**
     * フォームの送信
     */
    const onSubmit = (data: FormData) => {
        console.log(data);
        onAction(data);
    };

    return (
        <form
            onSubmit={handleSubmit(onSubmit)}
            className="w-full flex flex-col gap-y-10">
            <div className="mx-auto max-w-[440px] w-full flex flex-col gap-y-5">
                <div className="w-full flex flex-col gap-y-2">
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
            </div>
            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                <Button
                    type="button"
                    colorVariant="gray"
                    variant="outlined"
                    onClick={onClose}>
                    戻る
                </Button>
                <Button type="submit" disabled={isDisabledSendButton}>
                    {actionButtonText}
                </Button>
            </div>
        </form>
    );
};

export default EditForm;
