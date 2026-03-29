'use client';
import React from 'react';
import {
    Control,
    Controller,
    FieldValues,
    Path,
    RegisterOptions,
    UseFormGetValues,
    UseFormTrigger,
} from 'react-hook-form';

interface Props<T extends FieldValues> {
    id?: string;
    label: string;
    memo?: string;
    required?: boolean;
    errorMessage?: string[];
    control: Control<T>;
    rules?: RegisterOptions<T>;
    fromName: Path<T>;
    toName: Path<T>;
    getValues?: UseFormGetValues<T>;
    trigger?: UseFormTrigger<T>;
    rangeValidate?: (fromValue: unknown, toValue: unknown) => true | string;
    children: (fieldProps: {
        value: unknown;
        onChange: (v: unknown) => void;
        id: string;
    }) => React.ReactElement;
}

const VerticaFromToField = <T extends FieldValues>({
    children,
    label,
    memo,
    required = false,
    errorMessage,
    control,
    rules,
    fromName,
    toName,
    getValues,
    trigger,
    rangeValidate,
}: Props<T>) => {
    const toRules: RegisterOptions<T> = {
        ...rules,
        ...(rangeValidate && getValues && {
            validate: (toValue) => {
                const fromValue = getValues(fromName);
                return rangeValidate(fromValue, toValue);
            },
        }),
    };

    return (
        <div className="flex flex-col gap-y-2">
            <div className="flex flex-col gap-y-1">
                <label>
                    {label}
                    {required && <span className="text-alert-main">（必須）</span>}
                </label>
                {memo && <p className="text-xs">{memo}</p>}
            </div>
            <div className="flex flex-col gap-y-1">
                <div className="flex items-center flex-wrap md:flex-nowrap gap-3">
                    <Controller
                        control={control}
                        name={fromName}
                        rules={rules}
                        render={({ field: { onChange, value } }) =>
                            children({
                                value,
                                onChange: (v: unknown) => {
                                    onChange(v);
                                    // fromNameが変更されたら、toNameのバリデーションを再実行
                                    if (trigger && rangeValidate) {
                                        trigger(toName).catch(() => {
                                            // エラーは無視（バリデーションエラーは正常な動作）
                                        });
                                    }
                                },
                                id: fromName,
                            })
                        }
                    />
                    <span>～</span>
                    <Controller
                        control={control}
                        name={toName}
                        rules={toRules}
                        render={({ field: { onChange, value } }) =>
                            children({
                                value,
                                onChange: (v: unknown) => {
                                    onChange(v);
                                    // toNameが変更されたら、toNameのバリデーションを再実行
                                    if (trigger && rangeValidate) {
                                        trigger(toName).catch(() => {
                                            // エラーは無視（バリデーションエラーは正常な動作）
                                        });
                                    }
                                },
                                id: toName,
                            })
                        }
                    />
                </div>
                {errorMessage?.map(
                    (v, idx) =>
                        !!v && (
                            <p key={idx} className="text-sm text-alert-main">
                                {v}
                            </p>
                        ),
                )}
            </div>
        </div>
    );
};

export default VerticaFromToField;
