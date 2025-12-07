import React from 'react';
import { colors } from '@/constants/colors';
import { EllipsisVertical, LucideProps } from 'lucide-react';
import itemOpenStyles from '@/styles/itemOpen.module.css';
import LucideIconWrapper from './LucideIconWrapper';

interface ActionButton {
    label: string;
    icon: React.ReactElement<LucideProps>;
    onClick: () => void;
}

type Placement = 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left';

interface Props {
    actionButtons: ActionButton[];
    className?: string;
    placement?: Placement;
}

const positionClass: Record<NonNullable<Props['placement']>, string> = {
    'top-right': '-top-1 right-1',
    'top-left': '-top-1 left-1',
    'bottom-right': '-bottom-1 right-1',
    'bottom-left': '-bottom-1 left-1',
};

const ActionMenu = ({
    actionButtons,
    className,
    placement = 'bottom-right',
}: Props) => {
    const [isOpen, setIsOpen] = React.useState<boolean>(false);
    const containerRef = React.useRef<HTMLDivElement>(null);

    React.useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setIsOpen(false);
            }
        };

        const handleResize = () => {
            if (isOpen) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        window.addEventListener('resize', handleResize);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            window.removeEventListener('resize', handleResize);
        };
    }, [isOpen]);

    return (
        <div className="relative leading-none" ref={containerRef}>
            <button onClick={() => setIsOpen(true)}>
                <EllipsisVertical
                    color={colors.gray.main}
                    className={className}
                />
            </button>
            <div
                className={`z-10 absolute py-1 flex flex-col items-start text-sm bg-white rounded border border-gray-main shadow-lg ${positionClass[placement]} ${
                    isOpen ? itemOpenStyles.open : itemOpenStyles.close
                }`}>
                {actionButtons.map((v, idx) => (
                    <button
                        key={idx}
                        onClick={() => {
                            v.onClick();
                            setIsOpen(false);
                        }}
                        className="px-3 py-1 w-full flex items-center gap-x-2 whitespace-nowrap transition-colors hover:bg-gray-light">
                        <LucideIconWrapper
                            strokeWidth={1.5}
                            color={colors.black}
                            size={14}>
                            {v.icon}
                        </LucideIconWrapper>
                        {v.label}
                    </button>
                ))}
            </div>
        </div>
    );
};

export default ActionMenu;
