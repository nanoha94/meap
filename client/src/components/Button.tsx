import React from 'react';

import { BUTTON_TYPE, BUTTON_VARIANT, ButtonType, ButtonVariant, COLOR_VARIANT } from '@/constants';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    type?: ButtonType;
    variant?: ButtonVariant;
    colorVariant?: (typeof COLOR_VARIANT)['PRIMARY'] | (typeof COLOR_VARIANT)['GRAY'] | (typeof COLOR_VARIANT)['ALERT'];
    disabled?: boolean;
    className?: string;
}

const colorClass: Record<
    NonNullable<Props['variant']>,
    Record<NonNullable<Props['colorVariant']>, string>
> = {
    filled: {
        [COLOR_VARIANT.PRIMARY]:
            'text-white bg-primary-main hover:text-primary-main hover:bg-primary-light',
        [COLOR_VARIANT.GRAY]: 'text-white bg-gray-main hover:text-gray-main hover:bg-gray-light',
        [COLOR_VARIANT.ALERT]: 'text-white bg-alert-main hover:text-alert-main hover:bg-alert-light',
    },
    outlined: {
        [COLOR_VARIANT.PRIMARY]:
            'text-primary-main border-2 border-primary-main hover:bg-primary-light',
        [COLOR_VARIANT.GRAY]: 'text-gray-main border-2 border-gray-main hover:bg-gray-light',
        [COLOR_VARIANT.ALERT]: 'text-alert-main border-2 border-alert-main hover:bg-alert-light',
    },
};

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
            className={`${className} appearance-none p-3 w-full font-bold rounded-lg transition-colors ${colorClass[variant][colorVariant]} disabled:opacity-50 disabled:pointer-events-none`}
            disabled={disabled}
            {...props}
        />
    );
};

export default Button;
