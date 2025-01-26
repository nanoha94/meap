import React from 'react';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    type?: 'submit' | 'button' | 'reset';
    variant?: 'filled' | 'outlined';
    colorVariant?: 'primary' | 'gray' | 'alert';
    className?: string;
}

const Button = ({
    type = 'submit',
    variant = 'filled',
    colorVariant = 'primary',
    className,
    ...props
}: Props) => {
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

    return (
        <button
            type={type}
            className={`${className} appearance-none p-3 w-full text-base font-bold rounded-lg transition-colors ${colorClass[variant][colorVariant]}`}
            {...props}
        />
    );
};

export default Button;
