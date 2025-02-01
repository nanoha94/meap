'use client';
interface Props {
    size?: 'normal' | 'small';
    children: React.ReactNode;
    onClick: () => void;
}

const TextButton = ({ size = 'normal', children, onClick }: Props) => {
    return (
        <button
            onClick={onClick}
            className={`py-1 px-2 w-fit flex gap-x-1 ${size === 'small' ? 'text-sm' : 'text-base'} font-bold text-primary-main bg-white rounded border border-primary-main transition-colors hover:bg-gray-light`}>
            {children}
        </button>
    );
};

export default TextButton;
