'use client';
import { Control, Controller, FieldValues, Path, RegisterOptions } from 'react-hook-form';

interface Props<T extends FieldValues> {
    control: Control<T>;
    rules?: RegisterOptions<T>;
    name: Path<T>;
    label: string;
    errorMessage?: string[];
    children: (fieldProps: {
        value: unknown;
        onChange: (v: unknown) => void;
        id: string;
    }) => React.ReactElement;
}

const HorizontalRowField = <T extends FieldValues>({
    control,
    rules,
    name,
    label,
    errorMessage,
    children,
}: Props<T>) => {
    return (
        <div className="grid grid-cols-[80px_1fr] items-center">
            <label className="after:content-['：']" htmlFor={name}>
                {label}
            </label>
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
};

export default HorizontalRowField;
