import React from 'react';
import {
    Control,
    Controller,
    FieldValues,
    Path,
    RegisterOptions,
} from 'react-hook-form';

interface Props<T extends FieldValues> {
    id?: string;
    label: string;
    memo?: string;
    required?: boolean;
    errorMessage?: string[];
    control: Control<T>;
    rules?: RegisterOptions<T>;
    name: Path<T>;
    children: (fieldProps: {
        value: unknown;
        onChange: (v: unknown) => void;
        id: string;
    }) => React.ReactElement;
}

const VerticalRowField = <T extends FieldValues>({
    children,
    label,
    memo,
    required = false,
    errorMessage,
    control,
    rules,
    name,
}: Props<T>) => (
    <div className="flex flex-col gap-y-2">
        <div className="flex flex-col gap-y-1">
            <label>
                {label}
                {required && <span className="text-alert-main">（必須）</span>}
            </label>
            {memo && <p className="text-xs">{memo}</p>}
        </div>
        <div className="flex flex-col gap-y-1">
            <Controller
                control={control}
                name={name}
                rules={rules}
                render={({ field: { onChange, value } }) =>
                    children({ value, onChange, id: name })
                }
            />
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

export default VerticalRowField;
