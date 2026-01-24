import React from 'react';
import { colors } from '@/constants/colors';
import itemOpenStyles from '@/styles/itemOpen.module.css';
import LucideIconWrapper from './LucideIconWrapper';
import { ActionButton } from '@/types';
import { EllipsisVertical } from 'lucide-react';


type Placement = 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left';

interface Props {
    customButton?: React.ReactElement;
    actionButtons: ActionButton[];
    className?: string;
    placement?: Placement | string;
}

const positionClass: Record<Placement, string> = {
    'top-right': '-top-1 right-1',
    'top-left': '-top-1 left-1',
    'bottom-right': '-bottom-1 right-1',
    'bottom-left': '-bottom-1 left-1',
};

const MenuButton = ({
    customButton,
    actionButtons,
    className,
    placement = 'bottom-right',
}: Props) => {
    const [isOpen, setIsOpen] = React.useState<boolean>(false);
    const containerRef = React.useRef<HTMLDivElement>(null);

    // placementが定義された4パターンの場合はpositionClassから取得、それ以外は直接クラス名として使用
    const getPositionClass = (placement: Placement | string): string => {
        if (placement in positionClass) {
            return positionClass[placement as Placement];
        }
        return placement;
    };

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
            {customButton ? (
                React.cloneElement(customButton as React.ReactElement<{ onClick?: () => void }>, {
                    onClick: () => setIsOpen(true),
                })
            ) : (
                <button onClick={() => setIsOpen(true)}>
                    <EllipsisVertical
                        color={colors.gray.main}
                        className={className}
                    />
                </button>
            )}
            <div
                className={`z-10 absolute py-1 flex flex-col items-start text-sm md:text-base bg-white rounded border border-gray-main shadow-lg ${getPositionClass(placement)} ${isOpen ? itemOpenStyles.open : itemOpenStyles.close
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
                            size={20}>
                            {v.icon}
                        </LucideIconWrapper>
                        {v.label}
                    </button>
                ))}
            </div>
        </div>
    );
};

export default MenuButton;
