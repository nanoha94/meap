'use client';

import { colors } from '@/constants/colors';

interface Props {
    children: string;
    onClick: () => void;
}

const EmptyButton = ({ children, onClick }: Props) => {
    return (
        <>
            <button
                onClick={onClick}
                className="relative py-3 px-2 w-full text-gray-main rounded bg-gray-background transition-colors hover:text-black hover:bg-white">
                <div
                    className="absolute top-0 left-0 w-full h-full rounded"
                    style={{
                        background: `
                        linear-gradient(to right, ${colors.gray.main}, ${colors.gray.main} 8px, transparent 8px, transparent 4px) repeat-x left top / 12px 1px,
                        linear-gradient(to right, ${colors.gray.main}, ${colors.gray.main} 8px, transparent 8px, transparent 4px) repeat-x left bottom / 12px 1px,
                        linear-gradient(to bottom, ${colors.gray.main}, ${colors.gray.main} 8px, transparent 8px, transparent 4px) repeat-y left top / 1px 12px,
                        linear-gradient(to bottom, ${colors.gray.main}, ${colors.gray.main} 8px, transparent 8px, transparent 4px) repeat-y right top / 1px 12px
                    `,
                    }}
                />
                {children}
            </button>
        </>
    );
};

export default EmptyButton;
