'use client';
interface Props {
    className?: string;
    size?: 'normal' | 'small';
    disabled?: boolean;
    children: React.ReactNode;
    onClick: () => void;
}

const TextButton = ({
    className,
    size = 'normal',
    disabled = false,
    children,
    onClick,
}: Props) => {
    return (
        <button
            onClick={onClick}
            className={`py-1 px-2 w-fit flex items-center gap-x-1 ${size === 'small' ? 'text-sm' : 'text-base'} font-bold text-primary-main bg-white rounded border border-primary-main transition-colors hover:bg-gray-light ${disabled ? 'opacity-50' : ''} ${className}`}
            disabled={disabled}>
            {children}
        </button>
    );
};

export default TextButton;
