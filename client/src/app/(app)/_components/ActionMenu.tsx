import React from 'react';
import { colors } from '@/constants/colors';
import { EllipsisVertical, LucideProps } from 'lucide-react';
import itemOpenStyles from '@/styles/itemOpen.module.css';

interface ActionButton {
    label: string;
    icon: React.ReactElement<LucideProps>;
    onClick: () => void;
}

interface Props {
    actionButtons: ActionButton[];
}

const IconWrapper = ({
    children,
}: {
    children: React.ReactElement<LucideProps>;
}) => (
    <div className="flex items-center">
        {React.cloneElement(children, {
            strokeWidth: 1.5,
            color: colors.black,
            size: 16,
        })}
    </div>
);

const ActionMenu = ({ actionButtons }: Props) => {
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

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    return (
        <div className="relative" ref={containerRef}>
            <button onClick={() => setIsOpen(true)}>
                <EllipsisVertical color={colors.gray.main} />
            </button>
            <div
                className={`z-10 absolute -top-1 right-1 flex flex-col items-start text-sm bg-white rounded border border-gray-main ${
                    isOpen ? itemOpenStyles.open : itemOpenStyles.close
                }`}>
                {actionButtons.map((v, idx) => (
                    <button
                        key={idx}
                        onClick={() => {
                            v.onClick();
                            setIsOpen(false);
                        }}
                        className="px-3 py-1.5 flex items-center gap-x-2 whitespace-nowrap transition-colors hover:bg-gray-light">
                        <IconWrapper>{v.icon}</IconWrapper>
                        {v.label}
                    </button>
                ))}
            </div>
        </div>
    );
};

export default ActionMenu;
