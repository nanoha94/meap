import React from 'react';

interface Props {
    children: React.ReactNode;
    id?: string;
    label: string;
    memo?: string;
    required?: boolean;
    errorMessage?: string[];
}

const FormItem = ({
    children,
    label,
    memo,
    required = false,
    errorMessage,
}: Props) => (
    <div className="flex flex-col gap-y-2">
        <div className="flex flex-col gap-y-1">
            <label className="text-base">
                {label}
                {required &&
                    '<span className="text-alert-main">（必須）</span>'}
            </label>
            {memo && <p className="text-xs">{memo}</p>}
        </div>
        <div className="flex flex-col gap-y-1">
            {children}
            {errorMessage.map(
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

export default FormItem;
