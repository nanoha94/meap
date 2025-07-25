'use client';

import { Button } from '@/components/common';
import { HorizontalRowField } from '@/components/react-hook-form';
import { useForm } from 'react-hook-form';
import { useRecipeStore } from '../../hooks/recipeStores';
import { ISeasoning } from '@/types/api/recipe';
import StyledSelect from '@/components/common/StyledSelect';
import React from 'react';

interface Props {
    editingItem: ISeasoning | undefined;
    actionButtonText: string;
    onClose: () => void;
    onAction: (value: ISeasoning) => void;
}
type FormData = ISeasoning;

const EditForm = ({
    editingItem,
    actionButtonText,
    onClose,
    onAction,
}: Props) => {
    const { seasoningUnits } = useRecipeStore();
    const defaultValues: FormData = {
        id: '',
        name: '',
        quantity: undefined,
        unitId: '',
    };
    const { control, handleSubmit, reset } = useForm<FormData>({
        defaultValues,
    });
    const nameInputRef = React.useRef<HTMLInputElement>(null);

    React.useEffect(() => {
        nameInputRef.current?.focus();
    }, []);

    React.useEffect(() => {
        if (editingItem) {
            reset(editingItem);
        }
    }, [editingItem]);

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
            <div className="mx-auto max-w-[440px] flex flex-col gap-y-5">
                <div className="w-full flex flex-col gap-y-2">
                    <HorizontalRowField
                        control={control}
                        name="name"
                        label="調味料名">
                        {({ value, onChange, id }) => (
                            <input
                                ref={nameInputRef}
                                type="text"
                                id={id}
                                value={value as string}
                                placeholder="調味料名を入力"
                                onChange={e => onChange(e.target.value)}
                                className="py-2 px-4 text-base border rounded-lg outline-none border-gray-main"
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
                                className="py-2 px-4 text-base border rounded-lg outline-none border-gray-main"
                                inputMode="numeric"
                                pattern="\d*"
                            />
                        )}
                    </HorizontalRowField>
                    <HorizontalRowField
                        control={control}
                        name="unitId"
                        label="単位">
                        {({ value, onChange, id }) => (
                            <StyledSelect
                                value={value as string}
                                name={id}
                                onChange={e => onChange(e.target.value)}
                                options={seasoningUnits}
                            />
                        )}
                    </HorizontalRowField>
                </div>
                <p className="text-sm text-black">
                    ※「少々」などの数量で表せない場合は、調味料名と単位のみ記入してください。
                </p>
            </div>
            <div className="mx-auto max-w-[320px] w-full flex gap-x-6">
                <Button
                    type="button"
                    colorVariant="gray"
                    variant="outlined"
                    onClick={onClose}>
                    戻る
                </Button>
                <Button type="submit">{actionButtonText}</Button>
            </div>
        </form>
    );
};

export default EditForm;
