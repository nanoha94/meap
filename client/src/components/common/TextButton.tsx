interface Props {
    className?: string;
    size?: 'normal' | 'small';
    disabled?: boolean;
    colorVariant?: 'primary' | 'gray' | 'accent';
    children: React.ReactNode;
    onClick: () => void;
    type?: 'button' | 'submit' | 'reset';
}

const TextButton = ({
    className,
    size = 'normal',
    disabled = false,
    colorVariant = 'primary',
    children,
    onClick,
    type = 'button',
}: Props) => {
    const colorClasses = {
        primary: 'text-primary-main border-primary-main',
        gray: 'text-gray-main border-gray-main',
        accent: 'text-accent-main border-accent-main',
    };

    return (
        <button
            type={type}
            onClick={onClick}
            className={`py-1 px-2 w-fit flex items-center gap-x-1 ${
                size === 'small' ? 'text-sm' : 'text-base'
            } font-bold bg-white rounded border transition-colors hover:bg-gray-light ${
                colorClasses[colorVariant]
            } ${disabled ? 'opacity-50' : ''} ${className}`}
            disabled={disabled}>
            {children}
        </button>
    );
};

export default TextButton;
