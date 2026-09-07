import React from 'react';

import { BUTTON_TYPE, BUTTON_VARIANT, ButtonType, ButtonVariant, COLOR_VARIANT, PRIMARY_BUTTON_COLOR_CLASS } from '@/constants';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    type?: ButtonType;
    variant?: ButtonVariant;
    colorVariant?:
    | (typeof COLOR_VARIANT)['PRIMARY']
    | (typeof COLOR_VARIANT)['GRAY']
    | (typeof COLOR_VARIANT)['ALERT'];
    disabled?: boolean;
    className?: string;
}

const Button = ({
    type = BUTTON_TYPE.SUBMIT,
    variant = BUTTON_VARIANT.FILLED,
    colorVariant = COLOR_VARIANT.PRIMARY,
    disabled = false,
    className,
    ...props
}: Props) => {
    return (
        <button
            type={type}
            className={`${className ?? ''} appearance-none p-3 w-full font-bold rounded-lg transition-colors ${PRIMARY_BUTTON_COLOR_CLASS[variant][colorVariant]} disabled:opacity-50 disabled:pointer-events-none`}
            disabled={disabled}
            {...props}
        />
    );
};

export default Button;
