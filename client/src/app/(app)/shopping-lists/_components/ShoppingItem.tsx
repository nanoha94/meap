import React from 'react';
import { ActionMenu } from '../../_components';
import { colors } from '@/constants/colors';
import { Check, GripVertical, Pencil, Pin, PinOff, Trash } from 'lucide-react';
import { IGetShoppingItem } from '@/types/api';

interface Props {
    item: Omit<IGetShoppingItem, 'category'>;
    onDelete: () => void;
    onUpdate: (name: string, isPinned: boolean, isChecked: boolean) => void;
}

const ShoppingItem = ({ item, onDelete, onUpdate }: Props) => {
    const { id, name, isPinned = false, isChecked = false } = item;
    const inputRef = React.useRef<HTMLInputElement>(null);
    const [itemName, setItemName] = React.useState<string>(name);
    const [isEditing, setIsEditing] = React.useState<boolean>(name === '');

    const handleClickOutside = (event: MouseEvent) => {
        if (
            inputRef.current &&
            !inputRef.current.contains(event.target as Node)
        ) {
            const currentValue = inputRef.current.value;

            if (currentValue !== itemName) {
                if (currentValue === '') {
                    setIsEditing(false);
                    return;
                }
                setItemName(currentValue);
                onUpdate(currentValue, isPinned, isChecked);
            } else if (currentValue === '') {
                onDelete();
            }
            setIsEditing(false);
        }
    };

    React.useEffect(() => {
        if (isEditing) {
            inputRef.current?.focus();
        }
    }, [isEditing]);

    React.useEffect(() => {
        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [itemName]);

    return (
        <>
            <div className="absolute w-full h-full">
                {isPinned && (
                    <div className="absolute -top-3 -left-3 bg-primary-main rounded-full p-1">
                        <Pin color={colors.white} size={20} />
                    </div>
                )}
            </div>
            <div className="py-3 px-1 w-full text-left flex items-center justify-between">
                <div className="flex items-center gap-x-4">
                    <GripVertical color={colors.gray.main} />
                    <div>
                        <input
                            type="checkbox"
                            id={`checkbox-${id}`}
                            checked={isChecked}
                            onChange={() => {
                                onUpdate(itemName, isPinned, !isChecked);
                            }}
                            className="hidden"
                        />
                        <label
                            htmlFor={`checkbox-${id}`}
                            className="relative pl-7 w-full h-full whitespace-nowrap cursor-pointer">
                            <div
                                className={`absolute top-1/2 -translate-y-1/2 left-0 w-5 h-5 rounded border-[1.5px] transition-colors ${
                                    isChecked
                                        ? 'bg-primary-main border-[transparent]'
                                        : 'bg-white border-gray-main'
                                }`}>
                                {isChecked && (
                                    <Check
                                        strokeWidth={3.5}
                                        color={colors.white}
                                        size={20}
                                        className="absolute top-1/2 -translate-y-1/2 left-0"
                                    />
                                )}
                            </div>
                            {!isEditing ? (
                                itemName
                            ) : (
                                <input
                                    ref={inputRef}
                                    type="text"
                                    className="relative focus-visible:outline-none"
                                    defaultValue={itemName}
                                />
                            )}
                        </label>
                    </div>
                </div>
                <ActionMenu
                    actionButtons={[
                        {
                            label: '編集する',
                            icon: <Pencil />,
                            onClick: () => setIsEditing(true),
                        },
                        {
                            label: '削除する',
                            icon: <Trash />,
                            onClick: onDelete,
                        },
                        {
                            label: isPinned ? '固定解除する' : '固定する',
                            icon: isPinned ? <PinOff /> : <Pin />,
                            onClick: () =>
                                onUpdate(itemName, !isPinned, isChecked),
                        },
                    ]}
                />
            </div>
        </>
    );
};

export default ShoppingItem;
