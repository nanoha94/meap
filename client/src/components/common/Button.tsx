import React from 'react';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    type?: 'submit' | 'button' | 'reset';
    variant?: 'filled' | 'outlined';
    colorVariant?: 'primary' | 'gray' | 'alert';
    disabled?: boolean;
    className?: string;
}

const colorClass: Record<
    NonNullable<Props['variant']>,
    Record<NonNullable<Props['colorVariant']>, string>
> = {
    filled: {
        primary:
            'text-white bg-primary-main hover:text-primary-main hover:bg-primary-light',
        gray: 'text-white bg-gray-main hover:text-gray-main hover:bg-gray-light',
        alert: 'text-white bg-alert-main hover:text-alert-main hover:bg-alert-light',
    },
    outlined: {
        primary:
            'text-primary-main border-2 border-primary-main hover:bg-primary-light',
        gray: 'text-gray-main border-2 border-gray-main hover:bg-gray-light',
        alert: 'text-alert-main border-2 border-alert-main hover:bg-alert-light',
    },
};

const Button = ({
    type = 'submit',
    variant = 'filled',
    colorVariant = 'primary',
    disabled = false,
    className,
    ...props
}: Props) => {
    return (
        <button
            type={type}
            className={`${className} appearance-none p-3 w-full font-bold rounded-lg transition-colors ${disabled ? 'opacity-50 bg-gray-light text-gray-main hover:bg-gray-light hover:text-gray-main border-none cursor-not-allowed' : colorClass[variant][colorVariant]}`}
            disabled={disabled}
            {...props}
        />
    );
};

export default Button;
